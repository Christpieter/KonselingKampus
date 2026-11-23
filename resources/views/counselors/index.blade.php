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

<div class="divide-y divide-ink-100 overflow-hidden rounded-3xl border border-ink-100 bg-white">
    @forelse($profiles as $p)
        <article class="grid gap-4 p-6 md:grid-cols-12 md:items-center">
            <div class="flex gap-4 md:col-span-7">
                @include('partials.avatar', ['name' => $p->user->name, 'size' => 'h-14 w-14 text-xl'])
                <div>
                    <h2 class="text-xl font-bold"><a class="hover:underline" href="{{ route('counselors.show', $p) }}">{{ $p->user->name }}</a></h2>
                    <div class="text-sm text-slate-600">Lulusan {{ $p->graduate_field }} &middot; {{ $p->certifications->count() }} sertifikasi</div>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach($p->specializationList() as $s)<span class="badge bg-ink-50 text-ink-700">{{ $s }}</span>@endforeach
                    </div>
                </div>
            </div>
            <div class="md:col-span-2">@include('partials.stars', ['avg' => $p->avg, 'count' => $p->count])</div>
            <div class="flex gap-2 md:col-span-3 md:justify-end">
                <a class="btn" href="{{ route('counselors.show', $p) }}">Profil</a>
                @if(!auth()->check() || auth()->user()->isStudent())
                    <a class="btn btn-primary" href="{{ route('student.booking.start', ['konselor' => $p->user_id]) }}">Booking</a>
                @endif
            </div>
        </article>
    @empty
        <p class="p-8 text-center text-slate-500">Belum ada konselor yang cocok dengan pencarian ini.</p>
    @endforelse
</div>
@endsection
