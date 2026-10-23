@php
  $groups = [
    'Overview' => [
      ['dashboard', 'bi-grid-1x2', 'Dashboard', 'dashboard.view', 'dashboard'],
      ['monitoring.index', 'bi-droplet-half', 'pH monitoring', 'monitoring.view', 'monitoring.*'],
      ['readings.index', 'bi-clock-history', 'Reading history', 'monitoring.view', 'readings.*'],
      ['alerts.index', 'bi-bell', 'Alerts', 'alerts.view', 'alerts.*'],
    ],
    'Operations' => [
      ['inventory.index', 'bi-box-seam', 'Inventory', 'inventory.view', 'inventory.index|inventory.show|inventory.create|inventory.edit'],
      ['inventory.movements', 'bi-arrow-left-right', 'Stock movements', 'inventory.view', 'inventory.movements'],
      ['suppliers.index', 'bi-truck', 'Suppliers', 'suppliers.manage', 'suppliers.*'],
      ['containers.index', 'bi-bucket', 'Containers', 'containers.view', 'containers.*'],
    ],
    'Insights' => [
      ['reports.index', 'bi-bar-chart-line', 'Reports', 'reports.view', 'reports.*'],
      ['audit.index', 'bi-journal-text', 'Audit log', 'audit.view', 'audit.*'],
    ],
    'Customer care' => [
      ['support-chat.index', 'bi-chat-dots', 'Customer chat', 'support.chat', 'support-chat.*'],
    ],
    'Administration' => [
      ['users.index', 'bi-people', 'Users', 'users.manage', 'users.*'],
      ['settings.index', 'bi-sliders', 'Settings', 'settings.manage', 'settings.*'],
    ],
  ];
  if (auth()->user()->role === 'driver') {
    $groups = ['Driver' => [
      ['driver.dashboard', 'bi-speedometer2', 'Delivery home', 'driver.access', 'driver.dashboard'],
      ['driver.scan', 'bi-qr-code-scan', 'Scan QR code', 'driver.access', 'driver.scan'],
    ]];
  } else {
    $groups['Operations'][] = ['operations.orders.index', 'bi-receipt', 'Orders', 'orders.manage', 'operations.orders.*'];
  }
@endphp
<a href="{{ auth()->user()->role === 'customer' ? route('store.index') : (auth()->user()->role === 'driver' ? route('driver.dashboard') : route('dashboard')) }}" class="at-brand">
  @if($brand['logo'])<img src="{{ $brand['logo'] }}" alt="">@else<span class="at-drop"><i class="bi bi-droplet-fill"></i></span>@endif
  <span>AquaTrack<small class="d-block fw-semibold text-muted" style="font-size:.7rem">{{ \Illuminate\Support\Str::limit($brand['name'], 26) }}</small></span>
</a>
<nav class="at-nav">
  @if(auth()->user()->role === 'customer')
    <div class="grp">Shop</div>
    <a href="{{ route('store.index') }}" title="Browse products" class="{{ request()->routeIs('store.*') ? 'active' : '' }}"><i class="bi bi-shop"></i><span>Browse products</span></a>
    <a href="{{ route('cart.index') }}" title="Shopping cart" class="{{ request()->routeIs('cart.*', 'checkout.*') ? 'active' : '' }}"><i class="bi bi-cart3"></i><span>Shopping cart</span></a>
    <a href="{{ route('orders.index') }}" title="My orders" class="{{ request()->routeIs('orders.*') ? 'active' : '' }}"><i class="bi bi-receipt"></i><span>My orders</span></a>
    <a href="{{ route('support-chat.index') }}" title="Contact support" class="{{ request()->routeIs('support-chat.*') ? 'active' : '' }}"><i class="bi bi-chat-dots"></i><span>Contact support</span></a>
  @endif
  @foreach($groups as $title => $links)
    @php $visible = collect($links)->filter(fn ($l) => auth()->user()->can($l[3])); @endphp
    @if($visible->isNotEmpty())
      <div class="grp">{{ $title }}</div>
      @foreach($visible as [$route, $icon, $label, $ability, $pattern])
        <a href="{{ route($route) }}" title="{{ $label }}" class="{{ request()->routeIs(...explode('|', $pattern)) ? 'active' : '' }}"><i class="bi {{ $icon }}"></i><span>{{ $label }}</span></a>
      @endforeach
    @endif
  @endforeach
  <div class="grp">Account</div>
  <a href="{{ route('profile.show') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}"><i class="bi bi-person-circle"></i>My profile</a>
</nav>
