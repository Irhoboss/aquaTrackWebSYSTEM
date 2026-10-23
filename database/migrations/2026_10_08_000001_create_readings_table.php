<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('readings', function (Blueprint $t) {
            $t->id();
            $t->string('device_id', 40)->default('esp32-01');
            $t->unsignedSmallInteger('raw');      // 12-bit ADC value (0-4095)
            $t->float('voltage');                 // volts at pin 34
            $t->float('ph');                      // estimated pH
            $t->string('status', 10);             // ACIDIC | NORMAL | ALKALINE
            $t->timestamps();
            $t->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('readings');
    }
};
