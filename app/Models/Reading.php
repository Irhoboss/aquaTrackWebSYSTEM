<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reading extends Model
{
    protected $fillable = ['device_id', 'ph_value', 'temperature', 'raw_voltage_mv', 'sensor_status', 'status', 'measured_at', 'received_at'];
    protected $casts = ['ph_value' => 'float', 'temperature' => 'float', 'measured_at' => 'datetime', 'received_at' => 'datetime'];

    public const STATUS = [
        'low' => ['Low pH', 'warn'],
        'normal' => ['Within configured range', 'ok'],
        'high' => ['High pH', 'bad'],
        'invalid' => ['Invalid reading', 'muted'],
        'sensor_error' => ['Sensor error', 'bad'],
    ];

    public function device(): BelongsTo { return $this->belongsTo(Device::class); }

    public function getStatusLabelAttribute(): string { return self::STATUS[$this->status][0] ?? $this->status; }
    public function getStatusClassAttribute(): string { return self::STATUS[$this->status][1] ?? 'muted'; }
}
