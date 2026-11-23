@extends('layouts.app')
@section('title', 'Teman bicara')
@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-6">
        <a href="{{ route('student.dashboard') }}" class="text-sm font-semibold text-ink-700 underline underline-offset-4">Kembali ke sesi saya</a>
        <h1 class="mt-4 text-3xl font-bold md:text-4xl">Teman bicara</h1>
        <p class="mt-2 max-w-2xl leading-relaxed text-slate-600">
            Ruang untuk bercerita tentang hal yang sedang kamu hadapi. Kamu bisa mulai dari apa pun yang terasa nyaman untuk dibagikan.
        </p>
    </div>

    <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm leading-relaxed text-rose-900" role="note">
        <strong>Teman bicara ini bukan pengganti konselor dan bukan layanan darurat.</strong>
        Jika kamu atau orang lain dalam bahaya sekarang, hubungi layanan darurat setempat dan orang tepercaya yang bisa menemani. Jangan menunggu balasan AI.
    </div>

    <section class="overflow-hidden rounded-3xl border border-ink-100 bg-white shadow-sm" aria-label="Percakapan dengan teman bicara">
        <div class="flex items-center gap-3 border-b border-ink-100 px-5 py-4">
            <span class="grid h-10 w-10 place-items-center rounded-full bg-sage-100 font-display text-lg font-bold text-sage-900" aria-hidden="true">T</span>
            <div>
                <h2 class="text-base font-bold">Teman bicara</h2>
                <p class="text-xs text-slate-500">Dukungan umum yang hangat dan tidak menghakimi</p>
            </div>
        </div>

        <div id="chat-log" class="max-h-[55vh] min-h-64 space-y-3 overflow-y-auto bg-paper/60 px-4 py-5 md:px-6" role="log" aria-live="polite" aria-relevant="additions text">
            <div class="flex justify-start">
                <p class="max-w-[85%] rounded-2xl rounded-bl-sm border border-ink-100 bg-white px-4 py-3 text-sm leading-relaxed text-ink-900">
                    Hai, aku siap mendengarkan. Apa yang sedang terasa berat atau ingin kamu ceritakan hari ini?
                </p>
            </div>
        </div>

        @if($chatAvailable)
            <form id="companion-form" class="border-t border-ink-100 p-4 md:p-5">
                @csrf
                <label for="companion-message" class="sr-only">Tulis ceritamu</label>
                <div class="flex items-end gap-2">
                    <textarea id="companion-message" name="message" rows="2" maxlength="2000" required class="input min-h-12 resize-y" placeholder="Tulis ceritamu di sini…" aria-describedby="companion-privacy"></textarea>
                    <button id="companion-send" type="submit" class="btn btn-primary min-h-12 shrink-0" aria-label="Kirim pesan">Kirim</button>
                </div>
                <p id="companion-privacy" class="mt-2 text-xs leading-relaxed text-slate-500">Pesan dikirim ke penyedia layanan AI untuk menghasilkan balasan dan tidak disimpan oleh aplikasi ini. Jangan kirim data pribadi atau rahasia.</p>
                <p id="companion-status" class="mt-2 text-sm text-slate-600" role="status" aria-live="polite"></p>
            </form>
        @else
            <div class="border-t border-ink-100 bg-butter-50 px-5 py-4 text-sm leading-relaxed text-butter-900" role="status">
                Asisten AI belum tersambung. Hubungi admin jika fitur ini seharusnya sudah tersedia.
            </div>
        @endif
    </section>
</div>
@endsection

@if($chatAvailable)
    @push('scripts')
    <script>
    (() => {
        const form = document.getElementById('companion-form');
        const input = document.getElementById('companion-message');
        const send = document.getElementById('companion-send');
        const log = document.getElementById('chat-log');
        const status = document.getElementById('companion-status');
        const history = [];

        function addMessage(role, content) {
            const row = document.createElement('div');
            row.className = role === 'user' ? 'flex justify-end' : 'flex justify-start';
            const bubble = document.createElement('p');
            bubble.className = role === 'user'
                ? 'max-w-[85%] rounded-2xl rounded-br-sm bg-ink-800 px-4 py-3 text-sm leading-relaxed text-white'
                : 'max-w-[85%] rounded-2xl rounded-bl-sm border border-ink-100 bg-white px-4 py-3 text-sm leading-relaxed text-ink-900';
            bubble.textContent = content;
            row.appendChild(bubble);
            log.appendChild(row);
            log.scrollTop = log.scrollHeight;
        }

        form.addEventListener('submit', async event => {
            event.preventDefault();
            const message = input.value.trim();
            if (!message || send.disabled) return;

            addMessage('user', message);
            input.value = '';
            input.disabled = true;
            send.disabled = true;
            status.textContent = 'Teman bicara sedang menyiapkan balasan…';

            try {
                const response = await fetch(@json(route('student.companion.send')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': @json(csrf_token()),
                    },
                    body: JSON.stringify({ message, history }),
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Pesan belum dapat dikirim. Coba lagi nanti.');

                history.push({ role: 'user', content: message }, { role: 'assistant', content: data.reply });
                if (history.length > 12) history.splice(0, history.length - 12);
                addMessage('assistant', data.reply);
                status.textContent = '';
            } catch (error) {
                status.textContent = error.message || 'Terjadi kendala. Coba lagi nanti.';
            } finally {
                input.disabled = false;
                send.disabled = false;
                input.focus();
            }
        });
    })();
    </script>
    @endpush
@endif
