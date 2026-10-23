@extends('layouts.app')
@section('title', $isCustomer ? 'Contact support' : 'Customer chat')
@section('subtitle', $isCustomer ? 'Message the AquaTrack team' : 'Reply to customer messages')
@section('content')
<div class="support-layout {{ $isCustomer ? 'customer-chat' : '' }}">
  @unless($isCustomer)
  <aside class="at-card support-inbox">
    <div class="support-inbox-head"><h2>Customers</h2><span class="small text-muted">{{ $customers->count() }}</span></div>
    @forelse($customers as $person)
      @php $lastMessage = $person->id === optional($customer)->id ? $messages->last() : null; @endphp
      <a class="support-person {{ optional($customer)->id === $person->id ? 'active' : '' }}" href="{{ route('support-chat.index', ['customer' => $person->id]) }}">
        <span class="at-avatar">{{ $person->initials }}</span>
        <span class="support-person-copy"><strong>{{ $person->name }}</strong><small>{{ $lastMessage?->body ?? $person->email }}</small></span>
      </a>
    @empty
      <p class="text-muted p-3 mb-0">No active customer accounts yet.</p>
    @endforelse
  </aside>
  @endunless

  <section class="at-card support-conversation" data-chat-updates="{{ route('support-chat.updates', $isCustomer ? [] : ['customer' => $customer?->id]) }}">
    @if($customer)
      <header class="support-chat-head">
        <span class="at-avatar">{{ $customer->initials }}</span>
        <span><strong>{{ $isCustomer ? 'AquaTrack Support' : $customer->name }}</strong><small>{{ $isCustomer ? 'We usually reply as soon as possible' : $customer->email }}</small></span>
      </header>
      <div class="support-messages" id="supportMessages" aria-live="polite">
        @forelse($messages as $message)
          <article class="support-message {{ $message->sender_id === auth()->id() ? 'mine' : '' }}" data-message-id="{{ $message->id }}">
            <div class="support-bubble"><strong>{{ $message->sender_id === auth()->id() ? 'You' : $message->sender?->name }}</strong><p>{{ $message->body }}</p><time>{{ $message->created_at->format('M j, g:i a') }}</time></div>
          </article>
        @empty
          <div class="support-chat-empty"><i class="bi bi-chat-heart"></i><strong>Start the conversation</strong><span>Send a message and the team will get back to you here.</span></div>
        @endforelse
      </div>
      <form class="support-compose" method="post" action="{{ route('support-chat.store', $isCustomer ? [] : ['customer' => $customer->id]) }}">
        @csrf
        <label class="visually-hidden" for="supportBody">Your message</label>
        <textarea id="supportBody" name="body" rows="1" maxlength="4000" placeholder="Write a message…" required>{{ old('body') }}</textarea>
        <button class="btn btn-primary" type="submit"><span>Send</span><i class="bi bi-send-fill"></i></button>
      </form>
      @error('body')<div class="text-danger small px-3 pb-2">{{ $message }}</div>@enderror
    @else
      <div class="support-chat-empty"><i class="bi bi-chat-dots"></i><strong>Select a customer</strong><span>Choose a customer to view the conversation and send a reply.</span></div>
    @endif
  </section>
</div>
@endsection
@push('scripts')
<script>
(() => {
  const pane = document.getElementById('supportMessages');
  if (!pane) return;
  const conversation = document.querySelector('[data-chat-updates]');
  let latest = Math.max(0, ...Array.from(pane.querySelectorAll('[data-message-id]'), el => Number(el.dataset.messageId)));
  const nearBottom = () => pane.scrollHeight - pane.scrollTop - pane.clientHeight < 120;
  pane.scrollTop = pane.scrollHeight;
  const poll = async () => {
    try {
      const response = await fetch(`${conversation.dataset.chatUpdates}?after=${latest}`, {headers:{Accept:'application/json'}, credentials:'same-origin'});
      if (!response.ok) return;
      const messages = await response.json();
      const shouldScroll = nearBottom();
      if (messages.length) pane.querySelector('.support-chat-empty')?.remove();
      messages.forEach(message => {
        if (message.id <= latest) return;
        const article = document.createElement('article'); article.className = `support-message${message.mine ? ' mine' : ''}`; article.dataset.messageId = message.id;
        const bubble = document.createElement('div'); bubble.className = 'support-bubble';
        const sender = document.createElement('strong'); sender.textContent = message.mine ? 'You' : message.sender;
        const body = document.createElement('p'); body.textContent = message.body;
        const time = document.createElement('time'); time.textContent = message.time;
        bubble.append(sender, body, time); article.append(bubble); pane.append(article); latest = message.id;
      });
      if (shouldScroll && messages.length) pane.scrollTop = pane.scrollHeight;
    } catch (_) {}
  };
  window.setInterval(poll, 5000);
})();
</script>
@endpush
