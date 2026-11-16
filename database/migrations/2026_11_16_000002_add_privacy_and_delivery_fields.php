<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'terms_accepted_at')) $table->timestamp('terms_accepted_at')->nullable();
            if (!Schema::hasColumn('users', 'terms_version')) $table->string('terms_version', 20)->nullable();
            if (!Schema::hasColumn('users', 'privacy_acknowledged_at')) $table->timestamp('privacy_acknowledged_at')->nullable();
            if (!Schema::hasColumn('users', 'privacy_version')) $table->string('privacy_version', 20)->nullable();
            if (!Schema::hasColumn('users', 'age_confirmed_at')) $table->timestamp('age_confirmed_at')->nullable();
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'delivery_fee')) $table->decimal('delivery_fee', 10, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'delivery_fee')) $table->dropColumn('delivery_fee');
        });

        Schema::table('users', function (Blueprint $table) {
            $columns = ['terms_accepted_at', 'terms_version', 'privacy_acknowledged_at', 'privacy_version', 'age_confirmed_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) $table->dropColumn($column);
            }
        });
    }
};
