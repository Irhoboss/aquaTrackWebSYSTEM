<?php

namespace App\Http\Controllers;

use App\Models\Container;
use App\Models\Order;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DriverController extends Controller
{
    public function index()
    {
        $orders = Order::with('customer')->whereIn('status', ['out_for_delivery', 'delivery_failed'])
            ->latest()->limit(30)->get();
        $containers = Container::whereIn('status', ['available', 'in_use'])->orderBy('code')->limit(30)->get();
        return view('driver.index', compact('orders', 'containers'));
    }

    public function scan(Request $request)
    {
        $type = $request->query('type');
        $code = trim((string) $request->query('code', ''));
        $error = null;

        if (!$type && str_starts_with($code, 'AQ|')) {
            [, $type, $code] = array_pad(explode('|', $code, 3), 3, '');
        }

        $order = null;
        $container = null;
        if ($code !== '') {
            if ($type === 'order') {
                $order = Order::with(['customer', 'items'])->where('delivery_token', $code)->first();
                if (!$order) $error = 'That order QR code was not recognized.';
            } elseif ($type === 'container') {
                $container = Container::where('code', $code)->first();
                if (!$container) $error = 'That container QR code was not recognized.';
            } else {
                $error = 'Choose an order or container QR code.';
            }
        }

        return view('driver.scan', compact('type', 'code', 'order', 'container', 'error'));
    }

    public function updateOrder(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'exists:orders,delivery_token'],
            'status' => ['required', 'in:out_for_delivery,delivered,delivery_failed'],
        ]);
        $order = DB::transaction(function () use ($data, $request) {
            $order = Order::where('delivery_token', $data['token'])->lockForUpdate()->firstOrFail();
            $allowed = $order->status === 'out_for_delivery'
                ? ['delivered', 'delivery_failed']
                : ($order->status === 'delivery_failed' ? ['out_for_delivery'] : []);
            if (!in_array($data['status'], $allowed, true)) {
                throw ValidationException::withMessages(['status' => 'This order can no longer be updated from its current status.']);
            }
            $order->update([
                'status' => $data['status'],
                'delivered_by' => $request->user()->id,
                'delivered_at' => $data['status'] === 'delivered' ? now() : null,
            ]);
            return $order;
        });

        Audit::log('driver.order_status_updated', $order, ['status' => $order->status]);
        return redirect()->route('driver.scan', ['type' => 'order', 'code' => $order->delivery_token])
            ->with('success', 'Order status updated.');
    }

    public function updateContainer(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'exists:containers,code'],
            'status' => ['required', 'in:available,in_use'],
        ]);
        $container = DB::transaction(function () use ($data) {
            $container = Container::where('code', $data['code'])->lockForUpdate()->firstOrFail();
            if (!in_array($container->status, ['available', 'in_use'], true)) {
                throw ValidationException::withMessages(['status' => 'This container needs inspection or station maintenance and cannot be changed by a driver.']);
            }
            $container->update(['status' => $data['status']]);
            return $container;
        });

        Audit::log('driver.container_status_updated', $container, ['status' => $container->status]);
        return redirect()->route('driver.scan', ['type' => 'container', 'code' => $container->code])
            ->with('success', 'Container status updated.');
    }
}
