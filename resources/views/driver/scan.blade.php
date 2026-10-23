@extends('layouts.app')
@section('title', 'QR scanner')
@section('subtitle', 'Scan a delivery order or water container')
@section('content')
<div class="driver-scan-layout">
  <section class="at-card card-body-pad driver-scan-card">
    <div class="driver-scan-heading"><span class="driver-scan-icon"><i class="bi bi-qr-code-scan"></i></span><div><h2 class="h5 mb-1">Scan a QR code</h2><p class="small text-muted mb-0">Point the camera at an AquaTrack order or container label.</p></div></div>
    <button class="btn btn-primary w-100 mt-3" type="button" id="startQr"><i class="bi bi-camera me-2"></i>Start camera scanner</button>
    <div id="qr-reader" class="driver-qr-reader mt-3" hidden></div>
    <p id="scanStatus" class="small text-muted mt-2 mb-0" aria-live="polite">Camera permission is needed to scan. You can also enter a code below.</p>
    <div class="scan-divider"><span>OR ENTER CODE</span></div>
    <form method="get" action="{{ route('driver.scan') }}" class="row g-2">
      <div class="col-12 col-sm-4"><label class="form-label" for="scanType">Code type</label><select class="form-select" id="scanType" name="type"><option value="order" @selected($type === 'order')>Delivery order</option><option value="container" @selected($type === 'container')>Water container</option></select></div>
      <div class="col-12 col-sm-8"><label class="form-label" for="scanCode">QR code value</label><input class="form-control" id="scanCode" name="code" value="{{ $code }}" placeholder="Order token or container code" required></div>
      <div class="col-12"><button class="btn btn-soft w-100" type="submit">Look up code</button></div>
    </form>
  </section>

  <section class="driver-scan-result">
    @if($error)<div class="alert alert-danger border-0 rounded-4">{{ $error }}</div>
    @elseif($order)
      <article class="at-card card-body-pad scan-record">
        <div class="section-heading"><div><span class="small text-muted">DELIVERY ORDER</span><h2 class="h4 mb-0">{{ $order->order_number }}</h2></div><span class="pill {{ $order->status === 'delivery_failed' ? 'pill-warn' : 'pill-info' }}">{{ str($order->status)->replace('_',' ')->title() }}</span></div>
        <dl class="order-details"><div><dt>Customer</dt><dd>{{ $order->customer_name }}</dd></div><div><dt>Contact</dt><dd><a href="tel:{{ $order->phone }}">{{ $order->phone }}</a></dd></div><div><dt>Delivery address</dt><dd>{{ $order->delivery_address }}</dd></div><div><dt>Items</dt><dd>@foreach($order->items as $item){{ $item->quantity }} × {{ $item->product_name }}@if(!$loop->last), @endif @endforeach</dd></div></dl>
        @if($order->status === 'out_for_delivery' || $order->status === 'delivery_failed')
          <form method="post" action="{{ route('driver.scan.order') }}" class="driver-update-form" data-confirm="Update delivery status for {{ $order->order_number }}?" data-confirm-label="Update order">@csrf<input type="hidden" name="token" value="{{ $order->delivery_token }}">
            <label class="form-label" for="orderStatus">Update order status</label><select class="form-select" id="orderStatus" name="status" required>
              @if($order->status === 'out_for_delivery')<option value="delivered">Delivered</option><option value="delivery_failed">Could not deliver · send back to station</option>
              @else<option value="out_for_delivery">Retry delivery</option>@endif
            </select><button class="btn btn-primary w-100 mt-3" type="submit">Save order status</button>
          </form>
        @else<div class="notice mt-3">This order is {{ str($order->status)->replace('_',' ') }}. Station staff must prepare and dispatch it before delivery updates are available.</div>@endif
      </article>
    @elseif($container)
      <article class="at-card card-body-pad scan-record">
        <div class="section-heading"><div><span class="small text-muted">WATER CONTAINER</span><h2 class="h4 mb-0">{{ $container->code }}</h2></div><span class="pill pill-{{ $container->status_class }}">{{ $container->status_label }}</span></div>
        <dl class="order-details"><div><dt>Type</dt><dd>{{ $container->type }}</dd></div><div><dt>Capacity</dt><dd>{{ $container->capacity_liters ? $container->capacity_liters.' L' : 'Not set' }}</dd></div><div><dt>Condition</dt><dd>{{ ucfirst($container->condition) }}</dd></div></dl>
        @if(in_array($container->status, ['available','in_use'], true))
          <form method="post" action="{{ route('driver.scan.container') }}" class="driver-update-form" data-confirm="Update container {{ $container->code }} status?" data-confirm-label="Update container">@csrf<input type="hidden" name="code" value="{{ $container->code }}"><label class="form-label" for="containerStatus">Update container status</label><select class="form-select" id="containerStatus" name="status"><option value="in_use" @selected($container->status === 'in_use')>In use · delivered to customer</option><option value="available" @selected($container->status === 'available')>Available · returned to station</option></select><button class="btn btn-primary w-100 mt-3" type="submit">Save container status</button></form>
        @else<div class="notice mt-3">This container is {{ str($container->status)->replace('_',' ') }} and can only be updated by station staff.</div>@endif
      </article>
    @else
      <div class="at-card card-body-pad driver-scan-placeholder"><i class="bi bi-upc-scan"></i><h2 class="h5">Scan result appears here</h2><p class="text-muted mb-0">Once a QR code is read, the order or container details and permitted status options will appear here.</p></div>
    @endif
  </section>
</div>
@endsection
@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(() => {
  const button = document.getElementById('startQr');
  const reader = document.getElementById('qr-reader');
  const status = document.getElementById('scanStatus');
  if (!button || !reader) return;
  const openResult = decoded => {
    const parts = decoded.trim().split('|');
    if (parts.length >= 3 && parts[0] === 'AQ' && ['ORDER','CONTAINER'].includes(parts[1])) {
      const url = new URL(@json(route('driver.scan')), window.location.origin);
      url.searchParams.set('type', parts[1] === 'ORDER' ? 'order' : 'container');
      url.searchParams.set('code', parts.slice(2).join('|'));
      window.location.assign(url);
    } else {
      status.textContent = 'That QR code is not an AquaTrack order or container label. Enter its code manually if you have it.';
    }
  };
  button.addEventListener('click', async () => {
    if (!window.Html5Qrcode) { status.textContent = 'Camera scanner could not load. Enter the QR code value manually.'; return; }
    button.disabled = true; reader.hidden = false; status.textContent = 'Starting camera…';
    const scanner = new Html5Qrcode('qr-reader');
    try {
      await scanner.start({facingMode:'environment'}, {fps:10, qrbox:{width:230,height:230}}, async decoded => {
        status.textContent = 'QR code found. Opening record…';
        try { await scanner.stop(); } catch (_) {}
        openResult(decoded);
      });
      status.textContent = 'Camera is ready. Center the QR code in the frame.';
      button.textContent = 'Camera scanner active';
    } catch (_) {
      status.textContent = 'Camera unavailable or permission denied. Enter the QR code value manually.';
      reader.hidden = true; button.disabled = false;
    }
  });
})();
</script>
@endpush
