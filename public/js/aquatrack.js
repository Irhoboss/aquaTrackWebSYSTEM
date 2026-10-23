/* AquaTrack helpers */
(function () {
  const root = document.documentElement;
  function setTheme(theme) {
    root.dataset.theme = theme;
    try { localStorage.setItem('aq-theme', theme); } catch (e) { }
    document.querySelectorAll('[data-theme-toggle]').forEach(button => {
      const dark = theme === 'dark';
      button.setAttribute('aria-pressed', String(dark));
      const icon = button.querySelector('i');
      if (icon) icon.className = `bi ${dark ? 'bi-sun-fill' : 'bi-moon-stars-fill'}`;
      const label = button.querySelector('span');
      if (label) label.textContent = dark ? 'Light mode' : 'Dark mode';
    });
  }
  setTheme(root.dataset.theme || 'light');
  document.addEventListener('click', e => {
    if (e.target.closest('[data-theme-toggle]')) setTheme(root.dataset.theme === 'dark' ? 'light' : 'dark');
  });

  const sidebarButtons = document.querySelectorAll('[data-sidebar-toggle]');
  const syncSidebarButton = () => sidebarButtons.forEach(button => {
    const hidden = document.body.classList.contains('sidebar-off');
    button.setAttribute('aria-pressed', String(hidden));
    button.setAttribute('aria-label', hidden ? 'Show sidebar' : 'Hide sidebar');
    button.title = hidden ? 'Show sidebar' : 'Hide sidebar';
    const icon = button.querySelector('i');
    if (icon) icon.className = `bi ${hidden ? 'bi-layout-sidebar' : 'bi-layout-sidebar-inset'}`;
  });
  try {
    if (localStorage.getItem('aq-sidebar') === 'off') document.body.classList.add('sidebar-off');
  } catch (e) { }
  syncSidebarButton();
  sidebarButtons.forEach(button => button.addEventListener('click', () => {
    document.body.classList.toggle('sidebar-off');
    try { localStorage.setItem('aq-sidebar', document.body.classList.contains('sidebar-off') ? 'off' : 'on'); } catch (e) { }
    syncSidebarButton();
  }));

  // Add an accessible show/hide control to every password field.
  document.querySelectorAll('input[type="password"]:not([data-no-password-toggle])').forEach(input => {
    if (input.dataset.passwordToggleReady) return;
    input.dataset.passwordToggleReady = '1';
    const field = document.createElement('span');
    field.className = 'password-field';
    input.parentNode.insertBefore(field, input);
    field.appendChild(input);

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'password-toggle';
    toggle.setAttribute('aria-label', 'Show password');
    toggle.setAttribute('aria-pressed', 'false');
    toggle.innerHTML = '<i class="bi bi-eye" aria-hidden="true"></i>';
    toggle.addEventListener('click', () => {
      const reveal = input.type === 'password';
      input.type = reveal ? 'text' : 'password';
      toggle.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
      toggle.setAttribute('aria-pressed', String(reveal));
      toggle.innerHTML = `<i class="bi ${reveal ? 'bi-eye-slash' : 'bi-eye'}" aria-hidden="true"></i>`;
      input.focus({ preventScroll: true });
    });
    field.appendChild(toggle);
  });

  // Confirm destructive / important actions with a focused app modal.
  let confirmForm = null;
  let confirmSubmitter = null;
  document.addEventListener('submit', function (e) {
    const f = e.target;
    if (f.dataset.confirm && f.dataset.confirmed !== '1') {
      e.preventDefault();
      confirmForm = f;
      confirmSubmitter = e.submitter;
      const message = document.getElementById('aqConfirmMessage');
      const modalEl = document.getElementById('aqConfirmModal');
      if (message && modalEl && window.bootstrap?.Modal) {
        message.textContent = f.dataset.confirm;
        const proceed = document.getElementById('aqConfirmProceed');
        if (proceed) proceed.textContent = f.dataset.confirmLabel || 'Continue';
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
      } else if (window.confirm(f.dataset.confirm)) {
        f.dataset.confirmed = '1';
        f.requestSubmit(e.submitter || undefined);
      } else {
        confirmForm = null;
        confirmSubmitter = null;
      }
      return;
    }
    if (f.dataset.confirmed === '1') delete f.dataset.confirmed;
    // loading indicator on submit buttons (not for downloads)
    const b = f.querySelector('button[type=submit]:not([data-no-loading])');
    if (b && !f.dataset.noLoading) { setTimeout(() => { b.disabled = true; b.classList.add('loading'); }, 0); }
  });

  document.getElementById('aqConfirmProceed')?.addEventListener('click', () => {
    const form = confirmForm;
    const submitter = confirmSubmitter;
    confirmForm = null;
    confirmSubmitter = null;
    if (!form) return;
    form.dataset.confirmed = '1';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('aqConfirmModal')).hide();
    form.requestSubmit(submitter || undefined);
  });
  document.getElementById('aqConfirmModal')?.addEventListener('hidden.bs.modal', () => {
    confirmForm = null;
    confirmSubmitter = null;
    const proceed = document.getElementById('aqConfirmProceed');
    if (proceed) proceed.textContent = 'Continue';
  });

  // Copy-to-clipboard buttons: <button data-copy="#selector">
  document.addEventListener('click', function (e) {
    const b = e.target.closest('[data-copy]');
    if (!b) return;
    const el = document.querySelector(b.dataset.copy);
    if (el) navigator.clipboard.writeText(el.textContent.trim()).then(() => { const t = b.textContent; b.textContent = 'Copied'; setTimeout(() => b.textContent = t, 1500); });
  });

  // Poll without overlapping requests.
  window.AQ = {
    poll(url, handler, ms) {
      let busy = false;
      const run = async () => {
        if (busy || document.hidden) return;
        busy = true;
        try {
          const r = await fetch(typeof url === 'function' ? url() : url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
          if (r.status === 401 || r.status === 419) { location.reload(); return; }
          if (r.ok) handler(await r.json());
        } catch (err) { /* network hiccup: try again next tick */ }
        finally { busy = false; }
      };
      run();
      return setInterval(run, ms);
    },
    lineChart(canvas, cfg) {
      return new Chart(canvas, {
        type: 'line',
        data: { labels: [], datasets: [{ label: 'pH', data: [], borderColor: '#14998b', backgroundColor: 'rgba(43,196,180,.15)', fill: true, tension: .35, pointRadius: 0, borderWidth: 2.5 }] },
        options: { responsive: true, maintainAspectRatio: false, animation: { duration: 650, easing: 'easeOutQuart' }, interaction: { mode: 'index', intersect: false },
          scales: { y: { suggestedMin: 5, suggestedMax: 10, grid: { color: 'rgba(20,50,58,.06)' } }, x: { grid: { display: false }, ticks: { maxTicksLimit: 8 } } },
          plugins: { legend: { display: false } } },
      });
    },
    setChart(chart, labels, values, min, max) {
      chart.data.labels = labels; chart.data.datasets[0].data = values;
      if (min !== undefined) {
        chart.data.datasets[1] = { label: 'Min', data: labels.map(() => min), borderColor: 'rgba(217,138,18,.6)', borderDash: [6, 6], pointRadius: 0, borderWidth: 1.5, fill: false };
        chart.data.datasets[2] = { label: 'Max', data: labels.map(() => max), borderColor: 'rgba(214,69,69,.6)', borderDash: [6, 6], pointRadius: 0, borderWidth: 1.5, fill: false };
      }
      chart.update('active');
    },
  };

  const unreadBadge = document.getElementById('unreadBadge');
  if (unreadBadge) {
    const refreshUnread = async () => {
      try {
        const response = await fetch(unreadBadge.dataset.summaryUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
        if (!response.ok) return;
        const data = await response.json();
        unreadBadge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
        unreadBadge.hidden = data.unread_count < 1;
      } catch (e) { /* retry on the next interval */ }
    };
    setInterval(refreshUnread, 30000);
  }
})();
