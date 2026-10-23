<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $guarded = [];
    protected $casts = ['quantity_change' => 'float', 'quantity_before' => 'float', 'quantity_after' => 'float', 'unit_cost' => 'float'];

    public function item(): BelongsTo { return $this->belongsTo(InventoryItem::class, 'inventory_item_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
}
