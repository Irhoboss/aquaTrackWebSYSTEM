<?php

namespace App\Http\Controllers;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Supplier;
use App\Services\AlertService;
use App\Services\StockService;
use App\Support\Audit;
use App\Support\Csv;
use App\Support\Fmt;
use App\Support\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    private function query(Request $request)
    {
        $sorts = ['name', 'sku', 'quantity', 'updated_at'];
        $sort = in_array($request->sort, $sorts) ? $request->sort : 'name';
        $status = $request->input('status', 'active');

        return InventoryItem::with(['category', 'supplier'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->category, fn ($q, $c) => $q->where('category_id', $c))
            ->when($request->stock === 'low', fn ($q) => $q->where('quantity', '>', 0)->whereColumn('quantity', '<=', 'min_stock'))
            ->when($request->stock === 'out', fn ($q) => $q->where('quantity', '<=', 0))
            ->when($request->stock === 'ok', fn ($q) => $q->whereColumn('quantity', '>', 'min_stock'))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%")->orWhere('description', 'like', "%{$s}%")->orWhere('location', 'like', "%{$s}%")))
            ->orderBy($sort, $request->dir === 'desc' ? 'desc' : 'asc');
    }

    public function index(Request $request)
    {
        return view('inventory.index', [
            'items' => $this->query($request)->paginate(15)->withQueryString(),
            'categories' => InventoryCategory::withCount('items')->orderBy('name')->get(),
        ]);
    }

    public function export(Request $request)
    {
        $rows = (function () use ($request) {
            foreach ($this->query($request)->cursor() as $i) {
                yield [$i->sku, $i->name, $i->category?->name, $i->unit, $i->quantity, $i->min_stock, $i->max_stock, $i->stock_label,
                    $i->supplier?->name, $i->unit_cost, $i->location, $i->status, Fmt::dt($i->updated_at)];
            }
        })();
        return Csv::download('inventory-'.now()->format('Ymd').'.csv',
            ['SKU', 'Item', 'Category', 'Unit', 'Quantity', 'Minimum', 'Maximum', 'Stock status', 'Supplier', 'Unit cost', 'Location', 'Record status', 'Last updated'], $rows);
    }

    public function create()
    {
        return view('inventory.form', ['item' => new InventoryItem(['min_stock' => Setting::get('default_min_stock'), 'unit' => 'pcs']), 'categories' => InventoryCategory::orderBy('name')->get(), 'suppliers' => Supplier::where('status', 'active')->orderBy('name')->get()]);
    }

    private function rules(?InventoryItem $item = null): array
    {
        return [
            'sku' => ['required', 'string', 'max:50', Rule::unique('inventory_items', 'sku')->ignore($item?->id)],
            'name' => 'required|string|max:150',
            'category_id' => 'nullable|exists:inventory_categories,id',
            'description' => 'nullable|string|max:1000',
            'unit' => 'required|string|max:30',
            'min_stock' => 'required|numeric|min:0',
            'max_stock' => 'nullable|numeric|gte:min_stock',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'unit_cost' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:150',
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules() + ['quantity' => 'required|numeric|min:0']);
        $opening = (float) $data['quantity'];
        unset($data['quantity']);

        $item = InventoryItem::create($data + ['quantity' => 0, 'status' => 'active']);
        if ($opening > 0) {
            StockService::move($item, 'in', $opening, 'Opening balance', ['supplier_id' => $item->supplier_id, 'unit_cost' => $item->unit_cost]);
        } else {
            AlertService::syncItem($item->fresh());
        }
        Audit::log('inventory.created', $item, ['sku' => $item->sku]);
        return redirect()->route('inventory.show', $item)->with('success', 'Item added.');
    }

    public function show(InventoryItem $item)
    {
        return view('inventory.show', [
            'item' => $item->load(['category', 'supplier']),
            'movements' => $item->movements()->with(['user', 'supplier'])->latest()->paginate(10),
            'suppliers' => Supplier::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function edit(InventoryItem $item)
    {
        return view('inventory.form', ['item' => $item, 'categories' => InventoryCategory::orderBy('name')->get(), 'suppliers' => Supplier::where('status', 'active')->orderBy('name')->get()]);
    }

    /** Quantity is NOT editable here - only stock movements change it, so the ledger always balances. */
    public function update(Request $request, InventoryItem $item)
    {
        $item->update($request->validate($this->rules($item)));
        AlertService::syncItem($item->fresh());
        Audit::log('inventory.updated', $item);
        return redirect()->route('inventory.show', $item)->with('success', 'Item updated.');
    }

    public function archive(InventoryItem $item)
    {
        $item->update(['status' => $item->status === 'active' ? 'archived' : 'active']);
        AlertService::syncItem($item->fresh());
        Audit::log('inventory.'.($item->status === 'archived' ? 'archived' : 'restored'), $item);
        return back()->with('success', $item->status === 'archived' ? 'Item archived.' : 'Item restored.');
    }

    public function destroy(InventoryItem $item)
    {
        if ($item->movements()->exists()) {
            return back()->with('error', 'This item has stock history and cannot be deleted. Archive it instead.');
        }
        Audit::log('inventory.deleted', $item, ['sku' => $item->sku]);
        $item->delete();
        return redirect()->route('inventory.index')->with('success', 'Item deleted.');
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:80|unique:inventory_categories,name']);
        $cat = InventoryCategory::create($data);
        Audit::log('category.created', $cat);
        return back()->with('success', 'Category added.');
    }

    public function destroyCategory(InventoryCategory $category)
    {
        if ($category->items()->exists()) {
            return back()->with('error', 'This category still has items. Move them first.');
        }
        Audit::log('category.deleted', $category);
        $category->delete();
        return back()->with('success', 'Category deleted.');
    }
}
