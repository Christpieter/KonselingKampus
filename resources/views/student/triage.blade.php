@extends('layouts.app')
@section('title', 'Triage Kesehatan Mental')
@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-6">
        <a href="{{ route('student.dashboard') }}" class="text-sm font-semibold text-ink-700 underline underline-offset-4">Kembali ke sesi saya</a>
        <h1 class="mt-4 text-3xl font-bold md:text-4xl">Triage Kesehatan Mental</h1>
        <p class="mt-2 max-w-2xl leading-relaxed text-slate-600">
            Percakapan singkat untuk memahami kondisi emosional Anda sebelum sesi konseling.
            Asisten AI akan mengajukan 10 pertanyaan secara alami (bukan kuesioner kaku).
            Hasilnya hanya dilihat konselor Anda dan bersifat rahasia.
        </p>
    </div>

    @if($existingTriage)
        <div class="mb-6 rounded-2xl border border-sage-200 bg-sage-50 p-4 text-sm leading-relaxed text-sage-900" role="note">
            <strong>Anda sudah menyelesaikan triage sebelumnya.</strong>
            Hasil: Kecemasan <span class="font-semibold">{{ $existingTriage->levelLabel('anxiety') }}</span>,
            Depresi <span class="font-semibold">{{ $existingTriage->levelLabel('depression') }}</span>,
            Stres <span class="font-semibold">{{ $existingTriage->levelLabel('stress') }}</span>.
            <br class="mt-2">
            <a href="{{ route('student.booking.start') }}" class="btn btn-sm mt-2">Lanjut ke Booking</a>
            <button id="retake-triage" type="button" class="btn btn-ghost btn-sm mt-2 ml-2">Ulangi Triage</button>
        </div>
    @endif

    <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm leading-relaxed text-rose-900" role="note">
        <strong>Ini bukan diagnosis medis dan bukan layanan darurat.</strong>
        Kalau Anda atau orang lain dalam bahaya sekarang, hubungi layanan darurat setempat dan orang tepercaya yang bisa menemani. Jangan menunggu balasan AI.
    </div>

    <section class="overflow-hidden rounded-3xl border border-ink-100 bg-white shadow-sm" aria-label="Percakapan triage">
        <div class="flex items-center gap-3 border-b border-ink-100 px-5 py-4">
            <span class="grid h-10 w-10 place-items-center rounded-full bg-amber-100 font-display text-lg font-bold text-amber-900" aria-hidden="true">T</span>
            <div>
                <h2 class="text-base font-bold">Asisten Triage</h2>
                <p class="text-xs text-slate-500">10 pertanyaan &bull; 3 area: Kecemasan, Depresi, Stres</p>
            </div>
            <div id="progress-badge" class="ml-auto hidden rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800">
                Terjawab <span id="current-q">0</span> / {{ $questionsCount }}
            </div>
        </div>

        <div class="border-b border-ink-100 bg-amber-50 px-5 py-3 text-sm text-amber-950" role="note">
            <strong>Panduan jawaban:</strong>
            0 = Tidak pernah &middot;
            1 = Beberapa hari &middot;
            2 = Lebih dari separuh hari &middot;
            3 = Hampir setiap hari.
            Anda boleh menjawab dengan angka atau teks pilihan.
        </div>

        <div id="chat-log" class="max-h-[55vh] min-h-64 space-y-3 overflow-y-auto bg-paper/60 px-4 py-5 md:px-6" role="log" aria-live="polite" aria-relevant="additions text">
            <div class="flex justify-start">
                <p class="max-w-[85%] rounded-2xl rounded-bl-sm border border-ink-100 bg-white px-4 py-3 text-sm leading-relaxed text-ink-900">
                    Hai. Saya di sini untuk memahami kondisi emosional Anda sebelum sesi konseling.
                    Kita akan bicara sebentar tentang perasaan Anda dua minggu terakhir.
                    Jawab jujur ya—tidak ada jawaban salah. Mulai dari yang pertama:
                    <br><br>
                    <strong>Apakah ada rasa gelisah atau kegugupan Anda akhir-akhir ini?</strong>
                </p>
            </div>
        </div>

        @if($chatAvailable)
            <form id="triage-form" class="border-t border-ink-100 p-4 md:p-5">
                @csrf
                <input type="hidden" id="triage-session-id" name="triage_session_id" value="">
                <label for="triage-message" class="sr-only">Tulis jawaban Anda</label>
                <div class="flex items-end gap-2">
                    <textarea id="triage-message" name="message" rows="2" maxlength="2000" required class="input min-h-12 resize-y" placeholder="Tulis jawaban Anda (0-3)…" aria-describedby="triage-privacy"></textarea>
                    <button id="triage-send" type="submit" class="btn btn-primary min-h-12 shrink-0" aria-label="Kirim jawaban">Kirim</button>
                </div>
                <p id="triage-privacy" class="mt-2 text-xs leading-relaxed text-slate-500">Pesan diteruskan ke layanan AI dan mungkin tercatat di server penyedia. Jangan kirim data pribadi atau rahasia.</p>
                <p id="triage-status" class="mt-2 text-sm text-slate-600" role="status" aria-live="polite"></p>
            </form>
        @else
            <div class="border-t border-ink-100 bg-butter-50 px-5 py-4 text-sm leading-relaxed text-butter-900" role="status">
                Asisten AI belum tersambung. Hubungi admin jika fitur ini seharusnya sudah tersedia.
            </div>
        @endif
    </section>

    <div id="triage-result" class="mt-6 hidden">
        <div class="card space-y-4">
            <h2 class="text-xl font-bold">Hasil Triage Kesehatan Mental</h2>
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-lg border border-slate-200 p-4">
                    <h3 class="font-semibold text-slate-700">Kecemasan</h3>
                    <p id="anx-score" class="mt-1 text-3xl font-bold text-ink-900">—</p>
                    <p id="anx-level" class="text-sm text-slate-500">—</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-4">
                    <h3 class="font-semibold text-slate-700">Depresi</h3>
                    <p id="depr-score" class="mt-1 text-3xl font-bold text-ink-900">—</p>
                    <p id="depr-level" class="text-sm text-slate-500">—</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-4">
                    <h3 class="font-semibold text-slate-700">Stres</h3>
                    <p id="stress-score" class="mt-1 text-3xl font-bold text-ink-900">—</p>
                    <p id="stress-level" class="text-sm text-slate-500">—</p>
                </div>
            </div>
            <div id="priority-notice" class="hidden rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-900">
                <strong>Perlu perhatian prioritas:</strong> Salah satu skor menunjukkan tingkat berat/sangat berat. Konselor akan diberi tahu untuk penjadwalan prioritas.
            </div>
            <div class="flex flex-wrap gap-2">
                <a id="proceed-booking" href="{{ route('student.booking.start') }}" class="btn btn-primary hidden">Lanjut ke Booking</a>
            </div>
        </div>
    </div>

    <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
        <p class="max-w-xl text-xs leading-relaxed text-slate-500">Percakapan ini dicatat untuk keperluan triage. Hasil skor hanya diakses konselor yang menangani sesi Anda.</p>
    </div>
</div>

@if($chatAvailable)
<script>
(() => {
    const form = document.getElementById('triage-form');
    const input = document.getElementById('triage-message');
    const send = document.getElementById('triage-send');
    const log = document.getElementById('chat-log');
    const status = document.getElementById('triage-status');
    const progressBadge = document.getElementById('progress-badge');
    const currentQ = document.getElementById('current-q');
    const sessionIdInput = document.getElementById('triage-session-id');
    const resultSection = document.getElementById('triage-result');
    const proceedBtn = document.getElementById('proceed-booking');
    const retakeBtn = document.getElementById('retake-triage');
    const totalQuestions = {{ $questionsCount }};
    const csrf = '{{ csrf_token() }}';
    const sendUrl = '{{ route('student.triage.send') }}';
    const calculateUrl = '{{ route('student.triage.calculate') }}';

    let triageSessionId = null;
    let finished = false;

    function jsonHeaders() {
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf,
        };
    }

    function addMessage(role, text) {
        const row = document.createElement('div');
        row.className = `flex ${role === 'user' ? 'justify-end' : 'justify-start'}`;
        const bubble = document.createElement('p');
        bubble.className = role === 'user'
            ? 'max-w-[85%] rounded-2xl rounded-br-sm bg-ink-800 px-4 py-3 text-sm leading-relaxed text-white'
            : 'max-w-[85%] rounded-2xl rounded-bl-sm border border-ink-100 bg-white px-4 py-3 text-sm leading-relaxed text-ink-900';
        bubble.style.whiteSpace = 'pre-wrap';
        bubble.textContent = text;
        row.appendChild(bubble);
        log.appendChild(row);
        log.scrollTop = log.scrollHeight;
    }

    function updateProgress(answerCount) {
        progressBadge.classList.remove('hidden');
        currentQ.textContent = Math.min(answerCount, totalQuestions);
    }

    function renderResult(session) {
        document.getElementById('anx-score').textContent = session.anxiety_score;
        document.getElementById('anx-level').textContent = session.level_labels.anxiety;
        document.getElementById('depr-score').textContent = session.depression_score;
        document.getElementById('depr-level').textContent = session.level_labels.depression;
        document.getElementById('stress-score').textContent = session.stress_score;
        document.getElementById('stress-level').textContent = session.level_labels.stress;

        if (session.needs_priority) {
            document.getElementById('priority-notice').classList.remove('hidden');
        }

        proceedBtn.classList.remove('hidden');
        resultSection.classList.remove('hidden');
        resultSection.scrollIntoView({ behavior: 'smooth' });
    }

    async function calculateScores() {
        const res = await fetch(calculateUrl, {
            method: 'POST',
            headers: jsonHeaders(),
            body: JSON.stringify({ triage_session_id: triageSessionId }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message || 'Gagal menghitung skor.');
        return data.triage_session;
    }

    async function proceedToBooking() {
        window.location.href = '{{ route('student.booking.start') }}';
    }

    async function submitMessage() {
        const message = input.value.trim();
        if (!message || send.disabled) return;

        addMessage('user', message);
        input.value = '';
        send.disabled = true;
        status.textContent = 'Menunggu balasan…';

        try {
            const body = { message };
            if (triageSessionId) body.triage_session_id = triageSessionId;

            const response = await fetch(sendUrl, {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify(body),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.message || 'Pesan belum terkirim. Coba lagi.');
            }

            addMessage('assistant', data.reply);

            if (data.triage_session_id) {
                triageSessionId = data.triage_session_id;
                sessionIdInput.value = triageSessionId;
            }

            if (typeof data.answer_count === 'number') {
                updateProgress(data.answer_count);
            }

            if (data.triage_complete === true) {
                finished = true;
                input.disabled = true;
                form.classList.add('hidden');
                status.textContent = 'Triage selesai. Menghitung skor…';

                try {
                    const session = await calculateScores();
                    renderResult(session);
                    status.textContent = '';
                    // Auto-redirect to booking after a brief moment to let user see results
                    setTimeout(proceedToBooking, 1500);
                } catch (e) {
                    // Skor gagal dihitung: buka kembali form supaya pengguna tidak buntu
                    finished = false;
                    form.classList.remove('hidden');
                    input.disabled = false;
                    status.textContent = e.message;
                    addMessage('assistant', e.message);
                }
            } else {
                status.textContent = '';
            }
        } catch (error) {
            // Kembalikan teks agar bisa dikirim ulang
            input.value = message;
            addMessage('assistant', error.message || 'Asisten sedang tidak tersedia. Coba lagi nanti.');
            status.textContent = 'Balasan gagal dimuat.';
        } finally {
            if (!finished) {
                send.disabled = false;
                input.focus();
            }
        }
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        submitMessage();
    });

    // Enter = kirim, Shift+Enter = baris baru
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            submitMessage();
        }
    });

    if (retakeBtn) {
        retakeBtn.addEventListener('click', () => {
            if (confirm('Yakin ingin mengulang triage? Hasil sebelumnya akan diarsipkan.')) {
                window.location.reload();
            }
        });
    }
})();
</script>
@endif
@endsection