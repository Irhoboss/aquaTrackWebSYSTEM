@extends('layouts.app')
@section('title', 'Shopping cart')
@section('subtitle', 'Review your items before checkout')
@section('content')
@if($lines)
  <div class="checkout-layout">
    <section class="at-card card-body-pad">
      <div class="section-heading"><div><h2 class="h5 mb-1">Your items</h2><p class="text-muted small mb-0">Update quantities or remove an item before checkout.</p></div><span class="pill pill-info">{{ count($lines) }} {{ \Illuminate\Support\Str::plural('item', count($lines)) }}</span></div>
      <div class="cart-lines mt-3">
        @foreach($lines as $line)
          <article class="cart-line">
            <div class="cart-product-art"><i class="bi bi-droplet-half"></i></div>
            <div class="cart-product-copy"><strong>{{ $line['product']->name }}</strong><span>{{ $line['product']->unit }} · ₱{{ number_format($line['product']->price, 2) }} each</span><small>{{ $line['product']->stock }} in stock</small></div>
            <form class="cart-quantity" method="post" action="{{ route('cart.update', $line['product']) }}">@csrf @method('PATCH')<label for="cart-qty-{{ $line['product']->id }}">Qty</label><input id="cart-qty-{{ $line['product']->id }}" class="form-control" type="number" name="quantity" min="1" max="{{ min($line['product']->stock, 99) }}" value="{{ $line['quantity'] }}"><button class="btn btn-soft btn-sm" type="submit" aria-label="Update quantity"><i class="bi bi-arrow-repeat"></i></button></form>
            <strong class="cart-line-total">₱{{ number_format($line['subtotal'], 2) }}</strong>
            <form method="post" action="{{ route('cart.remove', $line['product']) }}" data-confirm="Remove {{ $line['product']->name }} from your cart?" data-confirm-label="Remove item">@csrf @method('DELETE')<button class="btn btn-soft btn-sm text-danger" type="submit" aria-label="Remove {{ $line['product']->name }}"><i class="bi bi-trash"></i></button></form>
          </article>
        @endforeach
      </div>
      <a class="btn btn-soft mt-3" href="{{ route('store.index') }}"><i class="bi bi-arrow-left"></i> Continue shopping</a>
    </section>
    <aside class="at-card card-body-pad cart-summary"><h2 class="h5">Order summary</h2><div class="summary-row"><span>Subtotal</span><strong>₱{{ number_format($total, 2) }}</strong></div><div class="summary-row"><span>Delivery</span><span>Arranged by station</span></div><hr><div class="summary-row summary-total"><span>Total</span><strong>₱{{ number_format($total, 2) }}</strong></div><a class="btn btn-primary w-100 mt-3" href="{{ route('checkout.index') }}">Continue to checkout <i class="bi bi-arrow-right"></i></a><p class="small text-muted mt-3 mb-0"><i class="bi bi-shield-check me-1"></i>Stock is confirmed when you place the order.</p></aside>
  </div>
@else
  <section class="at-card card-body-pad cart-empty"><span class="cart-empty-icon"><i class="bi bi-cart3"></i></span><h2 class="h4">Your cart is empty</h2><p class="text-muted">Browse the shop and add the products you need.</p><a class="btn btn-primary" href="{{ route('store.index') }}">Browse products</a></section>
@endif
@endsection
