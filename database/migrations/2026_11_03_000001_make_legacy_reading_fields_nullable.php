<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('readings')) return;

        $legacyColumns = ['raw', 'voltage', 'ph'];
        if (!collect($legacyColumns)->every(fn (string $column) => Schema::hasColumn('readings', $column))) return;

        Schema::table('readings', function (Blueprint $table) {
            $table->unsignedSmallInteger('raw')->nullable()->change();
            $table->float('voltage')->nullable()->change();
            $table->float('ph')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Keep these columns nullable: newer readings legitimately have no legacy ADC fields.
    }
};
