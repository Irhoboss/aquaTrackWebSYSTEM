<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    protected $fillable = ['inventory_item_id', 'change', 'quantity_after', 'type', 'note'];
    protected $casts = ['change' => 'integer', 'quantity_after' => 'integer'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
