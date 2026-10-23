<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;

class AuthenticateDevice
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        $device = $token ? Device::where('token_hash', hash('sha256', $token))->first() : null;

        if (!$device || !$device->is_active) {
            return response()->json(['success' => false, 'message' => 'Unauthorized device.'], 401);
        }

        $device->forceFill(['last_seen_at' => now(), 'last_ip' => $request->ip()])->save();
        $request->attributes->set('device', $device);

        return $next($request);
    }
}
