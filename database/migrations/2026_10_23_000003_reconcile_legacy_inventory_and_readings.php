<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->reconcileInventory();
        $this->reconcileReadings();
    }

    private function reconcileInventory(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_items', 'sku')) $table->string('sku', 50)->nullable()->unique();
            if (!Schema::hasColumn('inventory_items', 'category_id')) $table->foreignId('category_id')->nullable()->constrained('inventory_categories')->nullOnDelete();
            if (!Schema::hasColumn('inventory_items', 'description')) $table->text('description')->nullable();
            if (!Schema::hasColumn('inventory_items', 'min_stock')) $table->unsignedInteger('min_stock')->default(0);
            if (!Schema::hasColumn('inventory_items', 'max_stock')) $table->unsignedInteger('max_stock')->nullable();
            if (!Schema::hasColumn('inventory_items', 'supplier_id')) $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            if (!Schema::hasColumn('inventory_items', 'unit_cost')) $table->decimal('unit_cost', 12, 2)->nullable();
            if (!Schema::hasColumn('inventory_items', 'location')) $table->string('location', 150)->nullable();
            if (!Schema::hasColumn('inventory_items', 'status')) $table->string('status', 20)->default('active')->index();
        });

        if (Schema::hasColumn('inventory_items', 'sku')) {
            DB::table('inventory_items')->whereNull('sku')->orderBy('id')->each(function ($item) {
                DB::table('inventory_items')->where('id', $item->id)->update(['sku' => 'LEGACY-'.$item->id]);
            });
        }

        if (Schema::hasColumn('inventory_items', 'category') && Schema::hasColumn('inventory_items', 'category_id')) {
            DB::table('inventory_items')->select('category')->whereNotNull('category')->distinct()->get()->each(function ($row) {
                $name = trim($row->category);
                if ($name === '') return;
                $id = DB::table('inventory_categories')->where('name', $name)->value('id');
                if (!$id) $id = DB::table('inventory_categories')->insertGetId(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
                DB::table('inventory_items')->where('category', $row->category)->whereNull('category_id')->update(['category_id' => $id]);
            });
        }

        if (Schema::hasColumn('inventory_items', 'reorder_level') && Schema::hasColumn('inventory_items', 'min_stock')) {
            DB::statement('UPDATE inventory_items SET min_stock = reorder_level WHERE min_stock = 0 AND reorder_level > 0');
        }
    }

    private function reconcileReadings(): void
    {
        $legacy = Schema::hasColumn('readings', 'ph') && Schema::hasColumn('readings', 'raw');

        if ($legacy && !Schema::hasColumn('readings', 'legacy_device_id')) {
            Schema::table('readings', fn (Blueprint $table) => $table->renameColumn('device_id', 'legacy_device_id'));
        }

        Schema::table('readings', function (Blueprint $table) {
            if (!Schema::hasColumn('readings', 'device_id') || Schema::hasColumn('readings', 'legacy_device_id')) {
                if (!Schema::hasColumn('readings', 'device_id')) $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('readings', 'ph_value')) $table->decimal('ph_value', 5, 2)->nullable();
            if (!Schema::hasColumn('readings', 'temperature')) $table->decimal('temperature', 5, 2)->nullable();
            if (!Schema::hasColumn('readings', 'raw_voltage_mv')) $table->unsignedSmallInteger('raw_voltage_mv')->nullable();
            if (!Schema::hasColumn('readings', 'sensor_status')) $table->string('sensor_status', 20)->default('ok');
            if (!Schema::hasColumn('readings', 'measured_at')) $table->dateTime('measured_at')->nullable();
            if (!Schema::hasColumn('readings', 'received_at')) $table->dateTime('received_at')->nullable();
        });

        if ($legacy) {
            $deviceId = DB::table('devices')->where('device_id', 'AQUATRACK-ESP32-001')->value('id')
                ?? DB::table('devices')->value('id');

            if ($deviceId) {
                DB::table('readings')->whereNull('device_id')->update(['device_id' => $deviceId]);
            }

            DB::statement("UPDATE readings SET ph_value = ph WHERE ph_value IS NULL");
            DB::statement("UPDATE readings SET raw_voltage_mv = ROUND(voltage * 1000) WHERE raw_voltage_mv IS NULL AND voltage IS NOT NULL");
            DB::statement("UPDATE readings SET measured_at = COALESCE(created_at, NOW()) WHERE measured_at IS NULL");
            DB::statement("UPDATE readings SET received_at = COALESCE(created_at, NOW()) WHERE received_at IS NULL");
            DB::statement("UPDATE readings SET status = CASE WHEN ph IS NULL OR ph < 0 OR ph > 14 THEN 'invalid' WHEN ph < 6.5 THEN 'low' WHEN ph > 8.5 THEN 'high' ELSE 'normal' END");
        }

        if (Schema::hasColumn('readings', 'status')) {
            try {
                Schema::table('readings', fn (Blueprint $table) => $table->string('status', 20)->default('normal')->change());
            } catch (Throwable $e) {
                // Some older MySQL installations cannot change a column while retaining its index.
                // The app-facing status values still fit after migration on supported MySQL versions.
                throw $e;
            }
        }

        if (!Schema::hasColumn('readings', 'measured_at')) return;
        if (!collect(Schema::getIndexes('readings'))->contains(fn ($index) => in_array('measured_at', $index['columns'] ?? [], true))) {
            Schema::table('readings', fn (Blueprint $table) => $table->index('measured_at'));
        }
    }

    public function down(): void
    {
        // Keep the reconciled columns and migrated historical values in place on rollback.
    }
};
