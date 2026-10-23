<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $suppliers = Supplier::withCount('items')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('contact_person', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")->orWhere('supplied_items', 'like', "%{$s}%")))
            ->orderBy('name')->paginate(15)->withQueryString();
        return view('suppliers.index', compact('suppliers'));
    }

    public function create() { return view('suppliers.form', ['supplier' => new Supplier(['status' => 'active'])]); }

    private function rules(?Supplier $s = null): array
    {
        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('suppliers', 'name')->ignore($s?->id)],
            'contact_person' => 'nullable|string|max:120',
            'phone' => ['nullable', 'regex:/^[0-9+()\-\s]{7,20}$/'],
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'supplied_items' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function store(Request $request)
    {
        $supplier = Supplier::create($request->validate($this->rules()));
        Audit::log('supplier.created', $supplier);
        return redirect()->route('suppliers.show', $supplier)->with('success', 'Supplier added.');
    }

    public function show(Supplier $supplier)
    {
        return view('suppliers.show', [
            'supplier' => $supplier,
            'items' => $supplier->items()->orderBy('name')->get(),
            'movements' => $supplier->movements()->with(['item', 'user'])->latest()->paginate(10),
        ]);
    }

    public function edit(Supplier $supplier) { return view('suppliers.form', compact('supplier')); }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($request->validate($this->rules($supplier)));
        Audit::log('supplier.updated', $supplier);
        return redirect()->route('suppliers.show', $supplier)->with('success', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->items()->exists() || $supplier->movements()->exists()) {
            return back()->with('error', 'This supplier is linked to inventory records. Set it to inactive instead of deleting.');
        }
        Audit::log('supplier.deleted', $supplier);
        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted.');
    }
}
