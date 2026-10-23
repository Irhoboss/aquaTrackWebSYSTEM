@extends('layouts.app')
@section('title', 'Shop')
@section('subtitle', 'Browse products available for delivery')
@section('content')
<section class="store-hero at-card">
  <div><span class="store-kicker"><i class="bi bi-droplet-fill"></i> AQUATRACK STORE</span><h2>Good water, made easy.</h2><p>Choose your refill and contact the team if you need help with an order.</p><a class="btn btn-primary" href="{{ route('support-chat.index') }}"><i class="bi bi-chat-dots me-1"></i>Message the team</a></div>
  <div class="store-wave" aria-hidden="true"><span></span><span></span><span></span></div>
</section>
<div class="page-head mt-4"><div><h2 class="h5 mb-1">Available products</h2><p>Products and prices from your local station.</p></div>
  <form method="get" class="store-search"><i class="bi bi-search"></i><input name="q" value="{{ request('q') }}" placeholder="Search products" aria-label="Search products"><button class="btn btn-primary">Search</button></form>
</div>
<div class="store-grid mt-3">
  @forelse($products as $product)
    <article class="store-product at-card">
      <div class="store-art">@if($product->image_path)<img src="{{ asset($product->image_path) }}" alt="{{ $product->name }}">@else<i class="bi bi-droplet-half"></i>@endif</div>
      <div class="store-product-copy"><span class="store-kicker">{{ $product->unit }}</span><h3>{{ $product->name }}</h3><p>{{ $product->description ?: 'Fresh refill from AquaTrack.' }}</p><div class="store-product-foot"><strong>₱{{ number_format($product->price, 2) }} <small>/ {{ $product->unit }}</small></strong><span class="stock-live"><i></i>{{ $product->stock }} available</span></div>
        <form class="store-add-form" method="post" action="{{ route('cart.add', $product) }}">@csrf<label class="visually-hidden" for="qty-{{ $product->id }}">Quantity</label><input id="qty-{{ $product->id }}" class="form-control" type="number" name="quantity" min="1" max="{{ min($product->stock, 99) }}" value="1" aria-label="Quantity for {{ $product->name }}"><button class="btn btn-primary" type="submit"><i class="bi bi-cart-plus"></i><span>Add to cart</span></button></form>
      </div>
    </article>
  @empty
    <div class="at-card store-empty"><i class="bi bi-box-seam"></i><h3>{{ request('q') ? 'No matching products' : 'The shop is getting ready' }}</h3><p>{{ request('q') ? 'Try another product name.' : 'There are no products available right now. Check back soon or message the station.' }}</p></div>
  @endforelse
</div>
<div class="mt-3">{{ $products->links() }}</div>
@endsection
