@extends('layouts.app')

@section('title', 'Chat dengan Mahasiswa - ' . $booking->student->name)

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-ink-900">Chat dengan Mahasiswa</h1>
            <p class="text-slate-600 mt-1">
                {{ $booking->student->name }}@if($booking->student->nim) ({{ $booking->student->nim }})@endif &bull;
                {{ \Carbon\Carbon::parse($booking->session_date)->isoFormat('dddd, D MMMM YYYY') }} &bull;
                {{ \Carbon\Carbon::parse($booking->start_time)->format('H:i') }}&ndash;{{ \Carbon\Carbon::parse($booking->end_time)->format('H:i') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-2">
            <form method="POST" action="{{ route('counselor.complete', $booking) }}" onsubmit="return confirm('Tandai sesi ini selesai? Chat akan ditutup dan mahasiswa dapat memberi rating.')">
                @csrf
                <button type="submit" class="btn btn-primary shrink-0">
                    Selesaikan sesi
                </button>
            </form>
            <a href="{{ route('counselor.dashboard') }}" class="btn btn-ghost shrink-0">Kembali ke Dashboard</a>
        </div>
    </div>

    <div class="card flex flex-col h-[calc(100vh-22rem)] min-h-[500px]">
        <div class="p-4 border-b border-ink-100">
            <p class="text-sm text-slate-600">
                Chat ini hanya terlihat oleh Anda dan mahasiswa. Chat ditutup setelah sesi ditandai selesai.
            </p>
        </div>

        <div id="chat-log" class="flex-1 overflow-y-auto p-4 space-y-4" role="log" aria-live="polite" aria-relevant="additions text">
            @forelse($booking->messages as $msg)
                @php $own = $msg->sender_id === auth()->id(); @endphp
                <div class="flex items-end gap-2 {{ $own ? 'justify-end' : 'justify-start' }}" data-message-id="{{ $msg->id }}">
                    @unless($own)
                        <div class="w-8 h-8 rounded-full bg-rose-100 flex items-center justify-center shrink-0 text-rose-700 font-medium text-sm">
                            {{ Str::upper(Str::substr($msg->sender->name, 0, 1)) }}
                        </div>
                    @endunless

                    <div class="max-w-[70%] px-4 py-2.5 {{ $own
                        ? 'bg-indigo-600 text-white rounded-2xl rounded-br-sm'
                        : 'bg-slate-100 text-ink-900 rounded-2xl rounded-bl-sm' }}">
                        <p class="whitespace-pre-wrap break-words">{{ $msg->content }}</p>
                        <p class="text-xs mt-1 opacity-70 text-right">
                            {{ $msg->created_at->format('H:i') }}
                            @if($own && $msg->read_at)<span class="ml-1">✓✓</span>@endif
                        </p>
                    </div>

                    @if($own)
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center shrink-0 text-indigo-700 font-medium text-sm">
                            {{ Str::upper(Str::substr(auth()->user()->name, 0, 1)) }}
                        </div>
                    @endif
                </div>
            @empty
                <p id="chat-empty" class="text-center text-sm text-slate-500 mt-8">
                    Belum ada pesan. Mulai percakapan dengan mahasiswa.
                </p>
            @endforelse
        </div>

        <form id="chat-form" class="p-4 border-t border-ink-100 bg-paper/50" action="{{ route('counselor.chat.send', $booking) }}" method="POST">
            @csrf
            <div class="flex gap-3">
                <textarea id="chat-message" name="content" rows="2" maxlength="3000" required
                          class="input flex-1 min-h-12 resize-y"
                          placeholder="Tulis pesan untuk mahasiswa…"
                          aria-describedby="chat-hint"></textarea>
                <button type="submit" id="chat-send" class="btn btn-primary min-h-12 shrink-0" aria-label="Kirim pesan">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </button>
            </div>
            <p id="chat-hint" class="mt-2 text-xs text-slate-500">Maksimal 3000 karakter. Enter untuk baris baru, Ctrl+Enter atau tombol Kirim untuk mengirim.</p>
            <p id="chat-status" class="mt-2 text-sm text-red-600 hidden" role="status" aria-live="polite"></p>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form   = document.getElementById('chat-form');
    const input  = document.getElementById('chat-message');
    const send   = document.getElementById('chat-send');
    const log    = document.getElementById('chat-log');
    const status = document.getElementById('chat-status');

    const ME_ID       = @json(auth()->id());
    const ME_INITIAL  = @json(Str::upper(Str::substr(auth()->user()->name, 0, 1)));
    const THEM_INITIAL = @json(Str::upper(Str::substr($booking->student->name, 0, 1)));
    const POLL_URL    = @json(route('counselor.chat.index', $booking));
    const CSRF        = document.querySelector('meta[name="csrf-token"]')?.content ?? @json(csrf_token());

    const lastId = () => {
        const nodes = log.querySelectorAll('[data-message-id]');
        return nodes.length ? parseInt(nodes[nodes.length - 1].dataset.messageId, 10) : 0;
    };

    const nearBottom = () => log.scrollHeight - log.scrollTop - log.clientHeight < 120;
    const scrollToBottom = () => { log.scrollTop = log.scrollHeight; };

    function el(tag, className, text) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function appendMessage(msg) {
        // Hindari pesan dobel (polling vs respons kirim)
        if (log.querySelector(`[data-message-id="${msg.id}"]`)) return;

        const own = msg.sender_id === ME_ID;
        const stick = own || nearBottom();
        document.getElementById('chat-empty')?.remove();

        const wrap = el('div', `flex items-end gap-2 ${own ? 'justify-end' : 'justify-start'}`);
        wrap.dataset.messageId = msg.id;

        const bubble = el('div', `max-w-[70%] px-4 py-2.5 ${own
            ? 'bg-indigo-600 text-white rounded-2xl rounded-br-sm'
            : 'bg-slate-100 text-ink-900 rounded-2xl rounded-bl-sm'}`);
        bubble.append(el('p', 'whitespace-pre-wrap break-words', msg.content));

        const meta = el('p', 'text-xs mt-1 opacity-70 text-right', msg.time);
        if (own && msg.read) meta.append(el('span', 'ml-1', '✓✓'));
        bubble.append(meta);

        const avatar = el('div',
            `w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-sm font-medium ${own
                ? 'bg-indigo-100 text-indigo-700' : 'bg-rose-100 text-rose-700'}`,
            own ? ME_INITIAL : THEM_INITIAL);

        // Mahasiswa: avatar di kiri. Konselor (Anda): avatar di kanan.
        if (own) wrap.append(bubble, avatar); else wrap.append(avatar, bubble);

        log.appendChild(wrap);
        if (stick) scrollToBottom();
    }

    async function fetchNew() {
        if (document.hidden) return;
        try {
            const res = await fetch(`${POLL_URL}?since=${lastId()}`, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            (data.messages || []).forEach(appendMessage);
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

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                },
                body: JSON.stringify({ content }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Gagal mengirim pesan. Muat ulang halaman lalu coba lagi.');
            appendMessage(data.message);
            input.value = '';
            input.style.height = 'auto';
        } catch (err) {
            status.textContent = err.message;
            status.classList.remove('hidden');
        } finally {
            send.disabled = false;
            input.focus();
        }
    });

    // Ctrl+Enter / Cmd+Enter = kirim
    input.addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            form.requestSubmit();
        }
    });

    input.addEventListener('input', () => {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 150) + 'px';
    });

    scrollToBottom();
    const polling = setInterval(fetchNew, 3000);
    window.addEventListener('beforeunload', () => clearInterval(polling));
});
</script>
@endpush
@endsection