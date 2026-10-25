<?php

namespace App\Models;

use App\Support\Fmt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    protected $fillable = ['checked_in_at', 'checked_out_at'];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function durationLabel(): string
    {
        $minutes = (int) $this->checked_in_at->diffInMinutes($this->checked_out_at ?? now());

        return intdiv($minutes, 60).'h '.($minutes % 60).'m';
    }

    public function isLate(): bool
    {
        return $this->checked_in_at
            ->copy()
            ->timezone(Fmt::tz())
            ->format('H:i:s') >= '08:15:00';
    }

    public function isEarlyOut(): bool
    {
        return $this->checked_out_at !== null
            && $this->checked_out_at
                ->copy()
                ->timezone(Fmt::tz())
                ->format('H:i:s') < '18:00:00';
    }
}
