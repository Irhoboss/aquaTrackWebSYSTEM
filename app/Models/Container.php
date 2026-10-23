<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Container extends Model
{
    protected $guarded = [];
    protected $casts = [
        'date_acquired' => 'date', 'last_inspection_date' => 'date', 'next_inspection_date' => 'date',
        'replacement_due_date' => 'date', 'capacity_liters' => 'float',
    ];

    public const STATUSES = [
        'available' => 'Available', 'in_use' => 'In Use', 'needs_inspection' => 'Needs Inspection',
        'under_maintenance' => 'Under Maintenance', 'for_replacement' => 'For Replacement', 'retired' => 'Retired',
    ];
    public const CONDITIONS = ['good' => 'Good', 'fair' => 'Fair', 'poor' => 'Poor', 'damaged' => 'Damaged'];

    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function inspections(): HasMany { return $this->hasMany(ContainerInspection::class)->orderByDesc('inspected_at')->orderByDesc('id'); }
    public function maintenanceRecords(): HasMany { return $this->hasMany(ContainerMaintenanceRecord::class)->orderByDesc('performed_at')->orderByDesc('id'); }

    public function getStatusLabelAttribute(): string { return self::STATUSES[$this->status] ?? $this->status; }
    public function getStatusClassAttribute(): string
    {
        return ['available' => 'ok', 'in_use' => 'info', 'needs_inspection' => 'warn', 'under_maintenance' => 'warn',
            'for_replacement' => 'bad', 'retired' => 'muted'][$this->status] ?? 'muted';
    }
    public function getIsInspectionOverdueAttribute(): bool
    {
        return $this->status !== 'retired' && $this->next_inspection_date && $this->next_inspection_date->lt(today());
    }
    public function getIsReplacementDueAttribute(): bool
    {
        return $this->status !== 'retired' && $this->replacement_due_date && $this->replacement_due_date->lte(today());
    }
}
