@extends('layouts.app')
@section('title', 'Order details')
@section('subtitle', $order->order_number)
@section('content')
<div class="checkout-layout">
  <section class="at-card card-body-pad"><div class="section-heading"><div><span class="small text-muted">ORDER STATUS</span><h2 class="h4 mb-0">{{ str($order->status)->replace('_', ' ')->title() }}</h2></div><span class="pill {{ $order->status === 'delivered' ? 'pill-ok' : ($order->status === 'cancelled' ? 'pill-bad' : 'pill-info') }}">{{ $order->order_number }}</span></div>
    <div class="table-responsive mt-3"><table class="table align-middle"><thead><tr><th>Product</th><th>Quantity</th><th class="text-end">Subtotal</th></tr></thead><tbody>@foreach($order->items as $item)<tr><td>{{ $item->product_name }}</td><td>{{ $item->quantity }}</td><td class="text-end">₱{{ number_format($item->subtotal, 2) }}</td></tr>@endforeach</tbody><tfoot><tr><th colspan="2">Total</th><th class="text-end">₱{{ number_format($order->total, 2) }}</th></tr></tfoot></table></div>
    <p class="small text-muted mb-0">Placed @dt($order->created_at)</p>
    @if($order->status === 'out_for_delivery')<div class="order-driver-qr"><div id="customerOrderQr" data-code="AQ|ORDER|{{ $order->delivery_token }}"></div><span>Show this QR to your delivery driver</span></div>@endif
  </section>
  <aside class="at-card card-body-pad"><h2 class="h5">Delivery details</h2><dl class="order-details"><div><dt>Name</dt><dd>{{ $order->customer_name }}</dd></div><div><dt>Contact</dt><dd>{{ $order->phone }}</dd></div><div><dt>Address</dt><dd>{{ $order->delivery_address }}</dd></div>@if($order->note)<div><dt>Note</dt><dd>{{ $order->note }}</dd></div>@endif</dl><a class="btn btn-soft" href="{{ route('support-chat.index') }}"><i class="bi bi-chat-dots me-1"></i>Contact the station</a></aside>
</div>
@endsection
@if($order->status === 'out_for_delivery')
@push('scripts')<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script><script>const qr=document.getElementById('customerOrderQr');if(qr&&window.QRCode)new QRCode(qr,{text:qr.dataset.code,width:156,height:156,colorDark:'#14323a',colorLight:'#ffffff',correctLevel:QRCode.CorrectLevel.M});</script>@endpush
@endif
