<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContainerMaintenanceRecord extends Model
{
    protected $guarded = [];
    protected $casts = ['performed_at' => 'date', 'cost' => 'float'];

    public function container(): BelongsTo { return $this->belongsTo(Container::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
