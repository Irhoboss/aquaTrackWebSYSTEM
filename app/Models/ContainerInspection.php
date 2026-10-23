<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContainerInspection extends Model
{
    protected $guarded = [];
    protected $casts = ['inspected_at' => 'date', 'next_inspection_date' => 'date'];

    public function container(): BelongsTo { return $this->belongsTo(Container::class); }
    public function inspector(): BelongsTo { return $this->belongsTo(User::class, 'inspected_by'); }
}
