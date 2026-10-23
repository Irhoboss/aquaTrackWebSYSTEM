<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('category', 60)->default('Supply');
            $table->string('unit', 24)->default('pcs');
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('reorder_level')->default(0);
            $table->string('container_code', 60)->nullable()->unique();
            $table->string('inspection_status', 24)->default('Not applicable');
            $table->date('next_inspection_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['category', 'quantity']);
        });

        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->integer('change');
            $table->unsignedInteger('quantity_after');
            $table->string('type', 20);
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('inventory_items');
    }
};
