/* AquaTrack front-end: theme, live polling, dashboard, reports, chat */
(() => {
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const root = document.documentElement;
  const csrf = $('meta[name=csrf-token]')?.content;
  const cv = n => getComputedStyle(root).getPropertyValue(n).trim();
  const f = (n, d = 2) => (n == null || n === '' ? '–' : Number(n).toFixed(d));
  const jf = (url, opt = {}) =>
    fetch(url, { headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }, ...opt })
      .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); });

  /* ---------- dark mode ---------- */
  const toggle = $('#themeToggle');
  const syncToggle = () => toggle?.setAttribute('aria-pressed', root.dataset.theme === 'dark');
  syncToggle();
  toggle?.addEventListener('click', () => {
    const t = root.dataset.theme === 'dark' ? 'light' : 'dark';
    root.dataset.theme = t;
    try { localStorage.setItem('theme', t); } catch {}
    syncToggle();
    window.dispatchEvent(new Event('themechange'));
  });

  /* ---------- search (filters any [data-search] element) ---------- */
  const applySearch = () => {
    const v = ($('#q')?.value || '').trim().toLowerCase();
    $$('[data-search]').forEach(el => { el.hidden = Boolean(v) && !el.dataset.search.toLowerCase().includes(v); });
  };
  $('#q')?.addEventListener('input', applySearch);

  /* ---------- chart helpers ---------- */
  const themeCharts = () => {
    if (!window.Chart) return;
    Chart.defaults.color = cv('--muted');
    Chart.defaults.borderColor = cv('--grid');
    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
  };
  const fade = (ctx, rgb) => {
    const g = ctx.createLinearGradient(0, 0, 0, ctx.canvas.clientHeight || 250);
    g.addColorStop(0, `rgba(${rgb},.38)`); g.addColorStop(1, `rgba(${rgb},0)`);
    return g;
  };
  const legend = { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } };

  /* ---------- live polling (device chip on every page) ---------- */
  const subs = [];
  const chip = s => {
    const c = $('#devChip'); if (!c) return;
    const on = Boolean(s && s.online);
    c.dataset.on = on ? 1 : 0;
    $('span', c).textContent = on ? 'ESP32 online' : (s?.latest ? 'Device idle' : 'Waiting for device');
  };
  let busy = false;
  const poll = async () => {
    if (document.hidden || busy) return;
    busy = true;
    try { const s = await jf(ROUTES.live); chip(s); subs.forEach(fn => fn(s)); } catch { chip(null); }
    busy = false;
  };

  const pages = {};

  /* ---------- dashboard ---------- */
  pages.dashboard = () => {
    const ledTxt = { ACIDIC: 'Yellow light: acidic', NORMAL: 'Green light: normal', ALKALINE: 'Red light: alkaline' };
    let chart, last = window.__SNAP;

    const build = () => {
      themeCharts(); chart?.destroy();
      const ctx = $('#liveChart').getContext('2d');
      const line = (label, color) => ({ label, data: [], borderColor: cv(color), borderDash: [6, 6], pointRadius: 0, borderWidth: 1.5 });
      chart = new Chart(ctx, {
        type: 'line',
        data: { labels: [], datasets: [
          { label: 'pH', data: [], borderColor: cv('--accent'), backgroundColor: fade(ctx, '79,124,255'), fill: true, tension: .4, pointRadius: 0, pointHoverRadius: 5, borderWidth: 3 },
          line(`Acidic below ${PH.low}`, '--warn'), line(`Alkaline above ${PH.high}`, '--bad'),
        ] },
        options: { responsive: true, maintainAspectRatio: false, animation: false, interaction: { mode: 'index', intersect: false },
          scales: { y: { min: 0, max: 14, ticks: { stepSize: 2 } }, x: { grid: { display: false }, ticks: { maxTicksLimit: 6, maxRotation: 0 } } },
          plugins: { legend } },
      });
    };

    const paint = s => {
      last = s;
      const l = s.latest, st = l?.status;
      $('#hero').dataset.s = st || '';
      $('#phNum').textContent = l ? f(l.ph) : '–';
      const b = $('#status'); b.textContent = st ? st[0] + st.slice(1).toLowerCase() : 'No data'; b.className = 'badge ' + (st || '').toLowerCase();
      $('#marker').style.left = (l ? Math.min(100, Math.max(0, l.ph / 14 * 100)) : 50) + '%';
      $('#ledTxt').textContent = ledTxt[st] || 'LED off';
      $('#raw').textContent = l ? l.raw : '–';
      $('#volt').textContent = l ? f(l.voltage, 3) + ' V' : '–';
      $('#upd').textContent = l?.time || '–';
      $('#sAvg').textContent = f(s.stats.avg); $('#sLo').textContent = f(s.stats.lo);
      $('#sHi').textContent = f(s.stats.hi); $('#sN').textContent = s.stats.count;

      $('#empty').hidden = s.recent.length > 0;
      $('#rows').replaceChildren(...s.recent.map(r => {
        const d = document.createElement('div');
        d.className = 'row';
        d.dataset.search = `${r.time} ${r.status} ${r.ph}`;
        d.innerHTML = `<span class="t">${r.time}</span><b class="p">${f(r.ph)}</b><span class="v">${f(r.voltage, 3)} V</span>` +
          `<span class="r">ADC ${Number(r.raw)}</span><span class="s badge ${r.status.toLowerCase()}">${r.status[0] + r.status.slice(1).toLowerCase()}</span>`;
        return d;
      }));
      applySearch();

      chart.data.labels = s.series.labels;
      chart.data.datasets[0].data = s.series.values;
      chart.data.datasets[1].data = s.series.labels.map(() => PH.low);
      chart.data.datasets[2].data = s.series.labels.map(() => PH.high);
      chart.update('none');
    };

    $('#sim').addEventListener('click', async e => {
      const btn = e.currentTarget; btn.disabled = true;
      try { await jf(ROUTES.simulate, { method: 'POST' }); busy = false; await poll(); } catch {}
      btn.disabled = false;
    });

    build(); paint(window.__SNAP); subs.push(paint);
    window.addEventListener('themechange', () => { build(); paint(last); });
  };

  /* ---------- reports ---------- */
  pages.reports = () => {
    let range = '24h', data, charts = [];
    const make = (id, cfg) => charts.push(new Chart($(id).getContext('2d'), cfg));
    const base = { responsive: true, maintainAspectRatio: false };
    const xAxis = { grid: { display: false }, ticks: { maxTicksLimit: 8, maxRotation: 0 } };

    const render = () => {
      themeCharts(); charts.forEach(c => c.destroy()); charts = [];
      const d = data, s = d.stats;
      $('#empty').hidden = s.count > 0;
      $('#rAvg').textContent = f(s.avg); $('#rLo').textContent = f(s.lo); $('#rHi').textContent = f(s.hi);
      $('#rNormal').textContent = s.normal_pct == null ? '–' : s.normal_pct + '%'; $('#rN').textContent = s.count;

      const ctx = $('#cLine').getContext('2d');
      const limit = (label, c) => ({ label, data: d.labels.map(() => c === '--warn' ? PH.low : PH.high), borderColor: cv(c), borderDash: [6, 6], pointRadius: 0, borderWidth: 1.5 });
      make('#cLine', { type: 'line',
        data: { labels: d.labels, datasets: [
          { label: 'Average pH', data: d.avg, borderColor: cv('--accent'), backgroundColor: fade(ctx, '79,124,255'), fill: true, tension: .35, pointRadius: d.labels.length > 40 ? 0 : 3, borderWidth: 3 },
          limit('Acidic limit', '--warn'), limit('Alkaline limit', '--bad') ] },
        options: { ...base, interaction: { mode: 'index', intersect: false }, scales: { y: { min: 0, max: 14, ticks: { stepSize: 2 } }, x: xAxis }, plugins: { legend } } });
      make('#cPie', { type: 'doughnut',
        data: { labels: ['Acidic', 'Normal', 'Alkaline'], datasets: [{ data: [d.dist.ACIDIC, d.dist.NORMAL, d.dist.ALKALINE], backgroundColor: [cv('--warn'), cv('--ok'), cv('--bad')], borderWidth: 0, hoverOffset: 8 }] },
        options: { ...base, cutout: '68%', plugins: { legend } } });
      make('#cBar', { type: 'bar',
        data: { labels: d.labels, datasets: [{ label: 'Out-of-range readings', data: d.bad, backgroundColor: cv('--bad'), borderRadius: 6, maxBarThickness: 22 }] },
        options: { ...base, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: xAxis }, plugins: { legend: { display: false } } } });
    };

    const load = async () => {
      try { data = await jf(`${ROUTES.reportData}?range=${range}`); } catch { return; }
      $('#export').href = `${ROUTES.export}?range=${range}`;
      render();
    };
    $$('[data-range]').forEach(b => b.addEventListener('click', () => {
      range = b.dataset.range;
      $$('[data-range]').forEach(x => x.classList.toggle('on', x === b));
      load();
    }));
    window.addEventListener('themechange', () => data && render());
    load();
    setInterval(() => { if (!document.hidden) load(); }, 30000);
  };

  /* ---------- chat ---------- */
  pages.chat = () => {
    const list = $('#msgs'), hint = $('#hint'), seen = new Set();
    let last = 0;
    const nearBottom = () => innerHeight + scrollY >= document.body.scrollHeight - 200;
    const toEnd = smooth => scrollTo({ top: document.body.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });

    const add = m => {
      if (seen.has(m.id)) return;
      seen.add(m.id); last = Math.max(last, m.id);
      const el = document.createElement('div');
      el.className = `msg ${m.kind}${m.mine ? ' mine' : ''}`;
      el.dataset.search = `${m.author} ${m.body}`;
      const meta = document.createElement('div'); meta.className = 'meta2'; meta.textContent = `${m.author}, ${m.time}`;
      const body = document.createElement('div'); body.textContent = m.body;
      el.append(meta, body); list.append(el); hint.hidden = true;
    };

    hint.hidden = (window.__MSGS || []).length > 0;
    (window.__MSGS || []).forEach(add);
    toEnd(false);

    $$('#chatSeg button').forEach(b => b.addEventListener('click', () => {
      list.dataset.f = b.dataset.f;
      $$('#chatSeg button').forEach(x => x.classList.toggle('on', x === b));
    }));

    setInterval(async () => {
      if (document.hidden) return;
      try {
        const ms = await jf(`${ROUTES.messages}?after=${last}`);
        if (!ms.length) return;
        const stick = nearBottom();
        ms.forEach(add); applySearch();
        if (stick) toEnd(true);
      } catch {}
    }, 2500);

    $('#composer')?.addEventListener('submit', async e => {
      e.preventDefault();
      const input = $('#body'), body = input.value.trim();
      if (!body) return;
      input.value = '';
      try { add(await jf(ROUTES.send, { method: 'POST', body: JSON.stringify({ body }) })); toEnd(true); }
      catch { input.value = body; }
    });
  };

  pages[document.body.dataset.page]?.();
  poll();
  setInterval(poll, 3000);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
})();
