<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>@yield('title', 'Sign in') · AquaTrack</title>
  @if(!empty($brand['favicon']))<link rel="icon" href="{{ $brand['favicon'] }}">@endif
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="{{ asset('css/aquatrack.css') }}?v={{ filemtime(public_path('css/aquatrack.css')) }}" rel="stylesheet">
  <script>(function(){try{var t=localStorage.getItem('aq-theme');if(!t)t=matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';document.documentElement.dataset.theme=t}catch(e){}})()</script>
</head>
<body>
  @if(request()->routeIs('login'))
    <main class="login-screen">
      <section class="login-showcase">
        <a class="login-brand" href="{{ route('login') }}">
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
      <section class="login-panel">
        <button class="btn btn-soft theme-toggle auth-theme-toggle" type="button" data-theme-toggle aria-label="Switch color theme"><i class="bi bi-moon-stars-fill"></i><span>Dark mode</span></button>
        <div class="login-form-card">
          <div class="login-form-heading"><h2>Welcome back</h2><p>Sign in to continue</p></div>
          @include('partials.flash')
          @yield('content')
        </div>
        <p class="login-help">AquaTrack <span>·</span> Water quality and station management</p>
      </section>
      <footer class="login-footer"><span>© {{ now()->year }} {{ $brand['name'] ?? 'AquaTrack' }}</span><span>Water quality. In clear view.</span><span>Secure station access</span></footer>
    </main>
  @else
    <main class="auth-wrap">
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
    </main>
  @endif
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="{{ asset('js/aquatrack.js') }}?v={{ filemtime(public_path('js/aquatrack.js')) }}"></script>
</body>
</html>
