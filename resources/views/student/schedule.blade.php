@extends('layouts.app')
@section('title', 'Pilih jadwal')
@section('content')
@include('partials.stepper', ['step' => 3])
<div class="mb-6 flex flex-wrap items-center gap-4">
    @include('partials.avatar', ['name' => $profile->user->name, 'size' => 'h-14 w-14 text-xl'])
    <div>
        <h1 class="text-3xl font-bold">Pilih tanggal dan jam</h1>
        <p class="text-sm text-slate-600">Dengan {{ $profile->user->name }} &middot; {{ $type?->name }} &middot; <a class="font-semibold text-ink-700 underline underline-offset-4" href="{{ route('student.booking.counselors') }}">ganti konselor</a></p>
    </div>
</div>

@if(session('booking_draft.triage_session_id'))
    @php
        $triage = \App\Models\TriageSession::find(session('booking_draft.triage_session_id'));
    @endphp
    @if($triage && $triage->status === 'completed')
        <div class="mb-6 rounded-2xl border border-sage-200 bg-sage-50 p-4" role="note">
            <h2 class="font-semibold text-sage-900">Triage kesehatan mental (akan dilihat konselor)</h2>
            <div class="mt-2 grid gap-2 sm:grid-cols-3 text-sm">
                <div class="rounded-lg bg-white p-2">
                    <p class="text-xs text-sage-600">Kecemasan</p>
                    <p class="font-bold text-ink-900">{{ $triage->anxiety_score }} <span class="font-normal text-slate-500">({{ $triage->levelLabel('anxiety') }})</span></p>
                </div>
                <div class="rounded-lg bg-white p-2">
                    <p class="text-xs text-sage-600">Depresi</p>
                    <p class="font-bold text-ink-900">{{ $triage->depression_score }} <span class="font-normal text-slate-500">({{ $triage->levelLabel('depression') }})</span></p>
                </div>
                <div class="rounded-lg bg-white p-2">
                    <p class="text-xs text-sage-600">Stres</p>
                    <p class="font-bold text-ink-900">{{ $triage->stress_score }} <span class="font-normal text-slate-500">({{ $triage->levelLabel('stress') }})</span></p>
                </div>
            </div>
            @if($triage->needs_priority)
                <p class="mt-2 text-xs text-rose-800 font-medium">⚠ Perlu perhatian prioritas</p>
            @endif
        </div>
    @endif
@endif

<div class="grid gap-6 md:grid-cols-5">
    <div class="rounded-3xl border border-ink-100 bg-white p-6 md:col-span-3">@include('partials.calendar')</div>

    <div class="rounded-3xl bg-ink-50 p-6 md:col-span-2">
        @if($selected)
            <h2 class="text-xl font-bold">{{ $selected->locale('id')->translatedFormat('l, d F Y') }}</h2>
            <form method="POST" action="{{ route('student.booking.store') }}" class="mt-4">@csrf
                <input type="hidden" name="tanggal" value="{{ $selected->toDateString() }}">
                <div class="grid grid-cols-2 gap-2">
                    @foreach($slots as $s)
                        <label class="rounded-xl border px-3 py-3 text-center {{ $s->booked ? 'border-ink-100 bg-white/50 text-slate-400' : 'cursor-pointer border-ink-200 bg-white has-[:checked]:border-ink-800 has-[:checked]:bg-ink-800 has-[:checked]:text-white' }}">
                            <input type="radio" name="slot_id" value="{{ $s->id }}" class="sr-only" @disabled($s->booked) required>
                            <span class="block font-display text-lg font-bold {{ $s->booked ? 'line-through' : '' }}">{{ $s->start }}</span>
                            <span class="block text-xs">{{ $s->booked ? 'Terisi' : 's.d. '.$s->end }}</span>
                        </label>
                    @endforeach
                </div>
                @if($slots->contains(fn ($s) => ! $s->booked))
                    <button class="btn btn-primary mt-5 w-full">Kirim permintaan sesi</button>
                    <p class="mt-2 text-xs text-slate-500">Sesi baru pasti setelah konselor mengonfirmasi.</p>
                @else
                    <p class="mt-4 text-sm text-slate-500">Semua slot pada tanggal ini sudah terisi. Pilih tanggal lain.</p>
                @endif
            </form>
        @else
            <h2 class="text-xl font-bold">Jam yang tersedia</h2>
            <p class="mt-2 text-sm text-slate-600">Klik tanggal berwarna hijau, kuning, atau merah di kalender untuk melihat jamnya.</p>
        @endif
    </div>
</div>
@endsection
