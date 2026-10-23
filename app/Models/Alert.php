<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    protected $guarded = [];
    protected $casts = ['last_seen_at' => 'datetime', 'acknowledged_at' => 'datetime', 'resolved_at' => 'datetime'];

    public function device(): BelongsTo { return $this->belongsTo(Device::class); }
    public function acknowledger(): BelongsTo { return $this->belongsTo(User::class, 'acknowledged_by'); }
    public function resolver(): BelongsTo { return $this->belongsTo(User::class, 'resolved_by'); }

    public function scopeOpen(Builder $q): Builder { return $q->whereIn('status', ['new', 'acknowledged']); }

    /** Users only see alerts for the types their role is notified about. */
    public function scopeVisibleTo(Builder $q, User $user): Builder
    {
        $types = collect(config('aquatrack.alert_roles'))->filter(fn ($roles) => in_array($user->role, $roles))->keys()->all();
        return $q->whereIn('type', $types);
    }

    public function getTypeLabelAttribute(): string { return config('aquatrack.alert_types')[$this->type] ?? $this->type; }
    public function getSeverityClassAttribute(): string { return ['critical' => 'bad', 'warning' => 'warn', 'info' => 'info'][$this->severity] ?? 'muted'; }
    public function getStatusClassAttribute(): string { return ['new' => 'bad', 'acknowledged' => 'warn', 'resolved' => 'ok'][$this->status] ?? 'muted'; }
}
