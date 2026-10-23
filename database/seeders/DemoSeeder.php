<?php

namespace Database\Seeders;

use App\Models\Container;
use App\Models\Device;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Reading;
use App\Models\Supplier;
use App\Models\User;
use App\Services\AlertService;
use App\Services\PhClassifier;
use App\Services\StockService;
use Illuminate\Database\Seeder;

/** Optional demo data:  php artisan db:seed --class=DemoSeeder   (delete the demo records before real use) */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);

        User::firstOrCreate(['username' => 'manager'], ['name' => 'Demo Manager', 'email' => 'manager@aquatrack.test', 'role' => 'manager', 'password' => 'Manager!2026', 'must_change_password' => true]);
        User::firstOrCreate(['username' => 'staff'], ['name' => 'Demo Staff', 'email' => 'staff@aquatrack.test', 'role' => 'staff', 'password' => 'Staff!2026', 'must_change_password' => true]);

        $sup = Supplier::firstOrCreate(['name' => 'Demo Water Supplies Co.'], ['contact_person' => 'Juan Dela Cruz', 'phone' => '09171234567', 'email' => 'sales@example.com', 'address' => 'Nasugbu, Batangas', 'supplied_items' => 'Filters, caps, sanitizer']);

        $items = [
            ['FLT-001', 'Sediment filter cartridge', 'Filters & Cartridges', 'pcs', 12, 5],
            ['CAP-001', 'Slim gallon caps', 'Containers & Caps', 'pcs', 150, 100],
            ['SAN-001', 'Food-grade sanitizer', 'Chemicals & Sanitizers', 'liters', 3, 5],
            ['PKG-001', 'Shrink seal bands', 'Packaging', 'rolls', 0, 2],
        ];
        foreach ($items as [$sku, $name, $cat, $unit, $qty, $min]) {
            $item = InventoryItem::firstOrCreate(['sku' => $sku], [
                'name' => $name, 'category_id' => InventoryCategory::where('name', $cat)->value('id'),
                'unit' => $unit, 'min_stock' => $min, 'supplier_id' => $sup->id, 'quantity' => 0, 'location' => 'Back storage',
            ]);
            if ($qty > 0 && !$item->movements()->exists()) {
                StockService::move($item, 'in', $qty, 'Opening balance (demo)', ['supplier_id' => $sup->id]);
            }
            AlertService::syncItem($item->fresh());
        }

        foreach ([['GAL-001', 'Slim gallon', 5, 'available'], ['GAL-002', 'Round gallon', 5, 'in_use'], ['GAL-003', 'Slim gallon', 5, 'in_use']] as $i => [$code, $type, $cap, $st]) {
            Container::firstOrCreate(['code' => $code], [
                'type' => $type, 'capacity_liters' => 18.9, 'date_acquired' => now()->subMonths(6)->toDateString(), 'status' => $st,
                'inspection_frequency_days' => 30, 'next_inspection_date' => now()->addDays($i === 2 ? -3 : 10 + $i)->toDateString(),
                'replacement_due_date' => now()->addMonths(6)->toDateString(),
            ]);
        }

        $device = Device::first();
        if ($device && !Reading::exists()) {
            $t = now()->subHours(24)->startOfMinute();
            for ($i = 0; $i < 288; $i++) { // every 5 minutes for 24h
                $ph = round(7.2 + sin($i / 20) * 0.5 + (mt_rand(-10, 10) / 100) + ($i > 250 && $i < 262 ? 1.6 : 0), 2);
                Reading::create([
                    'device_id' => $device->id, 'ph_value' => $ph, 'sensor_status' => 'ok',
                    'status' => PhClassifier::classify($ph), 'measured_at' => $t->copy()->addMinutes($i * 5), 'received_at' => $t->copy()->addMinutes($i * 5),
                ]);
            }
        }
    }
}
