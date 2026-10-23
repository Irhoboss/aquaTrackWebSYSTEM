@extends('layouts.app')
@section('title', 'My orders')
@section('subtitle', 'Track orders you placed with the station')
@section('content')
<section class="at-card card-body-pad">
  @if($orders->isEmpty())<div class="cart-empty"><span class="cart-empty-icon"><i class="bi bi-receipt"></i></span><h2 class="h5">No orders yet</h2><p class="text-muted">Your completed checkouts will appear here.</p><a class="btn btn-primary" href="{{ route('store.index') }}">Browse products</a></div>
  @else
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Order</th><th>Placed</th><th>Items</th><th>Status</th><th class="text-end">Total</th><th></th></tr></thead><tbody>
      @foreach($orders as $order)<tr><td class="fw-bold">{{ $order->order_number }}</td><td>@dt($order->created_at)</td><td>{{ $order->items->sum('quantity') }}</td><td><span class="pill {{ $order->status === 'delivered' ? 'pill-ok' : ($order->status === 'cancelled' ? 'pill-bad' : 'pill-info') }}">{{ str($order->status)->replace('_', ' ')->title() }}</span></td><td class="text-end">₱{{ number_format($order->total, 2) }}</td><td><a class="btn btn-soft btn-sm" href="{{ route('orders.show', $order) }}">Details</a></td></tr>@endforeach
    </tbody></table></div><div class="mt-3">{{ $orders->links() }}</div>
  @endif
</section>
@endsection
