<?php

namespace App\Services;

use App\Support\Setting;

class PhClassifier
{
    /** Returns: low | normal | high | invalid | sensor_error. A missing value is NEVER treated as safe. */
    public static function classify(?float $ph, string $sensorStatus = 'ok'): string
    {
        if ($sensorStatus !== 'ok') return 'sensor_error';
        if ($ph === null || !is_finite($ph)) return 'invalid';
        if ($ph < (float) Setting::get('ph_valid_min') || $ph > (float) Setting::get('ph_valid_max')) return 'invalid';
        if ($ph < (float) Setting::get('ph_min')) return 'low';
        if ($ph > (float) Setting::get('ph_max')) return 'high';
        return 'normal';
    }
}
