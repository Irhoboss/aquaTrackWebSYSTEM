<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderManagementController extends Controller
{
    public function index()
    {
        $orders = Order::with(['customer', 'items'])->latest()->paginate(15);
        return view('orders.manage', compact('orders'));
    }

    public function update(Request $request, Order $order)
    {
        $data = $request->validate(['status' => ['required', 'in:confirmed,preparing,out_for_delivery,cancelled']]);
        DB::transaction(function () use ($order, $data) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $transitions = [
                'pending' => ['confirmed', 'cancelled'],
                'confirmed' => ['preparing', 'out_for_delivery', 'cancelled'],
                'preparing' => ['out_for_delivery', 'cancelled'],
                'delivery_failed' => ['out_for_delivery', 'cancelled'],
            ];
            if (!in_array($data['status'], $transitions[$locked->status] ?? [], true)) {
                throw ValidationException::withMessages(['status' => 'That order status change is not allowed. Refresh the order list and try again.']);
            }
            if ($data['status'] === 'cancelled') {
                foreach ($locked->items as $item) {
                    if ($item->product_id) Product::whereKey($item->product_id)->increment('stock', $item->quantity);
                }
            }
            $locked->update(['status' => $data['status']]);
        });

        $order->refresh();
        Audit::log('order.status_updated', $order, ['status' => $order->status]);
        return back()->with('success', "Order {$order->order_number} updated.");
    }
}
