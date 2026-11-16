<?php

namespace App\Support;

use App\Mail\VerificationCodeMail;
use App\Models\AuthOtp as AuthOtpRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmailOtp
{
    public const REGISTRATION = 'registration';
    public const PASSWORD_RESET = 'password_reset';
    public const PASSWORD_CHANGE = 'password_change';

    public static function issue(string $email, string $purpose, ?int $userId = null, array $payload = []): void
    {
        $email = Str::lower(trim($email));
        $emailKey = self::emailRateKey('send', $email, $purpose);
        $ipKey = 'aquatrack-otp:send-ip:'.hash('sha256', $purpose.'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($emailKey, 3) || RateLimiter::tooManyAttempts($ipKey, 12)) {
            throw ValidationException::withMessages([
                'email' => 'Too many verification codes were requested. Please wait before trying again.',
            ]);
        }

        RateLimiter::hit($emailKey, 600);
        RateLimiter::hit($ipKey, 3600);

        $code = (string) random_int(100000, 999999);
        DB::transaction(function () use ($email, $purpose, $userId, $payload, $code) {
            AuthOtpRecord::query()->where('created_at', '<', now()->subDay())->delete();
            $query = AuthOtpRecord::query()->where('email', $email)->where('purpose', $purpose)->whereNull('consumed_at');
            $userId === null ? $query->whereNull('user_id') : $query->where('user_id', $userId);
            $query->delete();

            AuthOtpRecord::create([
                'user_id' => $userId,
                'email' => $email,
                'purpose' => $purpose,
                'code_hash' => Hash::make($code),
                'payload' => $payload ?: null,
                'expires_at' => now()->addMinutes(10),
            ]);
        });

        $action = match ($purpose) {
            self::REGISTRATION => 'verify your email address',
            self::PASSWORD_RESET => 'reset your password',
            self::PASSWORD_CHANGE => 'change your password',
            default => 'verify your AquaTrack account',
        };

        Mail::to($email)->send(new VerificationCodeMail($code, $action));
    }

    public static function resend(string $email, string $purpose, ?int $userId = null): void
    {
        $email = Str::lower(trim($email));
        $query = AuthOtpRecord::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->where('created_at', '>=', now()->subDay())
            ->latest('id');
        $userId === null ? $query->whereNull('user_id') : $query->where('user_id', $userId);
        $challenge = $query->first();

        if (!$challenge) {
            throw ValidationException::withMessages([
                'email' => 'Request a new verification code before trying again.',
            ]);
        }

        self::issue($email, $purpose, $userId, $challenge->payload ?? []);
    }

    public static function verify(string $email, string $purpose, string $code, ?int $userId = null): array
    {
        $email = Str::lower(trim($email));
        $rateKey = self::emailRateKey('verify', $email, $purpose);

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            throw ValidationException::withMessages([
                'code' => 'Too many incorrect attempts. Request a new verification code.',
            ]);
        }

        $payload = DB::transaction(function () use ($email, $purpose, $code, $userId) {
            $query = AuthOtpRecord::query()
                ->where('email', $email)
                ->where('purpose', $purpose)
                ->whereNull('consumed_at')
                ->latest('id')
                ->lockForUpdate();
            $userId === null ? $query->whereNull('user_id') : $query->where('user_id', $userId);
            $challenge = $query->first();

            if (!$challenge || $challenge->expires_at->isPast() || $challenge->attempts >= 5) {
                return null;
            }

            if (!Hash::check($code, $challenge->code_hash)) {
                $challenge->increment('attempts');
                return null;
            }

            $payload = $challenge->payload ?? [];
            $challenge->delete();
            return $payload;
        });

        if ($payload === null) {
            RateLimiter::hit($rateKey, 600);
            throw ValidationException::withMessages([
                'code' => 'That verification code is invalid or expired. Request a new code and try again.',
            ]);
        }

        RateLimiter::clear($rateKey);
        return $payload;
    }

    private static function emailRateKey(string $action, string $email, string $purpose): string
    {
        return 'aquatrack-otp:'.$action.':'.hash('sha256', $purpose.'|'.$email);
    }
}
