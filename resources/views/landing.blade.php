<!doctype html>
<html lang="en" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="description" content="AquaTrack water delivery and quality monitoring, brought together in one simple service.">
  <title>{{ $brand['name'] ?? 'AquaTrack' }} · Water made simple</title>
  @if(!empty($brand['favicon']))<link rel="icon" href="{{ $brand['favicon'] }}">@endif
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="{{ asset('css/aquatrack.css') }}?v={{ filemtime(public_path('css/aquatrack.css')) }}" rel="stylesheet">
  <script nonce="{{ request()->attributes->get('csp_nonce') }}">(function(){try{var t=localStorage.getItem('aq-theme');if(!t)t=matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';document.documentElement.dataset.theme=t}catch(e){}})()</script>
</head>
<body class="landing-page">
  <header class="landing-nav-wrap">
    <nav class="landing-nav" aria-label="Main navigation">
      <a class="landing-brand" href="{{ route('home') }}">
        @if(!empty($brand['logo']))<img src="{{ $brand['logo'] }}" alt="">@else<span class="at-drop"><i class="bi bi-droplet-fill"></i></span>@endif
        <span><strong>{{ $brand['name'] ?? 'AquaTrack' }}</strong><small>WATER · QUALITY · CARE</small></span>
      </a>
      <div class="landing-links"><a href="#home">Home</a><a href="#about">About</a><a href="#products">Products</a><a href="#contact">Contact</a></div>
      <div class="landing-nav-actions">
        <a class="btn btn-soft landing-login" href="{{ route('login') }}"><i class="bi bi-person-circle"></i><span>Sign in</span></a>
        <a class="btn btn-primary" href="{{ \App\Support\Setting::get('registration_enabled') ? route('register') : route('login') }}"><span>{{ \App\Support\Setting::get('registration_enabled') ? 'Create account' : 'Get started' }}</span><i class="bi bi-arrow-up-right"></i></a>
      </div>
      <button class="btn btn-soft landing-menu-toggle" type="button" aria-label="Open navigation" aria-expanded="false" data-landing-menu-toggle><i class="bi bi-list"></i></button>
    </nav>
    <div class="landing-mobile-links" data-landing-menu>
      <a href="#home">Home</a><a href="#about">About us</a><a href="#products">Products</a><a href="#contact">Contact</a><a href="{{ route('login') }}">My account</a>
    </div>
  </header>

  <a class="skip-link" href="#main-content">Skip to main content</a>
  <main id="main-content" tabindex="-1">
    <section class="landing-hero" id="home">
      <div class="landing-hero-inner">
        <div class="landing-hero-copy">
          <span class="landing-eyebrow"><i class="bi bi-stars"></i> WATER REFILL SERVICE</span>
          <h1>Good water.<br><span>Clear peace of mind.</span></h1>
          <p>Order from your local station and follow delivery updates. AquaTrack also displays station monitoring readings; readings are not a certification of water safety.</p>
          <div class="landing-hero-actions">
            <a class="btn btn-primary btn-lg" href="{{ \App\Support\Setting::get('registration_enabled') ? route('register') : route('login') }}">Explore AquaTrack <i class="bi bi-arrow-right ms-1"></i></a>
            <a class="landing-text-link" href="#products">See available products <i class="bi bi-arrow-down-right"></i></a>
          </div>
          <div class="landing-trust"><span><i class="bi bi-shield-check"></i> Account protected</span><span><i class="bi bi-truck"></i> Delivery updates</span><span><i class="bi bi-droplet-half"></i> Quality monitoring</span></div>
        </div>
        <div class="landing-visual" aria-label="AquaTrack water service illustration">
          <div class="landing-orbit orbit-a"></div><div class="landing-orbit orbit-b"></div>
          <div class="landing-glass landing-water-card">
            <div class="landing-water-top"><span><i class="bi bi-droplet-fill"></i> AQUATRACK WATER</span><span class="landing-live"><i></i> STATION MONITORING</span></div>
            <div class="landing-bottle-scene"><div class="landing-bottle-cap"></div><div class="landing-bottle"><i class="bi bi-droplet-half"></i><b>{{ $brand['name'] ?? 'AquaTrack' }}</b><small>REFILL WATER</small><span>ORDER · TRACK · REFILL</span></div><div class="landing-water-ripple ripple-one"></div><div class="landing-water-ripple ripple-two"></div></div>
            <div class="landing-water-foot"><span><i class="bi bi-check-circle-fill"></i> Your water, on your schedule</span><i class="bi bi-arrow-up-right-circle"></i></div>
          </div>
          <div class="landing-float landing-float-quality"><span class="landing-float-icon"><i class="bi bi-activity"></i></span><span><b>Quality insights</b><small>Clear, useful readings</small></span></div>
          <div class="landing-float landing-float-delivery"><span class="landing-float-icon peach"><i class="bi bi-box-seam"></i></span><span><b>Easy reorders</b><small>Track each delivery</small></span></div>
          <span class="landing-bubble bubble-one"></span><span class="landing-bubble bubble-two"></span><span class="landing-bubble bubble-three"></span>
        </div>
      </div>
      <a href="#about" class="landing-scroll-cue" aria-label="Scroll to learn more"><i class="bi bi-chevron-double-down"></i></a>
    </section>

    <section class="landing-about landing-section" id="about">
      <div class="landing-about-art">
        <div class="landing-about-ring"><div class="landing-jug"><span></span><i class="bi bi-droplet-fill"></i><b>{{ $brand['name'] ?? 'AquaTrack' }}</b><small>WATER REFILL</small></div></div>
        <div class="landing-stat landing-stat-one"><strong>1</strong><span>simple place<br>to manage orders</span></div>
        <div class="landing-stat landing-stat-two"><i class="bi bi-graph-up-arrow"></i><span>Water quality<br>insights</span></div>
      </div>
      <div class="landing-about-copy">
        <span class="landing-eyebrow"><i class="bi bi-water"></i> A BETTER WAY TO STAY SUPPLIED</span>
        <h2>Water service that fits your day.</h2>
        <p>From choosing a refill to checking your order status, AquaTrack makes the everyday essentials easier to manage.</p>
        <div class="landing-benefit"><span><i class="bi bi-droplet"></i></span><div><h3>Quality in view</h3><p>Monitoring tools help the station keep water conditions visible and organized.</p></div></div>
        <div class="landing-benefit"><span><i class="bi bi-truck"></i></span><div><h3>Delivery without guesswork</h3><p>Follow order updates and contact customer support when you need a hand.</p></div></div>
        <a class="btn btn-soft landing-about-link" href="{{ route('login') }}">Get to know AquaTrack <i class="bi bi-arrow-right"></i></a>
      </div>
    </section>

    <section class="landing-products landing-section" id="products">
      <div class="landing-section-heading"><span class="landing-eyebrow"><i class="bi bi-droplet"></i> MADE FOR YOUR ROUTINE</span><h2>Choose your water.</h2><p>Explore products currently available from your local station.</p></div>
      <div class="landing-product-grid">
        @forelse($products as $product)
          <article class="landing-product landing-glass">
            <div class="landing-product-art">@if($product->image_path)<img src="{{ asset($product->image_path) }}" alt="{{ $product->name }}">@else<span class="landing-product-jug"><i class="bi bi-droplet-fill"></i></span>@endif</div>
            <div class="landing-product-info"><span class="landing-product-unit">{{ $product->unit }}</span><h3>{{ $product->name }}</h3><p>{{ \Illuminate\Support\Str::limit($product->description ?: 'Available from your local AquaTrack station.', 78) }}</p><div class="landing-product-bottom"><strong>₱{{ number_format($product->price, 2) }}</strong><a href="{{ route('login') }}" aria-label="Sign in to order {{ $product->name }}"><i class="bi bi-arrow-up-right"></i></a></div></div>
          </article>
        @empty
          <div class="landing-empty landing-glass"><span><i class="bi bi-box-seam"></i></span><h3>Products are being prepared</h3><p>Please check back soon or sign in to contact the station.</p><a class="btn btn-soft" href="{{ route('login') }}">Contact the station</a></div>
        @endforelse
      </div>
      <div class="landing-products-cta"><span>Ready for a refill?</span><a class="btn btn-primary" href="{{ \App\Support\Setting::get('registration_enabled') ? route('register') : route('login') }}">{{ \App\Support\Setting::get('registration_enabled') ? 'Create your account' : 'Sign in to order' }} <i class="bi bi-arrow-right"></i></a></div>
    </section>

    <section class="landing-contact landing-section" id="contact">
      <div class="landing-contact-card landing-glass"><div class="landing-contact-icon"><i class="bi bi-chat-heart"></i></div><div><span class="landing-eyebrow">HERE WHEN YOU NEED US</span><h2>Questions about your water order?</h2><p>Sign in to message the AquaTrack team and get help with your account or delivery.</p></div><a class="btn btn-primary" href="{{ route('login') }}">Contact customer care <i class="bi bi-arrow-right"></i></a></div>
    </section>
  </main>

  <footer class="landing-footer">
    <div class="landing-footer-main">
      <section class="landing-footer-brand">
        <a class="landing-brand" href="#home">
          @if(!empty($brand['logo']))<img src="{{ $brand['logo'] }}" alt="">@else<span class="at-drop" aria-hidden="true"><i class="bi bi-droplet-fill"></i></span>@endif
          <span><strong>{{ $brand['name'] ?? 'AquaTrack' }}</strong><small>WATER · QUALITY · CARE</small></span>
        </a>
        <p>Order refills, follow deliveries, and keep station water monitoring in view with AquaTrack.</p>
        <span class="landing-footer-tagline">ORDER. TRACK. REFILL.</span>
      </section>
      <nav class="landing-footer-column" aria-label="Explore AquaTrack">
        <h2>Navigate</h2>
        <a href="#home">Home</a><a href="#about">About AquaTrack</a><a href="#products">Products</a><a href="#contact">Contact</a>
      </nav>
      <nav class="landing-footer-column" aria-label="My account">
        <h2>My account</h2>
        <a href="{{ route('login') }}">Sign in</a>
        @if(\App\Support\Setting::get('registration_enabled'))
          <a href="{{ route('register') }}">Create account</a>
        @endif
        <a href="{{ route('login') }}">Order water</a>
      </nav>
      <section class="landing-footer-column landing-footer-contact">
        <h2>Get in touch</h2>
        @if($address = \App\Support\Setting::get('station_address'))
          <p><i class="bi bi-geo-alt-fill" aria-hidden="true"></i><span>{{ $address }}</span></p>
        @endif
        @if($contact = \App\Support\Setting::get('station_contact'))
          <p><i class="bi bi-telephone-fill" aria-hidden="true"></i><span>{{ $contact }}</span></p>
        @endif
        @if($email = \App\Support\Setting::get('privacy_contact_email'))
          <p><i class="bi bi-envelope-fill" aria-hidden="true"></i><a href="mailto:{{ $email }}">{{ $email }}</a></p>
        @endif
        @unless($address || $contact || $email)
          <p>Sign in to contact the AquaTrack team about your account or delivery.</p>
        @endunless
      </section>
    </div>
    <div class="landing-footer-bottom">
      <span>© {{ now()->year }} {{ $brand['name'] ?? 'AquaTrack' }}@if($operator = \App\Support\Setting::get('legal_operator_name')) · {{ $operator }}@endif. Monitoring readings do not certify water as potable.</span>
      <nav aria-label="Legal links">
        <a href="{{ route('legal.privacy') }}">Privacy</a><a href="{{ route('legal.terms') }}">Terms</a><a href="{{ route('legal.refunds') }}">Refunds</a><a href="{{ route('legal.cookies') }}">Cookies</a><button type="button" class="footer-link" data-cookie-reopen>Cookie settings</button>
      </nav>
    </div>
  </footer>
  @include('partials.cookie-consent')

  <script src="{{ asset('js/aquatrack.js') }}?v={{ filemtime(public_path('js/aquatrack.js')) }}"></script>
  <script nonce="{{ request()->attributes->get('csp_nonce') }}">
    document.querySelector('[data-landing-menu-toggle]')?.addEventListener('click', function () {
      const menu = document.querySelector('[data-landing-menu]');
      const open = this.getAttribute('aria-expanded') !== 'true';
      this.setAttribute('aria-expanded', String(open));
      this.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
      this.querySelector('i').className = `bi ${open ? 'bi-x-lg' : 'bi-list'}`;
      menu?.classList.toggle('is-open', open);
    });
  </script>
</body>
</html>
