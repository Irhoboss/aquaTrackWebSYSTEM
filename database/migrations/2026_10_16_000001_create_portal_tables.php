<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            if (!Schema::hasColumn('users', 'role')) $t->string('role', 20)->default('customer')->index();
            if (!Schema::hasColumn('users', 'phone')) $t->string('phone', 30)->nullable();
            if (!Schema::hasColumn('users', 'address')) $t->text('address')->nullable();
            if (!Schema::hasColumn('users', 'profile_photo_path')) $t->string('profile_photo_path')->nullable();
        });
        Schema::create('products', function (Blueprint $t) {
            $t->id(); $t->string('name', 140); $t->text('description')->nullable();
            $t->decimal('price', 10, 2); $t->unsignedInteger('stock')->default(0);
            $t->string('unit', 30)->default('jug'); $t->string('image_path')->nullable();
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('orders', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('order_number', 32)->unique(); $t->string('delivery_token', 64)->unique();
            $t->string('status', 30)->default('pending')->index();
            $t->decimal('total', 10, 2); $t->string('customer_name'); $t->string('phone', 30);
            $t->text('delivery_address'); $t->text('note')->nullable();
            $t->timestamp('delivered_at')->nullable(); $t->foreignId('delivered_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('order_items', function (Blueprint $t) {
            $t->id(); $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $t->string('product_name'); $t->unsignedInteger('quantity');
            $t->decimal('unit_price', 10, 2); $t->decimal('subtotal', 10, 2); $t->timestamps();
        });
        Schema::create('support_messages', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $t->text('body'); $t->boolean('is_staff_reply')->default(false); $t->timestamps();
            $t->index(['user_id', 'created_at']);
        });
        Schema::create('feedback', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $t->unsignedTinyInteger('rating'); $t->text('comment')->nullable(); $t->timestamps();
            $t->unique(['user_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback'); Schema::dropIfExists('support_messages');
        Schema::dropIfExists('order_items'); Schema::dropIfExists('orders'); Schema::dropIfExists('products');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['role', 'phone', 'address', 'profile_photo_path']));
    }
};
