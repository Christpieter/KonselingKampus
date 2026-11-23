@extends('layouts.app')
@section('title', 'Pilih konselor')
@section('content')
@include('partials.stepper', ['step' => 2])
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-3xl font-bold">Pilih konselor</h1>
        <p class="mt-2 max-w-xl text-sm text-slate-600">Konselor dengan slot kosong tampil lebih dulu. Label "Sesuai" berarti keahlian atau bidangnya memuat tipe masalah Anda ({{ $type?->name }}).</p>
    </div>
    <a class="text-sm font-semibold text-ink-700 underline underline-offset-4" href="{{ route('student.booking.intake') }}">Ubah data awal</a>
</div>

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

<div class="divide-y divide-ink-100 overflow-hidden rounded-3xl border border-ink-100 bg-white">
    @forelse($profiles as $p)
        <article class="grid gap-4 p-6 md:grid-cols-12 md:items-center {{ $p->nextOpen ? '' : 'opacity-60' }}">
            <div class="flex gap-4 md:col-span-7">
                @include('partials.avatar', ['name' => $p->user->name, 'size' => 'h-14 w-14 text-xl'])
                <div>
                    <h2 class="text-xl font-bold"><a class="hover:underline" target="_blank" href="{{ route('counselors.show', $p) }}">{{ $p->user->name }}</a>
                        @if($p->match)<span class="badge ml-1 bg-sage-200 align-middle text-sage-900">Sesuai</span>@endif</h2>
                    <div class="text-sm text-slate-600">Lulusan {{ $p->graduate_field }} &middot; {{ $p->certifications->count() }} sertifikasi</div>
                    <div class="mt-2 flex flex-wrap gap-1.5">@foreach($p->specializationList() as $s)<span class="badge bg-ink-50 text-ink-700">{{ $s }}</span>@endforeach</div>
                </div>
            </div>
            <div class="text-sm md:col-span-3">
                <div>@include('partials.stars', ['avg' => $p->avg, 'count' => $p->count])</div>
                <div class="mt-1 {{ $p->nextOpen ? 'font-semibold text-sage-700' : 'text-slate-500' }}">
                    {{ $p->nextOpen ? 'Kosong terdekat: '.$p->nextOpen->locale('id')->translatedFormat('d F Y') : 'Belum ada slot kosong dalam 2 bulan ke depan' }}
                </div>
            </div>
            <div class="md:col-span-2 md:text-right">
                @if($p->nextOpen)
                    <form method="POST" action="{{ route('student.booking.counselors.pick') }}">@csrf
                        <input type="hidden" name="counselor_id" value="{{ $p->user_id }}">
                        <button class="btn btn-primary">Pilih</button>
                    </form>
                @endif
            </div>
        </article>
    @empty
        <p class="p-8 text-center text-slate-500">Belum ada konselor yang disetujui admin.</p>
    @endforelse
</div>
@endsection
