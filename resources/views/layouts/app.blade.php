<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Dashboard') · AquaTrack</title>
  @if($brand['favicon'])<link rel="icon" href="{{ $brand['favicon'] }}">@endif
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="{{ asset('css/aquatrack.css') }}?v={{ filemtime(public_path('css/aquatrack.css')) }}" rel="stylesheet">
  <script>(function(){try{var t=localStorage.getItem('aq-theme');if(!t)t=matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';document.documentElement.dataset.theme=t}catch(e){}})()</script>
  @stack('head')
</head>
<body>
  <aside class="at-sidebar" aria-label="Main navigation">@include('partials.sidebar')</aside>

  <div class="offcanvas offcanvas-start" tabindex="-1" id="menu" style="width: 290px">
    <div class="offcanvas-header"><h5 class="offcanvas-title">Menu</h5><button class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button></div>
    <div class="offcanvas-body">@include('partials.sidebar', ['mobile' => true])</div>
  </div>

  <div class="at-main">
    <header class="at-topbar">
      <button class="btn btn-soft btn-sm d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#menu" aria-label="Open menu"><i class="bi bi-list fs-5"></i></button>
      <div class="me-auto">
        <h1>@yield('title', 'Dashboard')</h1>
        @hasSection('subtitle')<div class="small text-muted d-none d-sm-block">@yield('subtitle')</div>@endif
      </div>
      <button class="btn btn-soft theme-toggle" type="button" data-theme-toggle aria-label="Switch color theme" aria-pressed="false"><i class="bi bi-moon-stars-fill"></i><span class="d-none d-md-inline">Dark mode</span></button>

      @if(auth()->user()->role === 'customer')
        @php $cartCount = array_sum(session('aq_cart', [])); @endphp
        <a class="btn btn-soft position-relative" href="{{ route('cart.index') }}" aria-label="Shopping cart, {{ $cartCount }} items"><i class="bi bi-cart3"></i><span class="d-none d-md-inline ms-1">Cart</span>@if($cartCount)<span class="badge rounded-pill text-bg-danger position-absolute top-0 start-100 translate-middle">{{ $cartCount }}</span>@endif</a>
      @endif

      <div class="dropdown">
        <button class="btn btn-soft at-bell position-relative" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-label="Notifications">
          <i class="bi bi-bell"></i>
          <span id="unreadBadge" data-summary-url="{{ route('notifications.summary') }}" class="badge rounded-pill text-bg-danger" @if($unreadCount < 1) hidden @endif>{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
        </button>
        <div class="dropdown-menu dropdown-menu-end p-2" style="width: min(360px, 92vw)">
          <div class="d-flex justify-content-between align-items-center px-2 py-1">
            <strong>Notifications</strong>
            @if($unreadCount > 0)
              <form method="post" action="{{ route('notifications.readall') }}" data-no-loading="1">@csrf<button class="btn btn-link btn-sm p-0">Mark all as read</button></form>
            @endif
          </div>
          @forelse($navNotifications as $n)
            <form method="post" action="{{ route('notifications.read', $n) }}?go=1" data-no-loading="1">@csrf
              <button class="dropdown-item text-wrap d-flex gap-2 align-items-start {{ $n->read_at ? 'opacity-75' : '' }}">
                <span class="dot {{ $n->read_at ? '' : 'on' }} mt-2 flex-shrink-0"></span>
                <span><span class="d-block fw-bold">{{ $n->title }}</span><span class="d-block small text-muted">{{ \Illuminate\Support\Str::limit($n->body, 80) }}</span><span class="small text-muted">@dt($n->created_at)</span></span>
              </button>
            </form>
          @empty
            <div class="empty py-3"><i class="bi bi-bell-slash"></i>You are all caught up.</div>
          @endforelse
          <a class="dropdown-item text-center fw-bold mt-1" href="{{ route('notifications.index') }}">View all notifications</a>
        </div>
      </div>

      <div class="dropdown">
        <button class="btn p-0 border-0" data-bs-toggle="dropdown" aria-label="Account menu">
          @if(auth()->user()->avatar_url)<img class="at-avatar" src="{{ auth()->user()->avatar_url }}" alt="">@else<span class="at-avatar">{{ auth()->user()->initials }}</span>@endif
        </button>
        <div class="dropdown-menu dropdown-menu-end" style="min-width: 220px">
          <div class="px-3 py-2"><div class="fw-bold">{{ auth()->user()->name }}</div><div class="small text-muted">{{ auth()->user()->role_label }}</div></div>
          <a class="dropdown-item" href="{{ route('profile.show') }}"><i class="bi bi-person me-2"></i>My profile</a>
          <a class="dropdown-item" href="{{ route('profile.show') }}#password"><i class="bi bi-key me-2"></i>Change password</a>
          <hr class="my-1">
          <form method="post" action="{{ route('logout') }}">@csrf<button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Sign out</button></form>
        </div>
      </div>
    </header>

    @include('partials.flash')
    @yield('content')

    <footer class="text-center small text-muted mt-4 no-print">{{ $brand['name'] }} · AquaTrack. Sensor readings are a monitoring aid and do not certify water as potable.</footer>
  </div>

  <nav class="at-bottom" aria-label="Quick navigation">
    @if(auth()->user()->role === 'customer')<a href="{{ route('store.index') }}" class="{{ request()->routeIs('store.*') ? 'active' : '' }}"><i class="bi bi-shop"></i>Shop</a><a href="{{ route('cart.index') }}" class="{{ request()->routeIs('cart.*', 'checkout.*') ? 'active' : '' }}"><i class="bi bi-cart3"></i>Cart</a><a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.*') ? 'active' : '' }}"><i class="bi bi-receipt"></i>Orders</a><a href="{{ route('support-chat.index') }}" class="{{ request()->routeIs('support-chat.*') ? 'active' : '' }}"><i class="bi bi-chat-dots"></i>Chat</a>
    @else
    @can('dashboard.view')<a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="bi bi-grid-1x2"></i>Home</a>@endcan
    @can('monitoring.view')<a href="{{ route('monitoring.index') }}" class="{{ request()->routeIs('monitoring.*', 'readings.*') ? 'active' : '' }}"><i class="bi bi-droplet-half"></i>pH</a>
    @else<a href="{{ route('alerts.index') }}" class="{{ request()->routeIs('alerts.*') ? 'active' : '' }}"><i class="bi bi-bell"></i>Alerts</a>@endcan
    @can('inventory.view')<a href="{{ route('inventory.index') }}" class="{{ request()->routeIs('inventory.*') ? 'active' : '' }}"><i class="bi bi-box-seam"></i>Stock</a>@endcan
    @can('containers.view')<a href="{{ route('containers.index') }}" class="{{ request()->routeIs('containers.*') ? 'active' : '' }}"><i class="bi bi-bucket"></i>Containers</a>@endcan
    <button data-bs-toggle="offcanvas" data-bs-target="#menu" aria-label="More"><i class="bi bi-three-dots"></i>More</button>
    @endif
  </nav>

  <div class="modal fade" id="aqConfirmModal" tabindex="-1" aria-labelledby="aqConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content at-card">
      <div class="modal-header border-0 pb-1"><h2 class="modal-title fs-5" id="aqConfirmTitle">Confirm action</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body pt-2" id="aqConfirmMessage">Are you sure you want to continue?</div>
      <div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-soft" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="aqConfirmProceed">Continue</button></div>
    </div></div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
  <script src="{{ asset('js/aquatrack.js') }}?v={{ filemtime(public_path('js/aquatrack.js')) }}"></script>
  @stack('scripts')
</body>
</html>
