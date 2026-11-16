<form method="post" action="{{ route('register.store') }}" class="d-grid gap-3">
  @csrf
  <div><label class="form-label" for="register_name">Full name</label><input id="register_name" name="name" value="{{ old('name') }}" class="form-control" autocomplete="name" required></div>
  <div class="row g-3">
    <div class="col-sm-6"><label class="form-label" for="register_username">Username</label><input id="register_username" name="username" value="{{ old('username') }}" class="form-control" autocomplete="username" required></div>
    <div class="col-sm-6"><label class="form-label" for="register_phone">Contact number <span class="text-muted fw-normal">(optional)</span></label><input id="register_phone" name="phone" value="{{ old('phone') }}" class="form-control" autocomplete="tel"></div>
  </div>
  <div><label class="form-label" for="register_email">Email</label><input id="register_email" type="email" name="email" value="{{ old('email') }}" class="form-control" autocomplete="email" required></div>
  <div><label class="form-label" for="register_password">Password</label><input id="register_password" type="password" name="password" class="form-control" autocomplete="new-password" required></div>
  <div><label class="form-label" for="register_password_confirmation">Confirm password</label><input id="register_password_confirmation" type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required></div>
  <div class="form-check"><input class="form-check-input" type="checkbox" name="terms_accepted" value="1" id="terms_accepted" required @checked(old('terms_accepted'))><label class="form-check-label" for="terms_accepted">I agree to the <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener">Terms of Service</a>.</label></div>
  <div class="form-check"><input class="form-check-input" type="checkbox" name="privacy_acknowledged" value="1" id="privacy_acknowledged" required @checked(old('privacy_acknowledged'))><label class="form-check-label" for="privacy_acknowledged">I have read the <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener">Privacy Policy</a>.</label></div>
  <div class="form-check"><input class="form-check-input" type="checkbox" name="age_confirmed" value="1" id="age_confirmed" required @checked(old('age_confirmed'))><label class="form-check-label" for="age_confirmed">I am at least 18, or a parent/legal guardian creating or supervising this account and consenting to its use.</label></div>
  <button class="btn btn-primary btn-lg" type="submit">Register</button>
</form>
