@extends('layouts.app')
@section('title', 'Ringkasan admin')
@section('content')
<h1 class="mb-5 text-3xl font-bold">Ringkasan</h1>
<div class="grid gap-4 md:grid-cols-4">
    <div class="card"><div class="text-sm text-slate-500">Mahasiswa</div><div class="text-3xl font-semibold">{{ $students }}</div></div>
    <div class="card"><div class="text-sm text-slate-500">Konselor aktif</div><div class="text-3xl font-semibold">{{ $counselors }}</div></div>
    <a href="{{ route('admin.counselors') }}" class="card hover:border-ink-400"><div class="text-sm text-slate-500">Menunggu persetujuan</div><div class="text-3xl font-semibold">{{ $waiting }}</div></a>
    <div class="card"><div class="text-sm text-slate-500">Rata-rata rating</div><div class="text-3xl font-semibold">{{ $avg ?: '-' }}</div></div>
</div>
<div class="card mt-5">
    <h2 class="font-semibold">Booking menurut status ({{ $bookings }} total)</h2>
    <ul class="mt-3 grid gap-2 text-sm md:grid-cols-5">
        @foreach(\App\Models\Booking::STATUS_LABEL as $k => $label)
            <li class="rounded-lg bg-slate-50 px-3 py-2">{{ $label }}: <strong>{{ $byStatus[$k] ?? 0 }}</strong></li>
        @endforeach
    </ul>
</div>
@endsection
