<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Http\Request;

class SupportChatController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('support.chat'), 403);

        $isCustomer = $request->user()->role === 'customer';
        $customers = $isCustomer ? collect() : User::query()
            ->where('role', 'customer')->where('is_active', true)->orderBy('name')->get();
        $customer = $isCustomer
            ? $request->user()
            : $customers->firstWhere('id', (int) $request->query('customer'));
        $messages = $customer
            ? SupportMessage::with('sender:id,name,role')->where('user_id', $customer->id)->orderByDesc('id')->limit(100)->get()->reverse()->values()
            : collect();

        return view('support-chat.index', compact('customers', 'customer', 'messages', 'isCustomer'));
    }

    public function updates(Request $request)
    {
        abort_unless($request->user()->can('support.chat'), 403);
        $customer = $this->customerFor($request);
        $after = max(0, (int) $request->query('after', 0));
        $messages = SupportMessage::with('sender:id,name,role')
            ->where('user_id', $customer->id)->where('id', '>', $after)->orderBy('id')->limit(100)->get();

        return response()->json($messages->map(fn (SupportMessage $message) => $this->payload($message))->values());
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('support.chat'), 403);
        $customer = $this->customerFor($request);
        $data = $request->validate(['body' => ['required', 'string', 'max:4000']]);
        SupportMessage::create([
            'user_id' => $customer->id,
            'sender_id' => $request->user()->id,
            'body' => trim($data['body']),
            'is_staff_reply' => $request->user()->role !== 'customer',
        ]);

        return redirect()->route('support-chat.index', $request->user()->role === 'customer' ? [] : ['customer' => $customer->id]);
    }

    private function customerFor(Request $request): User
    {
        if ($request->user()->role === 'customer') return $request->user();

        return User::query()->where('role', 'customer')->where('is_active', true)
            ->findOrFail($request->integer('customer'));
    }

    private function payload(SupportMessage $message): array
    {
        return [
            'id' => $message->id,
            'sender' => $message->sender?->name ?? 'AquaTrack',
            'body' => $message->body,
            'mine' => $message->sender_id === auth()->id(),
            'time' => $message->created_at->format('M j, g:i a'),
        ];
    }
}
