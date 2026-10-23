@extends('layouts.app')
@section('title', 'Orders')
@section('subtitle', 'Confirm orders and prepare deliveries')
@section('content')
<section class="at-card card-body-pad">
  @if($orders->isEmpty())<div class="cart-empty"><span class="cart-empty-icon"><i class="bi bi-receipt"></i></span><h2 class="h5">No orders yet</h2><p class="text-muted">Customer checkouts will show here.</p></div>
  @else
    <div class="orders-manage-list">
      @foreach($orders as $order)
        @php $nextStatuses = match($order->status) { 'pending' => ['confirmed','cancelled'], 'confirmed' => ['preparing','out_for_delivery','cancelled'], 'preparing' => ['out_for_delivery','cancelled'], 'delivery_failed' => ['out_for_delivery','cancelled'], default => [] }; @endphp
        <article class="orders-manage-row">
          <div class="orders-manage-info"><span class="orders-manage-number">{{ $order->order_number }}</span><span class="pill {{ $order->status === 'cancelled' ? 'pill-bad' : ($order->status === 'delivered' ? 'pill-ok' : 'pill-info') }}">{{ str($order->status)->replace('_',' ')->title() }}</span><strong>{{ $order->customer_name }}</strong><span class="small text-muted">{{ $order->customer?->phone ?? $order->phone }} · {{ $order->items->sum('quantity') }} items</span><span class="small text-muted">{{ \Illuminate\Support\Str::limit($order->delivery_address, 90) }}</span></div>
          <strong class="orders-manage-total">₱{{ number_format($order->total, 2) }}</strong>
          @if($nextStatuses)
            <form class="orders-manage-actions" method="post" action="{{ route('operations.orders.status', $order) }}" @if(in_array('cancelled', $nextStatuses, true)) data-confirm="Cancel order {{ $order->order_number }}? Its reserved product stock will be returned." data-confirm-label="Cancel order" @endif>@csrf @method('PATCH')
              <label class="visually-hidden" for="order-next-{{ $order->id }}">Next status for {{ $order->order_number }}</label><select class="form-select form-select-sm" id="order-next-{{ $order->id }}" name="status" required><option value="">Update status…</option>@foreach($nextStatuses as $status)<option value="{{ $status }}">{{ str($status)->replace('_',' ')->title() }}</option>@endforeach</select><button class="btn btn-primary btn-sm" type="submit">Save</button>
            </form>
          @endif
          @if($order->status === 'out_for_delivery')<div class="orders-driver-code"><div class="order-driver-qr"><div class="manage-order-qr" data-order-code="AQ|ORDER|{{ $order->delivery_token }}"></div><span>Driver scan</span></div></div>@endif
        </article>
      @endforeach
    </div>
    <div class="mt-3">{{ $orders->links() }}</div>
  @endif
</section>
@endsection
@push('scripts')<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script><script>document.querySelectorAll('.manage-order-qr').forEach(el=>{if(window.QRCode)new QRCode(el,{text:el.dataset.orderCode,width:104,height:104,colorDark:'#14323a',colorLight:'#ffffff',correctLevel:QRCode.CorrectLevel.M})});</script>@endpush
