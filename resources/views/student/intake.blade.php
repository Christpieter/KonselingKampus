@extends('layouts.app')
@section('title', 'Data awal konseling')
@section('content')
<div class="mx-auto max-w-3xl">
    @include('partials.stepper', ['step' => 1])
    <h1 class="mb-6 text-3xl font-bold">Ceritakan keadaan Anda</h1>

    @if(session('booking_draft.triage_session_id'))
        @php
            $triage = \App\Models\TriageSession::find(session('booking_draft.triage_session_id'));
        @endphp
        @if($triage && $triage->status === 'completed')
            <div class="mb-6 rounded-2xl border border-sage-200 bg-sage-50 p-4" role="note">
                <h2 class="font-semibold text-sage-900">Triage kesehatan mental sudah selesai</h2>
                <div class="mt-3 grid gap-2 sm:grid-cols-3 text-sm">
                    <div class="rounded-lg bg-white p-3">
                        <p class="text-sage-600">Kecemasan</p>
                        <p class="font-bold text-ink-900">{{ $triage->anxiety_score }} <span class="font-normal text-slate-500">({{ $triage->levelLabel('anxiety') }})</span></p>
                    </div>
                    <div class="rounded-lg bg-white p-3">
                        <p class="text-sage-600">Depresi</p>
                        <p class="font-bold text-ink-900">{{ $triage->depression_score }} <span class="font-normal text-slate-500">({{ $triage->levelLabel('depression') }})</span></p>
                    </div>
                    <div class="rounded-lg bg-white p-3">
                        <p class="text-sage-600">Stres</p>
                        <p class="font-bold text-ink-900">{{ $triage->stress_score }} <span class="font-normal text-slate-500">({{ $triage->levelLabel('stress') }})</span></p>
                    </div>
                </div>
                @if($triage->needs_priority)
                    <p class="mt-3 text-sm text-rose-800 font-medium">⚠ Perlu perhatian prioritas: skor menunjukkan tingkat berat/sangat berat. Konselor akan diberi tahu untuk penjadwalan prioritas.</p>
                @endif
            </div>
        @endif
    @endif

    <form method="POST" action="{{ route('student.booking.intake.save') }}" class="space-y-6">@csrf
        <div class="card">
            <h2 class="mb-3 font-semibold">1. Tipe masalah</h2>
            <div class="grid gap-2 md:grid-cols-2">
                @foreach($types as $t)
                    <label class="flex cursor-pointer gap-3 rounded-lg border border-slate-200 p-3 has-[:checked]:border-ink-600 has-[:checked]:bg-ink-50">
                        <input type="radio" name="problem_type_id" value="{{ $t->id }}" class="mt-1" @checked(old('problem_type_id', $draft['problem_type_id'] ?? null) == $t->id) required>
                        <span><span class="block text-sm font-medium">{{ $t->name }}</span><span class="block text-xs text-slate-500">{{ $t->description }}</span></span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="card">
            <h2 class="mb-1 font-semibold">2. Cerita singkat</h2>
            <p class="mb-3 text-sm text-slate-500">Apa yang terjadi, sejak kapan, dan apa yang paling mengganggu. Minimal 30 karakter.</p>
            <textarea name="story" rows="6" maxlength="1500" class="input" required>{{ old('story', $draft['story'] ?? '') }}</textarea>
        </div>

        <p class="rounded-lg bg-butter-50 p-3 text-sm text-butter-900">
            Bila saat ini Anda merasa dalam bahaya, hubungi layanan darurat setempat atau orang terdekat sekarang, tanpa menunggu jadwal konseling.
        </p>

        <button class="btn btn-primary">Lanjut</button>
    </form>
</div>
@endsection
