@extends('layouts.app')
@section('title', 'Checkout')
@section('subtitle', 'Confirm your delivery details and place your order')
@section('content')
<div class="checkout-layout">
  <section class="at-card card-body-pad">
    <div class="section-heading"><div><h2 class="h5 mb-1">Delivery details</h2><p class="small text-muted mb-0">We’ll use these details to coordinate your order.</p></div><a class="small fw-bold" href="{{ route('cart.index') }}">Edit cart</a></div>
    <form method="post" action="{{ route('checkout.store') }}" class="row g-3 mt-1" data-confirm="Place this order for ₱{{ number_format($total, 2) }}?" data-confirm-label="Place order">
      @csrf
      <div class="col-md-6"><label class="form-label" for="customer_name">Full name</label><input class="form-control" id="customer_name" name="customer_name" value="{{ old('customer_name', $user->name) }}" maxlength="120" required></div>
      <div class="col-md-6"><label class="form-label" for="phone">Contact number</label><input class="form-control" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="30" autocomplete="tel" required></div>
      <div class="col-12"><label class="form-label" for="delivery_address">Delivery address</label><textarea class="form-control" id="delivery_address" name="delivery_address" rows="3" maxlength="1000" autocomplete="street-address" required>{{ old('delivery_address') }}</textarea></div>
      <div class="col-12"><label class="form-label" for="note">Order note <span class="text-muted fw-normal">(optional)</span></label><textarea class="form-control" id="note" name="note" rows="2" maxlength="1000" placeholder="Delivery instructions or other details">{{ old('note') }}</textarea></div>
      <div class="col-12"><button class="btn btn-primary btn-lg w-100" type="submit">Place order · ₱{{ number_format($total, 2) }}</button></div>
    </form>
  </section>
  <aside class="at-card card-body-pad cart-summary"><h2 class="h5">Your order</h2><div class="checkout-order-lines">@foreach($lines as $line)<div class="checkout-order-line"><span><strong>{{ $line['quantity'] }} ×</strong> {{ $line['product']->name }}</span><strong>₱{{ number_format($line['subtotal'], 2) }}</strong></div>@endforeach</div><hr><div class="summary-row summary-total"><span>Total</span><strong>₱{{ number_format($total, 2) }}</strong></div><p class="small text-muted mt-3 mb-0">The available stock is reserved when the order is placed. The team will confirm delivery details with you.</p></aside>
</div>
@endsection
