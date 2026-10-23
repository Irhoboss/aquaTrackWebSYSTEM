<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index()
    {
        $name = session('chat_name');
        $latest = Message::latest('id')->limit(60)->get()->reverse()->values();

        return view('chat', ['name' => $name, 'messages' => $this->payload($latest, $name)]);
    }

    public function messages(Request $request)
    {
        $after = (int) $request->query('after', 0);
        $list = Message::where('id', '>', $after)->orderBy('id')->limit(100)->get();

        return response()->json($this->payload($list, session('chat_name')));
    }

    public function store(Request $request)
    {
        $name = session('chat_name');
        abort_unless($name, 403, 'Join the chat first');

        $data = $request->validate(['body' => 'required|string|max:500']);
        $msg = Message::create(['author' => $name, 'body' => trim($data['body']), 'kind' => 'user']);

        return response()->json($this->payload(collect([$msg]), $name)[0], 201);
    }

    public function setName(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:24|not_in:AquaTrack,aquatrack']);
        session(['chat_name' => trim($data['name'])]);

        return redirect()->route('chat.index');
    }

    private function payload($messages, ?string $me): array
    {
        return $messages->map(fn ($m) => [
            'id'     => $m->id,
            'author' => $m->author,
            'body'   => $m->body,
            'kind'   => $m->kind,
            'time'   => $m->created_at->format('H:i'),
            'mine'   => $m->kind === 'user' && $m->author === $me,
        ])->values()->all();
    }
}
