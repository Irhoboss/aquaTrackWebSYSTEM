<?php

namespace App\Providers;

use App\Support\Setting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // One reusable authorization system: role => abilities in config/permissions.php
        Gate::before(function ($user, $ability) {
            $perms = config("permissions.roles.{$user->role}", []);
            return (in_array('*', $perms, true) || in_array($ability, $perms, true)) ? true : false;
        });

        RateLimiter::for('device-readings', function (Request $request) {
            $device = $request->attributes->get('device');
            return Limit::perMinute(30)->by($device?->id ?: $request->ip());
        });

        // @dt($utcDateTime)  -> Philippine local time;  @d($dateColumn) -> plain date
        Blade::directive('dt', fn ($e) => "<?php echo e(\\App\\Support\\Fmt::dt($e)); ?>");
        Blade::directive('d', fn ($e) => "<?php echo e(\\App\\Support\\Fmt::d($e)); ?>");
        Blade::directive('qty', fn ($e) => "<?php echo e(\\App\\Support\\Fmt::qty($e)); ?>");

        View::composer('*', function ($view) {
            static $brand = null;
            if ($brand === null) {
                try {
                    $url = fn ($key) => Setting::get($key) ? Storage::disk('public')->url(Setting::get($key)) : null;
                    $brand = ['name' => Setting::get('station_name'), 'logo' => $url('logo'), 'favicon' => $url('favicon')];
                } catch (\Throwable $e) {
                    $brand = ['name' => 'AquaTrack', 'logo' => null, 'favicon' => null];
                }
            }
            $view->with('brand', $brand);
        });

        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            if ($user) {
                $view->with('unreadCount', $user->inbox()->whereNull('read_at')->count())
                     ->with('navNotifications', $user->inbox()->latest()->limit(6)->get());
            }
        });
    }
}
