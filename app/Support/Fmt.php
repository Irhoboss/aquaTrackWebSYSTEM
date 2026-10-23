<?php

namespace App\Support;

use Carbon\Carbon;

class Fmt
{
    public static function tz(): string { return config('aquatrack.display_tz', 'Asia/Manila'); }

    /** Date-time stored in UTC -> Philippine local display. */
    public static function dt($value, string $format = 'M d, Y h:i A'): string
    {
        return $value ? Carbon::parse($value)->timezone(self::tz())->format($format) : '—';
    }

    /** Date-only columns (no timezone conversion). */
    public static function d($value, string $format = 'M d, Y'): string
    {
        return $value ? Carbon::parse($value)->format($format) : '—';
    }

    public static function num($value, int $dec = 2): string
    {
        return $value === null ? '—' : number_format((float) $value, $dec);
    }

    public static function qty($value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ','), '0'), '.') ?: '0';
    }

    /** "2026-10-23" typed in local time -> start of that day in UTC. */
    public static function dayStart(?string $date): ?Carbon
    {
        return $date ? Carbon::parse($date, self::tz())->startOfDay()->utc() : null;
    }

    public static function dayEnd(?string $date): ?Carbon
    {
        return $date ? Carbon::parse($date, self::tz())->endOfDay()->utc() : null;
    }
}
