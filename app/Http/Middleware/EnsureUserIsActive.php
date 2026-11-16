<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && !$user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors(['login' => 'Your account has been deactivated.']);
        }

        $googleAuthenticated = $user
            && hash_equals(
                (string) $user->getAuthIdentifier(),
                (string) $request->session()->get('google_authenticated_user_id', '')
            );

        if ($user && $user->must_change_password
            && !$googleAuthenticated
            && !$request->routeIs('profile.*', 'logout')) {
            return redirect()->route('profile.show')->with('warning', 'Please change your temporary password before continuing.');
        }

        return $next($request);
    }
}
