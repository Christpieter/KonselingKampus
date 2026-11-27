@extends('layouts.app')
@section('title', 'Daftar konselor')
@section('content')
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-4xl font-bold">Daftar konselor</h1>
        <p class="mt-2 text-slate-600">{{ $profiles->count() }} konselor tampil, diurutkan dari rating tertinggi.</p>
    </div>
    <form method="GET" class="flex gap-2">
        <input class="input w-64" name="cari" value="{{ $cari }}" placeholder="Nama, keahlian, atau bidang" aria-label="Cari konselor">
        <button class="btn">Cari</button>
    </form>
</div>

<div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
    @forelse($profiles as $p)
        <article class="flex h-full flex-col rounded-3xl border border-ink-100 bg-white p-6 shadow-sm transition-shadow hover:shadow-md">
            <div class="flex items-start gap-4">
                @include('partials.avatar', ['name' => $p->user->name, 'size' => 'h-16 w-16 text-2xl'])
                <div class="min-w-0 flex-1">
                    <h2 class="text-xl font-bold leading-tight">
                        <a class="hover:underline" href="{{ route('counselors.show', $p) }}">{{ $p->user->name }}</a>
                    </h2>
                    <p class="mt-1 text-sm leading-relaxed text-slate-600">Lulusan {{ $p->graduate_field }}</p>
                </div>
            </div>

            <div class="mt-5">@include('partials.stars', ['avg' => $p->avg, 'count' => $p->count])</div>

            <div class="mt-5 flex-1">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Keahlian</h3>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach($p->specializationList() as $s)<span class="badge bg-ink-50 text-ink-700">{{ $s }}</span>@endforeach
                </div>
                <p class="mt-4 text-sm text-slate-500">{{ $p->certifications->count() }} sertifikasi</p>
            </div>

            <div class="mt-6 flex gap-2 border-t border-ink-100 pt-5">
                <a class="btn flex-1" href="{{ route('counselors.show', $p) }}">Lihat profil</a>
                @if(!auth()->check() || auth()->user()->isStudent())
                    <a class="btn btn-primary flex-1" href="{{ route('student.booking.start', ['konselor' => $p->user_id]) }}">Booking</a>
                @endif
            </div>
        </article>
    @empty
        <p class="col-span-full rounded-3xl border border-ink-100 bg-white p-8 text-center text-slate-500">Belum ada konselor yang cocok dengan pencarian ini.</p>
    @endforelse
</div>
@endsection
