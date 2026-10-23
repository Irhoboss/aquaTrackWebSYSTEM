<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\InventoryCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Initial administrator for local setup. Change this password after deployment.
        $password = env('AQUATRACK_ADMIN_PASSWORD', 'admin123');
        User::firstOrCreate(['username' => 'admin'], [
            'name' => 'Philzen Owner',
            'email' => env('AQUATRACK_ADMIN_EMAIL', 'admin@aquatrack.test'),
            'role' => 'admin',
            'is_active' => true,
            'must_change_password' => false,
            'password' => $password,
        ]);

        // 2) Restricted station staff and delivery driver accounts.
        User::firstOrCreate(['username' => 'staff'], [
            'name' => 'AquaTrack Staff',
            'email' => 'staff@aquatrack.test',
            'role' => 'staff',
            'is_active' => true,
            'must_change_password' => true,
            'password' => env('AQUATRACK_STAFF_PASSWORD', 'Staff!2026'),
        ]);
        User::firstOrCreate(['username' => 'driver'], [
            'name' => 'AquaTrack Driver',
            'email' => 'driver@aquatrack.test',
            'role' => 'driver',
            'is_active' => true,
            'must_change_password' => true,
            'password' => env('AQUATRACK_DRIVER_PASSWORD', 'Driver!2026'),
        ]);

        // 3) Starter inventory categories
        foreach (['Cleaning Supplies', 'Filters & Cartridges', 'Containers & Caps', 'Chemicals & Sanitizers', 'Packaging', 'Tools & Spare Parts'] as $name) {
            InventoryCategory::firstOrCreate(['name' => $name]);
        }

        // 4) First ESP32 device (token is shown ONCE here; you can regenerate it in Settings > Devices)
        if (!Device::where('device_id', 'AQUATRACK-ESP32-001')->exists()) {
            $device = Device::create(['device_id' => 'AQUATRACK-ESP32-001', 'name' => 'Main Station Sensor', 'location' => 'Refilling station']);
            $token = $device->issueToken();
            $this->command?->warn("ESP32 device token (copy it into firmware/aquatrack_esp32.ino now): {$token}");
        }

        $this->command?->info('Login -> admin: admin | staff: staff | driver: driver. Staff/driver passwords are set by AQUATRACK_STAFF_PASSWORD and AQUATRACK_DRIVER_PASSWORD (local defaults: Staff!2026 / Driver!2026).');
    }
}
