@extends('layouts.app')

@section('title', 'Chat dengan Konselor - ' . $booking->counselor->name)

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-ink-900">Chat dengan Konselor</h1>
            <p class="text-slate-600 mt-1">
                {{ $booking->counselor->name }} &bull;
                {{ \Carbon\Carbon::parse($booking->session_date)->isoFormat('dddd, D MMMM YYYY') }} &bull;
                {{ \Carbon\Carbon::parse($booking->start_time)->format('H:i') }}&ndash;{{ \Carbon\Carbon::parse($booking->end_time)->format('H:i') }}
            </p>
        </div>
        <a href="{{ route('student.dashboard') }}" class="btn btn-ghost shrink-0">Kembali ke Dashboard</a>
    </div>

    <div class="card flex flex-col h-[calc(100vh-22rem)] min-h-[500px]">
        <div class="p-4 border-b border-ink-100">
            <p class="text-sm text-slate-600">
                Chat ini hanya terlihat oleh Anda dan konselor. Pesan tersimpan hingga sesi selesai.
            </p>
        </div>

        <div id="chat-log" class="flex-1 overflow-y-auto p-4 space-y-4" role="log" aria-live="polite" aria-relevant="additions text">
            @foreach($booking->messages as $msg)
                <div class="flex {{ $msg->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}"
                     data-message-id="{{ $msg->id }}">
                    <div class="max-w-[70%] {{ $msg->sender_id === auth()->id() ? 'order-2' : 'order-1' }}">
                        <div class="{{ $msg->sender_id === auth()->id()
                            ? 'bg-indigo-600 text-white rounded-2xl rounded-tr-sm'
                            : 'bg-slate-100 text-ink-900 rounded-2xl rounded-tl-sm' }}
                            px-4 py-2.5">
                            <p class="whitespace-pre-wrap">{{ $msg->content }}</p>
                            <p class="text-xs mt-1 opacity-70 text-right">
                                {{ $msg->created_at->format('H:i') }}
                                @if($msg->sender_id === auth()->id() && $msg->read_at)
                                    <span class="ml-1">✓✓</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    @if($msg->sender_id !== auth()->id())
                        <div class="w-8 h-8 rounded-full bg-sage-100 flex items-center justify-center order-2 ml-2 shrink-0 text-sage-700 font-medium text-sm">
                            {{ Str::upper($msg->sender->name[0]) }}
                        </div>
                    @else
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center order-1 mr-2 shrink-0 text-indigo-700 font-medium text-sm">
                            {{ Str::upper(auth()->user()->name[0]) }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <form id="chat-form" class="p-4 border-t border-ink-100 bg-paper/50" action="{{ route('student.chat.send', $booking) }}" method="POST">
            @csrf
            <div class="flex gap-3">
                <textarea id="chat-message" name="content" rows="2" maxlength="3000" required
                          class="input flex-1 min-h-12 resize-y"
                          placeholder="Tulis pesan untuk konselor…"
                          aria-describedby="chat-hint"></textarea>
                <button type="submit" id="chat-send" class="btn btn-primary min-h-12 shrink-0" aria-label="Kirim pesan">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </button>
            </div>
            <p id="chat-hint" class="mt-2 text-xs text-slate-500">Maksimal 3000 karakter. Enter untuk baris baru, klik Kirim untuk mengirim.</p>
            <p id="chat-status" class="mt-2 text-sm text-slate-600 hidden" role="status" aria-live="polite"></p>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('chat-form');
    const input = document.getElementById('chat-message');
    const send = document.getElementById('chat-send');
    const log = document.getElementById('chat-log');
    const status = document.getElementById('chat-status');

    let polling = null;
    const lastId = () => {
        const nodes = log.querySelectorAll('[data-message-id]');
        return nodes.length ? parseInt(nodes[nodes.length - 1].dataset.messageId, 10) : 0;
    };

    function scrollToBottom(instant = false) {
        log.scrollTop = instant ? log.scrollHeight : log.scrollHeight;
    }

    function appendMessage(msg, isOwn) {
        const wrap = document.createElement('div');
        wrap.className = `flex ${isOwn ? 'justify-end' : 'justify-start'}`;
        wrap.dataset.messageId = msg.id;

        const bubble = document.createElement('div');
        bubble.className = `max-w-[70%] ${isOwn ? 'order-2 bg-indigo-600 text-white rounded-2xl rounded-tr-sm' : 'order-1 bg-slate-100 text-ink-900 rounded-2xl rounded-tl-sm'} px-4 py-2.5`;
        bubble.innerHTML = `<p class="whitespace-pre-wrap">${escapeHtml(msg.content)}</p>
            <p class="text-xs mt-1 opacity-70 text-right">${escapeHtml(msg.created_at)} ${isOwn && msg.read_at ? '<span class="ml-1">✓✓</span>' : ''}</p>`;

        const avatar = document.createElement('div');
        avatar.className = `w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-sm font-medium ${isOwn ? 'order-1 mr-2 bg-indigo-100 text-indigo-700' : 'order-2 ml-2 bg-sage-100 text-sage-700'}`;
        avatar.textContent = isOwn ? '{{ Str::upper(auth()->user()->name[0]) }}' : '{{ Str::upper($booking->counselor->name[0]) }}';

        wrap.append(isOwn ? bubble : avatar);
        wrap.append(isOwn ? avatar : bubble);
        log.appendChild(wrap);
        scrollToBottom();
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    async function fetchNew() {
        try {
            const since = lastId();
            const res = await fetch(`{{ route('student.chat.index', $booking) }}?since=${since}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const data = await res.json();
            if (data.messages?.length) {
                data.messages.forEach(m => appendMessage(m, m.sender_id === {{ auth()->id() }}));
            }
        } catch (e) {
            console.warn('Polling gagal', e);
        }
    }

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const content = input.value.trim();
        if (!content || send.disabled) return;

        send.disabled = true;
        status.classList.add('hidden');
        status.textContent = '';

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? ''
                },
                body: JSON.stringify({ content })
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal mengirim pesan');
            appendMessage(data.message, true);
            input.value = '';
            input.style.height = 'auto';
        } catch (err) {
            status.textContent = err.message;
            status.classList.remove('hidden');
        } finally {
            send.disabled = false;
        }
    });

    input.addEventListener('input', () => {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 150) + 'px';
    });

    scrollToBottom(true);
    polling = setInterval(fetchNew, 3000);

    // Cleanup on unload
    window.addEventListener('beforeunload', () => clearInterval(polling));
});
</script>
@endpush
@endsection