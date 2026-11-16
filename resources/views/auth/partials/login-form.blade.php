<form method="post" action="{{ route('login.attempt') }}" class="d-grid gap-3">
  @csrf
  <div><label class="form-label" for="login">Username or email</label>
    <input id="login" name="login" value="{{ old('login') }}" class="form-control @error('login') is-invalid @enderror" autocomplete="username" required autofocus></div>
  <div><label class="form-label" for="password">Password</label>
    <input id="password" type="password" name="password" class="form-control" autocomplete="current-password" required></div>
  <div class="d-flex justify-content-between align-items-center">
    <div class="form-check"><input class="form-check-input" type="checkbox" name="remember" id="remember"><label class="form-check-label small" for="remember">Remember me</label></div>
    <a class="small fw-bold" href="{{ route('password.request') }}">Forgot password?</a>
  </div>
  <button class="btn btn-primary btn-lg" type="submit">Sign in</button>
  <div class="d-flex align-items-center gap-3 text-muted small"><span class="flex-grow-1 border-top"></span><span>or</span><span class="flex-grow-1 border-top"></span></div>
  <a class="btn btn-soft btn-lg d-flex align-items-center justify-content-center gap-2" href="{{ route('google.redirect') }}"><i class="bi bi-google" aria-hidden="true"></i>Continue with Google</a>
</form>
