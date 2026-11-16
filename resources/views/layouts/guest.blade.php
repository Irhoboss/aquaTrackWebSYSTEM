<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>@yield('title', 'Sign in') · AquaTrack</title>
  @if(!empty($brand['favicon']))<link rel="icon" href="{{ $brand['favicon'] }}">@endif
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="{{ asset('css/aquatrack.css') }}?v={{ filemtime(public_path('css/aquatrack.css')) }}" rel="stylesheet">
  <script nonce="{{ request()->attributes->get('csp_nonce') }}">(function(){try{var t=localStorage.getItem('aq-theme');if(!t)t=matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';document.documentElement.dataset.theme=t}catch(e){}})()</script>
</head>
<body>
  <a class="skip-link" href="#main-content">Skip to main content</a>
  @if(request()->routeIs('login', 'register'))
    <main class="login-screen" id="main-content" tabindex="-1">
      <a class="login-back-link" href="{{ route('home') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to landing page</a>
      <section class="login-showcase">
        <a class="login-brand" href="{{ route('home') }}">
          @if(!empty($brand['logo']))<img src="{{ $brand['logo'] }}" alt="">@else<span class="login-brand-mark"><i class="bi bi-droplet-fill"></i></span>@endif
          <span><strong>{{ $brand['name'] ?? 'AquaTrack' }}</strong><small>WATER INTELLIGENCE</small></span>
        </a>
        <div class="login-intro"><span class="login-eyebrow"><i class="bi bi-activity"></i> AQUATRACK PLATFORM</span><h1>Monitor. Track.<br>Protect.</h1><p>Water quality, inventory, and station activity—organized in one clear view.</p></div>
        <div class="login-art" aria-hidden="true">
          <div class="login-art-orbit orbit-one"></div><div class="login-art-orbit orbit-two"></div>
          <div class="login-art-card"><div class="login-art-top"><span><i class="bi bi-droplet-half"></i> Water quality</span><span class="login-live"><i></i> Live</span></div><div class="login-art-reading"><strong>7.2</strong><span>pH level<br><b>Within normal range</b></span></div><div class="login-art-chart"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div><div class="login-art-labels"><span>6 hours ago</span><span>Now</span></div></div>
          <span class="login-float float-drop"><i class="bi bi-droplet-fill"></i></span><span class="login-float float-shield"><i class="bi bi-shield-check"></i></span><span class="login-float float-box"><i class="bi bi-box-seam"></i></span>
        </div>
        <div class="login-features"><span><i class="bi bi-broadcast-pin"></i> Live monitoring</span><span><i class="bi bi-shield-lock"></i> Secure access</span><span><i class="bi bi-box-seam"></i> Station ready</span></div>
      </section>
      <section class="login-panel{{ request()->routeIs('register') ? ' login-panel-register' : '' }}" data-auth-panel-container>
        <button class="btn btn-soft theme-toggle auth-theme-toggle" type="button" data-theme-toggle aria-label="Switch color theme"><i class="bi bi-moon-stars-fill"></i><span>Dark mode</span></button>
        <div class="login-form-card">
          @include('partials.flash')
          @yield('content')
        </div>

        <p class="login-help">AquaTrack <span>·</span> Water quality and station management</p>
      </section>
      <footer class="login-footer"><span>© {{ now()->year }} {{ $brand['name'] ?? 'AquaTrack' }}</span><nav aria-label="Legal links"><a href="{{ route('legal.privacy') }}">Privacy</a> · <a href="{{ route('legal.terms') }}">Terms</a> · <a href="{{ route('legal.refunds') }}">Refunds</a> · <a href="{{ route('legal.cookies') }}">Cookies</a> · <button type="button" class="footer-link" data-cookie-reopen>Cookie settings</button></nav></footer>
    </main>
  @else
    <main class="auth-wrap" id="main-content" tabindex="-1">
      <button class="btn btn-soft theme-toggle auth-theme-toggle" type="button" data-theme-toggle aria-label="Switch color theme"><i class="bi bi-moon-stars-fill"></i><span>Dark mode</span></button>
      <div class="at-card auth-card">
        <div class="auth-hero">
          @if(!empty($brand['logo']))<img src="{{ $brand['logo'] }}" alt="" style="width:84px;height:84px;object-fit:contain" class="mb-3">@else<div class="at-drop"><i class="bi bi-droplet-fill"></i></div>@endif
          <h2 class="h3 mb-1">@yield('heading')</h2>
          <p class="text-muted mb-0">@yield('lead')</p>
        </div>
        @include('partials.flash')
        @yield('content')
      </div>
      <footer class="auth-legal-links" aria-label="Legal links">
        <a href="{{ route('legal.privacy') }}">Privacy</a><a href="{{ route('legal.terms') }}">Terms</a><a href="{{ route('legal.refunds') }}">Refunds</a><a href="{{ route('legal.cookies') }}">Cookies</a><button type="button" class="footer-link" data-cookie-reopen>Cookie settings</button>
      </footer>
    </main>
  @endif
  @if(request()->routeIs('login') && session('signed_out'))
    <div class="modal fade aq-logout-modal" id="aqSignedOutModal" tabindex="-1" aria-labelledby="aqSignedOutTitle" aria-describedby="aqSignedOutMessage" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-body">
          <div class="aq-logout-icon aq-logout-success" aria-hidden="true"><svg class="aq-session-mark aq-session-mark-check" viewBox="0 0 24 24" focusable="false"><path d="m5 12 4.5 4.5L19 7"></path></svg></div>
          <h2 class="aq-logout-title" id="aqSignedOutTitle">Signed out</h2>
          <p class="aq-logout-message" id="aqSignedOutMessage">You have been signed out of AquaTrack. See you soon!</p>
          <div class="aq-logout-actions">
            <button type="button" class="btn btn-primary aq-logout-button" data-bs-dismiss="modal">Continue</button>
          </div>
        </div>
      </div></div>
    </div>
  @endif
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  @include('partials.cookie-consent')
  <script src="{{ asset('js/aquatrack.js') }}?v={{ filemtime(public_path('js/aquatrack.js')) }}"></script>
</body>
</html>
