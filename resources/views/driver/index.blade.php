@extends('layouts.app')
@section('title', 'Driver home')
@section('subtitle', 'Your delivery and container scan workspace')
@section('content')
<section class="driver-welcome at-card"><div><span class="store-kicker"><i class="bi bi-truck"></i> DELIVERY WORKSPACE</span><h2>Ready for today’s deliveries?</h2><p>Scan an order or container QR code to update its status.</p></div><a class="btn btn-primary" href="{{ route('driver.scan') }}"><i class="bi bi-qr-code-scan me-1"></i>Open QR scanner</a></section>
<div class="row g-3 mt-1">
  <div class="col-md-6"><section class="at-card card-body-pad driver-list-card"><div class="section-heading mb-2"><h2 class="h5 mb-0">Orders to deliver</h2><span class="pill pill-info">{{ $orders->count() }}</span></div>
    @forelse($orders as $order)<a class="driver-list-row" href="{{ route('driver.scan', ['type'=>'order','code'=>$order->delivery_token]) }}"><span class="driver-list-icon"><i class="bi bi-box-seam"></i></span><span class="driver-list-copy"><strong>{{ $order->order_number }}</strong><small>{{ $order->customer_name }} · {{ str($order->status)->replace('_',' ')->title() }}</small></span><i class="bi bi-chevron-right"></i></a>@empty<div class="empty py-4"><i class="bi bi-check2-circle"></i>No deliveries are waiting.</div>@endforelse
  </section></div>
  <div class="col-md-6"><section class="at-card card-body-pad driver-list-card"><div class="section-heading mb-2"><h2 class="h5 mb-0">Containers</h2><span class="pill pill-muted">{{ $containers->count() }}</span></div>
    @forelse($containers as $container)<a class="driver-list-row" href="{{ route('driver.scan', ['type'=>'container','code'=>$container->code]) }}"><span class="driver-list-icon"><i class="bi bi-droplet-half"></i></span><span class="driver-list-copy"><strong>{{ $container->code }}</strong><small>{{ $container->type }} · {{ $container->status_label }}</small></span><i class="bi bi-chevron-right"></i></a>@empty<div class="empty py-4"><i class="bi bi-bucket"></i>No available containers.</div>@endforelse
  </section></div>
</div>
@endsection
