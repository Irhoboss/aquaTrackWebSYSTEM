<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Services\StockService;
use App\Support\Audit;
use App\Support\Csv;
use App\Support\Fmt;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function store(Request $request, InventoryItem $item)
    {
        if ($item->status !== 'active') {
            return back()->with('error', 'Archived items cannot receive stock movements. Restore the item first.');
        }
        $type = $request->input('type');
        $data = $request->validate([
            'type' => 'required|in:in,out,adjust',
            'quantity' => ['required', 'numeric', $type === 'adjust' ? 'min:0' : 'gt:0', 'max:9999999'],
            'reason' => 'required|string|max:150',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'unit_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $movement = StockService::move($item, $data['type'], (float) $data['quantity'], $data['reason'], [
            'supplier_id' => $data['supplier_id'] ?? null,
            'unit_cost' => $data['unit_cost'] ?? null,
            'notes' => $data['notes'] ?? null,
            // Negative stock only for users with the explicit "stock.negative" ability (owner) AND who ticked the box.
            'allow_negative' => $request->boolean('allow_negative') && $request->user()->can('stock.negative'),
        ]);

        Audit::log('stock.'.$data['type'], $item, ['change' => $movement->quantity_change, 'after' => $movement->quantity_after, 'reason' => $data['reason']]);
        return back()->with('success', 'Stock updated. New quantity: '.Fmt::qty($movement->quantity_after).' '.$item->unit.'.');
    }

    private function query(Request $request)
    {
        return StockMovement::with(['item', 'user', 'supplier'])
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->user_id, fn ($q, $u) => $q->where('user_id', $u))
            ->when($request->from, fn ($q) => $q->where('created_at', '>=', Fmt::dayStart($request->from)))
            ->when($request->to, fn ($q) => $q->where('created_at', '<=', Fmt::dayEnd($request->to)))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('reason', 'like', "%{$s}%")
                ->orWhereHas('item', fn ($i) => $i->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%"))))
            ->latest();
    }

    public function index(Request $request)
    {
        if ($request->export) {
            $rows = (function () use ($request) {
                foreach ($this->query($request)->cursor() as $m) {
                    yield [Fmt::dt($m->created_at, 'Y-m-d H:i:s'), $m->item?->sku, $m->item?->name, $m->type, $m->quantity_change, $m->quantity_before, $m->quantity_after, $m->reason, $m->supplier?->name, $m->user?->name];
                }
            })();
            return Csv::download('stock-movements-'.now()->format('Ymd').'.csv', ['Date', 'SKU', 'Item', 'Type', 'Change', 'Before', 'After', 'Reason', 'Supplier', 'By'], $rows);
        }
        return view('inventory.movements', ['movements' => $this->query($request)->paginate(20)->withQueryString()]);
    }
}
