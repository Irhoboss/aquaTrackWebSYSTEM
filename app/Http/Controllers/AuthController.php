<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Audit;
use App\Support\EmailOtp;
use App\Support\Setting;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class AuthController extends Controller
{
    public function showLogin() { return view('auth.login', ['authMode' => 'login']); }

    public function login(Request $request)
    {
        $request->validate(['login' => 'required|string|max:255', 'password' => 'required|string']);

        $key = 'login:'.hash('sha256', Str::lower($request->input('login')).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['login' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.']);
        }

        $field = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        if (!Auth::attempt([$field => $request->login, 'password' => $request->password], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            Audit::log('login.failed', null, [
                'login_type' => filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username',
            ]);
            throw ValidationException::withMessages(['login' => 'Incorrect username/email or password.']);
        }

        $user = Auth::user();
        if (!$user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw ValidationException::withMessages(['login' => 'This account is deactivated or still waiting for approval.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();
        Audit::log('login', $user);

        return redirect()->intended($this->homeFor($user))->with('signed_in', true);
    }

    public function logout(Request $request)
    {
        Audit::log('logout', $request->user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('signed_out', true);
    }

    public function showForgot() { return view('auth.forgot'); }

    public function sendReset(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:255']);
        $email = Str::lower($data['email']);
        $user = User::where('email', $email)->first();
        if ($user) {
            EmailOtp::issue($email, EmailOtp::PASSWORD_RESET, $user->id);
        }

        return redirect()->route('password.otp.form', ['email' => $email])
            ->with('status', 'If that email is registered, a verification code has been sent.');
    }

    public function showResetOtp(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:255']);
        return view('auth.reset', ['email' => Str::lower($data['email'])]);
    }

    public function verifyPasswordReset(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|max:255',
            'code' => 'required|digits:6',
            'password' => ['required', 'confirmed', PasswordRule::min((int) Setting::get('password_min_length'))->letters()->numbers()],
        ]);
        $email = Str::lower($data['email']);
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'code' => 'That verification code is invalid or expired. Request a new code and try again.',
            ]);
        }

        EmailOtp::verify($email, EmailOtp::PASSWORD_RESET, $data['code'], $user->id);
        $user->forceFill(['password' => $data['password'], 'must_change_password' => false])
            ->setRememberToken(Str::random(60));
        $user->save();
        Audit::log('password.reset', $user);
        event(new PasswordReset($user));

        return redirect()->route('login')->with('status', 'Password updated. You can sign in now.');
    }

    public function showRegister()
    {
        abort_unless(Setting::get('registration_enabled'), 404);
        return view('auth.login', ['authMode' => 'register']);
    }

    public function register(Request $request)
    {
        abort_unless(Setting::get('registration_enabled'), 404);
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'username' => ['required', 'alpha_dash', 'max:40', 'unique:users,username'],
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => ['nullable', 'regex:/^[0-9+()\-\s]{7,20}$/'],
            'password' => ['required', 'confirmed', PasswordRule::min((int) Setting::get('password_min_length'))->letters()->numbers()],
            'terms_accepted' => 'accepted',
            'privacy_acknowledged' => 'accepted',
            'age_confirmed' => 'accepted',
        ]);
        $email = Str::lower($data['email']);
        EmailOtp::issue($email, EmailOtp::REGISTRATION, null, [
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $email,
            'phone' => $data['phone'] ?? null,
            'password_hash' => Hash::make($data['password']),
            'terms_accepted' => true,
            'privacy_acknowledged' => true,
            'age_confirmed' => true,
        ]);

        return redirect()->route('register.verify', ['email' => $email])
            ->with('status', 'A verification code has been sent to your email.');
    }

    public function showRegistrationVerification(Request $request)
    {
        abort_unless(Setting::get('registration_enabled'), 404);
        $data = $request->validate(['email' => 'required|email|max:255']);
        return view('auth.verify-registration', ['email' => Str::lower($data['email'])]);
    }

    public function verifyRegistration(Request $request)
    {
        abort_unless(Setting::get('registration_enabled'), 404);
        $data = $request->validate([
            'email' => 'required|email|max:255',
            'code' => 'required|digits:6',
        ]);
        $email = Str::lower($data['email']);
        $payload = EmailOtp::verify($email, EmailOtp::REGISTRATION, $data['code']);

        $registration = validator($payload, [
            'name' => 'required|string|max:120',
            'username' => 'required|alpha_dash|max:40',
            'email' => 'required|email|max:255',
            'phone' => ['nullable', 'regex:/^[0-9+()\-\s]{7,20}$/'],
            'password_hash' => 'required|string|min:50',
            'terms_accepted' => 'accepted',
            'privacy_acknowledged' => 'accepted',
            'age_confirmed' => 'accepted',
        ])->validate();

        if (Str::lower($registration['email']) !== $email) {
            throw ValidationException::withMessages(['code' => 'That verification code is invalid or expired.']);
        }
        if (User::where('email', $email)->exists()) {
            return redirect()->route('login')->with('error', 'An account already uses this email. Sign in or reset its password.');
        }
        if (User::where('username', $registration['username'])->exists()) {
            return redirect()->route('register')->withErrors(['username' => 'That username was just taken. Please choose another one and register again.']);
        }

        $user = User::create([
            'name' => $registration['name'],
            'username' => $registration['username'],
            'email' => $email,
            'phone' => $registration['phone'] ?? null,
            'role' => 'customer',
            'is_active' => false,
            'password' => $registration['password_hash'],
        ]);
        $user->forceFill([
            'terms_accepted_at' => now(),
            'privacy_acknowledged_at' => now(),
            'age_confirmed_at' => now(),
            'terms_version' => '1.0',
            'privacy_version' => '1.0',
        ])->save();
        $user->forceFill(['email_verified_at' => now()])->save();
        Audit::log('user.registered', $user, [], $user);
        return redirect()->route('login')->with('status', 'Email verified. Your account is waiting for admin approval before you can sign in.');
    }

    public function resendRegistrationVerification(Request $request)
    {
        abort_unless(Setting::get('registration_enabled'), 404);
        $data = $request->validate(['email' => 'required|email|max:255']);
        EmailOtp::resend(Str::lower($data['email']), EmailOtp::REGISTRATION);

        return redirect()->route('register.verify', ['email' => Str::lower($data['email'])])
            ->with('status', 'A new verification code has been sent.');
    }

    public function redirectToGoogle()
    {
        if (!config('services.google.client_id') || !config('services.google.client_secret')) {
            return redirect()->route('login')->with('error', 'Google sign-in is not configured. Add the Google OAuth credentials to the server environment.');
        }

        return Socialite::driver('google')->scopes(['openid', 'email', 'profile'])->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        if ($request->query('error')) {
            return redirect()->route('login')->with('error', 'Google sign-in was cancelled.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $exception) {
            Log::warning('Google sign-in state could not be verified.', [
                'host' => $request->getHost(),
                'ip' => $request->ip(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Your sign-in session expired or its cookie was unavailable. Please start Google sign-in again in the same browser.');
        }
        $email = Str::lower((string) $googleUser->getEmail());
        $verified = filter_var(
            $googleUser->user['email_verified'] ?? $googleUser->user['verified_email'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$verified) {
            return redirect()->route('login')->withErrors(['login' => 'Google did not provide a verified email address.']);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return redirect()->route('register')->with('error', 'Create and verify an AquaTrack account with this email before using Google sign-in.');
        }
        if (!$user->is_active) {
            return redirect()->route('login')->withErrors(['login' => 'This account is deactivated or still waiting for approval.']);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('google_authenticated_user_id', $user->getKey());
        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();
        Audit::log('login.google', $user);

        return redirect()->intended($this->homeFor($user))->with('signed_in', true);
    }

    private function homeFor(User $user): string
    {
        return match ($user->role) {
            'customer' => route('store.index'),
            'driver' => route('driver.dashboard'),
            'manager' => route('monitoring.index'),
            'staff' => route('inventory.index'),
            default => route('dashboard'),
        };
    }
}
