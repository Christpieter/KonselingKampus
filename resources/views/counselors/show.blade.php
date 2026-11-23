@extends('layouts.app')
@section('title', $profile->user->name)
@section('content')
<a href="{{ route('counselors.index') }}" class="text-sm font-semibold text-ink-700 underline underline-offset-4">&larr; Semua konselor</a>

<header class="mt-4 flex flex-wrap items-center gap-6 rounded-[2rem] bg-ink-800 p-8 text-white md:p-10">
    @include('partials.avatar', ['name' => $profile->user->name, 'size' => 'h-24 w-24 text-4xl'])
    <div>
        <h1 class="text-3xl font-bold text-white md:text-4xl">{{ $profile->user->name }}</h1>
        <p class="mt-1 text-ink-100">Lulusan {{ $profile->graduate_field }}</p>
        <div class="mt-2 rounded-full bg-white/10 px-3 py-1 text-sm text-white inline-block">@include('partials.stars', ['avg' => $avg, 'count' => $count])</div>
    </div>
</header>

<div class="mt-8 grid gap-8 md:grid-cols-3">
    <div class="space-y-8 md:col-span-2">
        @if($profile->bio)
            <section><h2 class="text-xl font-bold">Tentang</h2><p class="mt-2 max-w-prose whitespace-pre-line leading-relaxed text-slate-700">{{ $profile->bio }}</p></section>
        @endif
        <section class="card">
            <h2 class="text-xl font-bold">Latar belakang</h2>
            <dl class="mt-4 grid gap-4 text-sm md:grid-cols-2">
                <div><dt class="text-slate-500">Lulusan bidang</dt><dd class="font-semibold">{{ $profile->graduate_field }}</dd></div>
                <div><dt class="text-slate-500">Pendidikan</dt><dd class="font-semibold">{{ $profile->education }}</dd></div>
            </dl>
            <h3 class="mt-5 text-sm font-semibold text-slate-500">Keahlian</h3>
            <div class="mt-2 flex flex-wrap gap-1.5">@foreach($profile->specializationList() as $s)<span class="badge bg-ink-50 text-ink-700">{{ $s }}</span>@endforeach</div>
            <h3 class="mt-5 text-sm font-semibold text-slate-500">Sertifikasi</h3>
            <ul class="mt-2 space-y-1 text-sm">
                @forelse($profile->certifications as $c)
                    <li>{{ $c->name }}@if($c->issuer), {{ $c->issuer }}@endif @if($c->year)({{ $c->year }})@endif</li>
                @empty
                    <li class="text-slate-400">Belum ada sertifikasi tercatat.</li>
                @endforelse
            </ul>
        </section>
        <section>
            <h2 class="text-xl font-bold">Ulasan mahasiswa</h2>
            <div class="mt-3 divide-y divide-ink-100 rounded-2xl border border-ink-100 bg-white">
                @forelse($ratings as $r)
                    <div class="p-5">
                        <span class="text-butter-500">{{ str_repeat('★', $r->stars) }}</span><span class="text-slate-300">{{ str_repeat('★', 5 - $r->stars) }}</span>
                        <span class="ml-2 text-xs text-slate-500">{{ $r->created_at->format('d M Y') }}</span>
                        @if($r->comment)<p class="mt-1 text-sm text-slate-700">{{ $r->comment }}</p>@endif
                    </div>
                @empty
                    <p class="p-5 text-sm text-slate-400">Belum ada ulasan.</p>
                @endforelse
            </div>
        </section>
    </div>
    <aside class="h-fit rounded-3xl bg-sage-100 p-6 md:sticky md:top-24">
        <h2 class="text-xl font-bold text-sage-900">Booking sesi</h2>
        <p class="mt-2 text-sm text-sage-900/80">
            @if($nextOpen) Slot kosong terdekat: <strong>{{ $nextOpen->locale('id')->translatedFormat('l, d F Y') }}</strong>. @else Belum ada slot kosong dalam 2 bulan ke depan. @endif
        </p>
        @if(!auth()->check() || auth()->user()->isStudent())
            <a class="btn btn-primary mt-5 w-full" href="{{ route('student.booking.start', ['konselor' => $profile->user_id]) }}">Booking konselor ini</a>
            @guest<p class="mt-2 text-xs text-sage-900/70">Anda perlu masuk sebagai mahasiswa untuk melanjutkan.</p>@endguest
        @endif
    </aside>
</div>
@endsection
