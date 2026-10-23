<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    protected $guarded = [];
    protected $casts = ['quantity' => 'float', 'min_stock' => 'float', 'max_stock' => 'float', 'unit_cost' => 'float'];

    public function category(): BelongsTo { return $this->belongsTo(InventoryCategory::class, 'category_id'); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function movements(): HasMany { return $this->hasMany(StockMovement::class); }

    public function getStockStateAttribute(): string
    {
        if ($this->quantity <= 0) return 'out';
        if ($this->quantity <= $this->min_stock) return 'low';
        return 'ok';
    }

    public function getStockLabelAttribute(): string { return ['out' => 'Out of stock', 'low' => 'Low stock', 'ok' => 'In stock'][$this->stock_state]; }
    public function getStockClassAttribute(): string { return ['out' => 'bad', 'low' => 'warn', 'ok' => 'ok'][$this->stock_state]; }
}
