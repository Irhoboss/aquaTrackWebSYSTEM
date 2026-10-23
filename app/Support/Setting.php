<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class Setting
{
    private static function all(): array
    {
        return Cache::rememberForever('aq_settings', fn () => SystemSetting::pluck('value', 'key')->all());
    }

    public static function get(string $key, $default = null)
    {
        $all = self::all();
        if (array_key_exists($key, $all) && $all[$key] !== null) {
            return $all[$key];
        }
        return config("aquatrack.defaults.$key", $default);
    }

    public static function set(string $key, $value): void
    {
        SystemSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('aq_settings');
    }
}
