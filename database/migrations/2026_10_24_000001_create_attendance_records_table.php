<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->dateTime('checked_in_at');
            $table->dateTime('checked_out_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'checked_in_at']);
            $table->index('checked_out_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
