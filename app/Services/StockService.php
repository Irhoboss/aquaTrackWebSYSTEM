<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    /**
     * $type: in | out | adjust.  For "adjust", $qty is the NEW counted quantity; otherwise it is the amount moved.
     * Quantity change and ledger row are written in one DB transaction with a row lock.
     */
    public static function move(InventoryItem $item, string $type, float $qty, string $reason, array $opt = []): StockMovement
    {
        $movement = DB::transaction(function () use ($item, $type, $qty, $reason, $opt) {
            $locked = InventoryItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            $before = (float) $locked->quantity;
            $change = match ($type) {
                'in' => abs($qty),
                'out' => -abs($qty),
                default => $qty - $before,
            };
            $after = round($before + $change, 2);

            if ($after < 0 && !($opt['allow_negative'] ?? false)) {
                throw ValidationException::withMessages(['quantity' => 'Not enough stock. Available: '.\App\Support\Fmt::qty($before)." {$locked->unit}."]);
            }
            if ($type === 'adjust' && abs($change) < 0.005) {
                throw ValidationException::withMessages(['quantity' => 'The counted quantity equals the current quantity. Nothing to adjust.']);
            }

            $locked->update(['quantity' => $after]);

            return StockMovement::create([
                'inventory_item_id' => $locked->id,
                'user_id' => auth()->id(),
                'supplier_id' => $opt['supplier_id'] ?? null,
                'type' => $type,
                'quantity_change' => round($change, 2),
                'quantity_before' => $before,
                'quantity_after' => $after,
                'unit_cost' => $opt['unit_cost'] ?? null,
                'reason' => $reason,
                'notes' => $opt['notes'] ?? null,
            ]);
        });

        AlertService::syncItem($item->fresh());
        return $movement;
    }
}
