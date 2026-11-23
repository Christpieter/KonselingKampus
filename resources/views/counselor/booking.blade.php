@extends('layouts.app')
@section('title', 'Detail sesi')
@section('content')
<a href="{{ route('counselor.dashboard') }}" class="text-sm text-ink-700 underline">&larr; Kembali</a>
<div class="mt-3 grid gap-5 md:grid-cols-3">
    <div class="space-y-5 md:col-span-2">
        <div class="card">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h1 class="text-xl font-semibold">{{ $booking->student->name }}</h1>
                    <div class="text-sm text-slate-600">NIM {{ $booking->student->nim }} &middot; {{ $booking->student->email }}@if($booking->student->phone) &middot; {{ $booking->student->phone }}@endif</div>
                </div>
                <span class="badge bg-slate-100 text-slate-700">{{ $booking->statusLabel() }}</span>
            </div>
            @if($booking->needs_priority)
                <p class="mt-4 rounded-lg bg-rose-50 p-3 text-sm text-rose-800">Mahasiswa menjawab pertanyaan 10 (pikiran menghilang atau menyakiti diri) dengan jawaban selain "tidak pernah". Pertimbangkan untuk menghubungi lebih dulu dan menjadwalkan lebih cepat.</p>
            @endif
            <h2 class="mt-5 text-sm text-slate-500">Tipe masalah</h2><p>{{ $booking->problemType->name }}</p>
            <h2 class="mt-4 text-sm text-slate-500">Cerita</h2><p class="whitespace-pre-line">{{ $booking->story }}</p>
        </div>
        <div class="card">
            <h2 class="font-semibold">Kuesioner &middot; skor {{ $booking->score }}/30 ({{ \App\Models\Booking::scoreLevel($booking->score) }})</h2>
            @php
                $answers = $booking->answers ?? [];
                $answerIsList = array_is_list($answers);
                $answerLabels = [0 => 'Tidak pernah', 1 => 'Beberapa hari', 2 => 'Lebih dari separuh hari', 3 => 'Hampir setiap hari'];
            @endphp
            <table class="mt-3 w-full text-sm">
                @foreach($questions as $q)
                    @php
                        // Jawaban tersimpan sebagai list berurutan (indeks 0..9) atau ber-key id pertanyaan.
                        $a = $answerIsList ? ($answers[$loop->index] ?? null) : ($answers[$q->id] ?? null);
                    @endphp
                    <tr class="border-t border-slate-100"><td>{{ $q->position }}. {{ $q->text }}</td>
                        <td class="whitespace-nowrap">{{ $answerLabels[$a] ?? '-' }}</td></tr>
                @endforeach
            </table>
            <p class="mt-3 text-xs text-slate-500">Skor ini gambaran awal, bukan diagnosis.</p>
        </div>
    </div>

    <aside class="card h-fit space-y-4">
        <div>
            <div class="text-sm text-slate-500">Jadwal diminta</div>
            <div class="font-semibold">{{ $booking->session_date->locale('id')->translatedFormat('l, d F Y') }}</div>
            <div>{{ $booking->timeRange() }}</div>
        </div>

        @if($booking->status === 'pending')
            <form method="POST" action="{{ route('counselor.confirm', $booking) }}">@csrf<button class="btn btn-primary w-full">Konfirmasi sesi</button></form>
            <form method="POST" action="{{ route('counselor.reject', $booking) }}" class="space-y-2 border-t border-slate-100 pt-4">@csrf
                <label class="label">Alasan penolakan</label>
                <textarea name="counselor_note" rows="3" class="input" required>{{ old('counselor_note') }}</textarea>
                <button class="btn btn-danger w-full">Tolak permintaan</button>
            </form>
        @elseif($booking->status === 'confirmed')
            <a href="{{ route('counselor.chat.index', $booking) }}" class="btn btn-primary w-full">Chat dengan mahasiswa</a>

            <form method="POST" action="{{ route('counselor.complete', $booking) }}" class="space-y-2 border-t border-slate-100 pt-4">@csrf
                <label class="label">Catatan sesi (opsional, hanya terlihat oleh Anda)</label>
                <textarea name="counselor_note" rows="4" class="input"></textarea>
                <button class="btn btn-primary w-full">Tandai selesai</button>
            </form>
            <p class="text-xs text-slate-500">Chat ditutup setelah sesi ditandai selesai.</p>
        @elseif($booking->counselor_note)
            <div><div class="text-sm text-slate-500">Catatan</div><p class="whitespace-pre-line text-sm">{{ $booking->counselor_note }}</p></div>
        @endif

        @if($booking->status === 'completed')
            <div class="border-t border-slate-100 pt-4">
                <h2 class="mb-2 text-sm font-semibold">Rekap sesi</h2>
                @include('counselor.partials.report-actions', ['booking' => $booking])
            </div>
        @endif

        @if($booking->rating)
            <div class="border-t border-slate-100 pt-4 text-sm"><div class="text-slate-500">Rating mahasiswa</div><span class="text-butter-500">{{ str_repeat('★', $booking->rating->stars) }}</span> {{ $booking->rating->comment }}</div>
        @endif
    </aside>
</div>
@endsection