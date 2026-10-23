<?php

namespace App\Http\Controllers;

use App\Models\{Order, Product};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StorefrontController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::query()
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim((string) $request->query('q'));
                $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%"));
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('storefront.index', compact('products'));
    }

    public function cart()
    {
        [$lines, $total] = $this->cartLines();
        return view('storefront.cart', compact('lines', 'total'));
    }

    public function addToCart(Request $request, Product $product)
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]);
        abort_unless($product->is_active, 404);
        $cart = $request->session()->get('aq_cart', []);
        $quantity = ($cart[$product->id] ?? 0) + (int) $data['quantity'];
        if ($quantity > $product->stock) {
            return back()->withErrors(['cart' => "Only {$product->stock} {$product->unit} currently available."]);
        }
        $cart[$product->id] = $quantity;
        $request->session()->put('aq_cart', $cart);
        return back()->with('success', "{$product->name} added to your cart.");
    }

    public function updateCart(Request $request, Product $product)
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]);
        abort_unless($product->is_active, 404);
        if ((int) $data['quantity'] > $product->stock) {
            return back()->withErrors(['cart' => "Only {$product->stock} {$product->unit} currently available."]);
        }
        $cart = $request->session()->get('aq_cart', []);
        if (array_key_exists($product->id, $cart)) {
            $cart[$product->id] = (int) $data['quantity'];
            $request->session()->put('aq_cart', $cart);
        }
        return back()->with('success', 'Cart quantity updated.');
    }

    public function removeFromCart(Request $request, Product $product)
    {
        $cart = $request->session()->get('aq_cart', []);
        unset($cart[$product->id]);
        $request->session()->put('aq_cart', $cart);
        return back()->with('success', 'Item removed from your cart.');
    }

    public function checkout(Request $request)
    {
        [$lines, $total] = $this->cartLines();
        if (!$lines) return redirect()->route('cart.index')->with('warning', 'Add an item to your cart before checkout.');
        $user = $request->user();
        return view('storefront.checkout', compact('lines', 'total', 'user'));
    }

    public function placeOrder(Request $request)
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'delivery_address' => ['required', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $cart = $request->session()->get('aq_cart', []);
        if (!$cart) return redirect()->route('cart.index')->with('warning', 'Add an item to your cart before checkout.');

        $order = DB::transaction(function () use ($cart, $data, $request) {
            $products = Product::whereIn('id', array_keys($cart))->lockForUpdate()->get()->keyBy('id');
            $total = 0;
            foreach ($cart as $id => $quantity) {
                $product = $products->get($id);
                if (!$product || !$product->is_active || $quantity > $product->stock) {
                    throw ValidationException::withMessages(['cart' => 'Stock changed while you were checking out. Review your cart and try again.']);
                }
                $total += (float) $product->price * (int) $quantity;
            }

            $order = Order::create([
                'user_id' => $request->user()->id,
                'order_number' => 'AT-'.now()->format('ymd').'-'.Str::upper(Str::random(7)),
                'delivery_token' => Str::random(48),
                'status' => 'pending',
                'total' => $total,
                ...$data,
            ]);

            foreach ($cart as $id => $quantity) {
                $product = $products->get($id);
                $product->decrement('stock', $quantity);
                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'subtotal' => (float) $product->price * $quantity,
                ]);
            }
            return $order;
        });

        $request->session()->forget('aq_cart');
        return redirect()->route('orders.show', $order)->with('success', "Order {$order->order_number} placed successfully.");
    }

    public function orders(Request $request)
    {
        $orders = Order::with('items')->where('user_id', $request->user()->id)->latest()->paginate(10);
        return view('storefront.orders', compact('orders'));
    }

    public function showOrder(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        $order->load('items');
        return view('storefront.order', compact('order'));
    }

    private function cartLines(): array
    {
        $cart = session('aq_cart', []);
        $products = Product::whereIn('id', array_keys($cart))->where('is_active', true)->get()->keyBy('id');
        $lines = [];
        $total = 0;
        foreach ($cart as $id => $quantity) {
            $product = $products->get($id);
            if (!$product) continue;
            $quantity = min((int) $quantity, (int) $product->stock);
            if ($quantity < 1) continue;
            $subtotal = (float) $product->price * $quantity;
            $total += $subtotal;
            $lines[] = ['product' => $product, 'quantity' => $quantity, 'subtotal' => $subtotal];
        }
        return [$lines, $total];
    }
}
