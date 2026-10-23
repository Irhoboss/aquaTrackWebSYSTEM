<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Support\Audit;
use App\Support\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $keys = array_keys(config('aquatrack.defaults'));
        return view('settings.index', [
            's' => collect($keys)->mapWithKeys(fn ($k) => [$k => Setting::get($k)])->all(),
            'devices' => Device::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'station_name' => 'required|string|max:150',
            'station_address' => 'nullable|string|max:255',
            'station_contact' => 'nullable|string|max:120',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:1024',
            'favicon' => 'nullable|image|mimes:png,ico,jpg,jpeg,webp|max:512',
            'ph_min' => 'required|numeric|between:0,14',
            'ph_max' => 'required|numeric|between:0,14|gt:ph_min',
            'reporting_interval_seconds' => 'required|integer|between:10,3600',
            'offline_minutes' => 'required|integer|between:1,1440',
            'default_min_stock' => 'required|numeric|min:0',
            'default_inspection_days' => 'required|integer|between:1,3650',
            'default_replacement_months' => 'required|integer|between:1,240',
            'password_min_length' => 'required|integer|between:6,64',
        ]);

        foreach (['logo', 'favicon'] as $file) {
            if ($request->hasFile($file)) {
                if ($old = Setting::get($file)) Storage::disk('public')->delete($old);
                Setting::set($file, $request->file($file)->store('branding', 'public'));
            }
            unset($data[$file]);
        }
        foreach ($data as $k => $v) Setting::set($k, $v);
        foreach (['registration_enabled', 'notify_ph_alerts', 'notify_inventory_alerts', 'notify_container_alerts'] as $flag) {
            Setting::set($flag, $request->boolean($flag) ? 1 : 0);
        }

        Audit::log('settings.updated', null, collect($data)->only(['ph_min', 'ph_max', 'offline_minutes', 'reporting_interval_seconds'])->all());
        return back()->with('success', 'Settings saved.');
    }

    public function storeDevice(Request $request)
    {
        $data = $request->validate([
            'device_id' => ['required', 'regex:/^[A-Za-z0-9._-]+$/', 'max:64', 'unique:devices,device_id'],
            'name' => 'required|string|max:120',
            'location' => 'nullable|string|max:150',
        ]);
        $device = Device::create($data);
        $token = $device->issueToken();
        Audit::log('device.created', $device);
        return back()->with('success', 'Device registered.')->with('new_token', ['device' => $device->device_id, 'token' => $token]);
    }

    public function regenerate(Device $device)
    {
        $token = $device->issueToken(); // old token stops working immediately
        Audit::log('device.token_regenerated', $device);
        return back()->with('success', 'New token issued. The old token no longer works.')->with('new_token', ['device' => $device->device_id, 'token' => $token]);
    }

    public function toggle(Device $device)
    {
        $device->update(['is_active' => !$device->is_active]);
        Audit::log($device->is_active ? 'device.enabled' : 'device.revoked', $device);
        return back()->with('success', $device->is_active ? 'Device enabled.' : 'Device revoked. It can no longer upload readings.');
    }
}
