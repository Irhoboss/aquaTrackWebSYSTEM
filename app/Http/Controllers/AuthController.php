<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Audit;
use App\Support\Setting;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin() { return view('auth.login'); }

    public function login(Request $request)
    {
        $request->validate(['login' => 'required|string|max:255', 'password' => 'required|string']);

        $key = Str::lower($request->input('login')).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['login' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.']);
        }

        $field = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        if (!Auth::attempt([$field => $request->login, 'password' => $request->password], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            Audit::log('login.failed', null, ['login' => $request->login]);
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

        $home = match ($user->role) {
            'customer' => route('store.index'),
            'driver' => route('driver.dashboard'),
            'manager' => route('monitoring.index'),
            'staff' => route('inventory.index'),
            default => route('dashboard'),
        };
        return redirect()->intended($home);
    }

    public function logout(Request $request)
    {
        Audit::log('logout', $request->user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function showForgot() { return view('auth.forgot'); }

    public function sendReset(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        Password::sendResetLink($request->only('email'));
        // Same answer whether or not the email exists (prevents account enumeration).
        return back()->with('status', 'If that email is registered, a reset link has been sent. (In development, check storage/logs/laravel.log.)');
    }

    public function showReset(Request $request, string $token)
    {
        return view('auth.reset', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required', 'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min((int) Setting::get('password_min_length'))->letters()->numbers()],
        ]);

        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'must_change_password' => false])->setRememberToken(Str::random(60));
            $user->save();
            Audit::log('password.reset', $user, [], $user);
            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Password updated. You can sign in now.')
            : back()->withErrors(['email' => 'This reset link is invalid or has expired.']);
    }

    public function showRegister()
    {
        abort_unless(Setting::get('registration_enabled'), 404);
        return view('auth.register');
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
        ]);
        $user = User::create($data + ['role' => 'customer', 'is_active' => true]);
        Audit::log('user.registered', $user, [], $user);
        return redirect()->route('login')->with('status', 'Account created. An administrator must activate it before you can sign in.');
    }
}
