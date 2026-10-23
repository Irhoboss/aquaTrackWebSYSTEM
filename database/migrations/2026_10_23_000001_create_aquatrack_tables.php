<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('devices')) Schema::create('devices', function (Blueprint $t) {
            $t->id();
            $t->string('device_id', 64)->unique();          // e.g. AQUATRACK-ESP32-001
            $t->string('name');
            $t->string('location')->nullable();
            $t->string('token_hash', 64)->nullable()->unique(); // sha256 of the device token (token itself is never stored)
            $t->boolean('is_active')->default(true);
            $t->timestamp('last_seen_at')->nullable();
            $t->string('last_ip', 45)->nullable();
            $t->string('firmware', 32)->nullable();
            $t->timestamps();
        });

        if (!Schema::hasTable('readings')) Schema::create('readings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('device_id')->constrained()->cascadeOnDelete();
            $t->decimal('ph_value', 5, 2)->nullable();
            $t->decimal('temperature', 5, 2)->nullable();
            $t->unsignedSmallInteger('raw_voltage_mv')->nullable();
            $t->string('sensor_status', 20)->default('ok');  // ok | disconnected | error
            $t->string('status', 20)->index();               // low | normal | high | invalid | sensor_error
            $t->dateTime('measured_at');
            $t->dateTime('received_at');
            $t->timestamps();
            $t->unique(['device_id', 'measured_at']);        // prevents duplicate uploads
            $t->index('measured_at');
        });

        Schema::create('suppliers', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->string('contact_person')->nullable();
            $t->string('phone', 30)->nullable();
            $t->string('email')->nullable();
            $t->string('address')->nullable();
            $t->text('supplied_items')->nullable();
            $t->string('status', 20)->default('active');
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('inventory_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->timestamps();
        });

        if (!Schema::hasTable('inventory_items')) Schema::create('inventory_items', function (Blueprint $t) {
            $t->id();
            $t->string('sku', 50)->unique();
            $t->string('name');
            $t->foreignId('category_id')->nullable()->constrained('inventory_categories')->nullOnDelete();
            $t->text('description')->nullable();
            $t->string('unit', 30)->default('pcs');
            $t->decimal('quantity', 12, 2)->default(0);
            $t->decimal('min_stock', 12, 2)->default(0);
            $t->decimal('max_stock', 12, 2)->nullable();
            $t->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $t->decimal('unit_cost', 12, 2)->nullable();
            $t->string('location')->nullable();
            $t->string('status', 20)->default('active');     // active | archived
            $t->timestamps();
            $t->index('name');
        });

        Schema::create('stock_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('inventory_item_id')->constrained()->restrictOnDelete(); // ledger is never lost
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $t->string('type', 10);                          // in | out | adjust
            $t->decimal('quantity_change', 12, 2);           // signed
            $t->decimal('quantity_before', 12, 2);
            $t->decimal('quantity_after', 12, 2);
            $t->decimal('unit_cost', 12, 2)->nullable();
            $t->string('reason');
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index('created_at');
        });

        Schema::create('containers', function (Blueprint $t) {
            $t->id();
            $t->string('code', 50)->unique();
            $t->string('type', 60);
            $t->decimal('capacity_liters', 8, 2)->nullable();
            $t->date('date_acquired')->nullable();
            $t->string('status', 30)->default('available');
            $t->string('condition', 20)->default('good');
            $t->date('last_inspection_date')->nullable();
            $t->date('next_inspection_date')->nullable();
            $t->unsignedSmallInteger('inspection_frequency_days')->default(30);
            $t->date('replacement_due_date')->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('container_inspections', function (Blueprint $t) {
            $t->id();
            $t->foreignId('container_id')->constrained()->restrictOnDelete();
            $t->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $t->date('inspected_at');
            $t->string('result', 20);                        // pass | needs_attention | fail
            $t->string('condition', 20);
            $t->text('findings')->nullable();
            $t->text('corrective_action')->nullable();
            $t->date('next_inspection_date')->nullable();
            $t->timestamps();
        });

        Schema::create('container_maintenance_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('container_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('type', 20);                          // maintenance | repair | replacement
            $t->date('performed_at');
            $t->text('description')->nullable();
            $t->decimal('cost', 12, 2)->nullable();
            $t->timestamps();
        });

        Schema::create('alerts', function (Blueprint $t) {
            $t->id();
            $t->string('type', 30)->index();
            $t->string('severity', 10);                      // info | warning | critical
            $t->string('title');
            $t->text('description')->nullable();
            $t->string('dedupe_key')->index();
            $t->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $t->string('related_type', 30)->nullable();      // item | container | device
            $t->unsignedBigInteger('related_id')->nullable();
            $t->string('related_label')->nullable();
            $t->string('status', 20)->default('new')->index(); // new | acknowledged | resolved
            $t->unsignedInteger('occurrences')->default(1);
            $t->timestamp('last_seen_at')->nullable();
            $t->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('acknowledged_at')->nullable();
            $t->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('resolved_at')->nullable();
            $t->text('resolution_notes')->nullable();
            $t->timestamps();
        });

        Schema::create('user_notifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('alert_id')->nullable()->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->text('body')->nullable();
            $t->string('url')->nullable();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'read_at']);
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action', 60)->index();
            $t->string('subject_type', 40)->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->string('subject_label')->nullable();
            $t->json('details')->nullable();                 // never contains passwords or tokens
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('system_settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['system_settings', 'audit_logs', 'user_notifications', 'alerts', 'container_maintenance_records',
            'container_inspections', 'containers', 'stock_movements', 'inventory_items', 'inventory_categories',
            'suppliers', 'readings', 'devices'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
