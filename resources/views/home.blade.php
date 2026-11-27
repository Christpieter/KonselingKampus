@extends('layouts.app')
@section('title', 'Konseling Kampus')
@section('content')
@php
    $done = (int) ($byStatus['completed'] ?? 0);
    $booked = (int) ($byStatus['confirmed'] ?? 0);
    $waiting = (int) ($byStatus['pending'] ?? 0);
    $active = $done + $booked + $waiting;
    $donePct = $active > 0 ? round($done / $active * 100) : 0;
    $maxType = (int) ($byType->max('total') ?? 1);
@endphp

<section class="relative overflow-hidden rounded-[2rem] bg-ink-800 px-6 py-14 text-white md:px-14 md:py-20">
    <div class="absolute -right-20 -top-24 h-72 w-72 rounded-full bg-sage-300/20 blur-3xl" aria-hidden="true"></div>
    <div class="relative max-w-3xl">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-sage-200">Untuk kamu, apa pun yang sedang kamu rasakan</p>
        <h1 class="mt-5 font-display text-4xl font-bold leading-tight tracking-tight text-white md:text-6xl">Kamu tidak harus melewati semuanya sendirian.</h1>
        <p class="mt-6 max-w-2xl text-lg leading-relaxed text-ink-100">
            Ada hari ketika tugas, relasi, masa depan, atau rasa lelah datang bersamaan. Kamu tak perlu punya semua jawabannya sekarang. Mulai saja dengan memberi ruang pada dirimu untuk didengar.
        </p>
        <a href="#bergabung" class="btn mt-8 border-white bg-white text-ink-900 hover:bg-ink-50">Temukan langkah pertamamu</a>
    </div>
</section>

<section class="mx-auto mt-20 max-w-3xl text-center">
    <p class="font-display text-2xl font-semibold leading-relaxed text-ink-800 md:text-3xl">
        “Kamu boleh datang dengan cerita yang belum selesai, perasaan yang belum punya nama, atau hanya keinginan untuk bercerita.”
    </p>
    <p class="mx-auto mt-6 max-w-2xl leading-relaxed text-slate-600">
        Apa yang kamu rasakan tetap berarti, sekecil atau sebesar apa pun. Mengulurkan tangan bukan tanda lemah, itu bentuk perhatian pada diri sendiri.
    </p>
</section>



{{-- Statistik platform --}}
<section class="mt-20" aria-labelledby="angka">
    <h2 id="angka" class="text-3xl font-bold">Angka di platform ini</h2>
    <p class="mt-2 text-slate-600">Dihitung langsung dari data yang ada saat halaman dibuka.</p>

    <dl class="mt-6 grid grid-cols-2 divide-ink-100 overflow-hidden rounded-3xl border border-ink-100 bg-white md:grid-cols-4 md:divide-x">
        <div class="p-6"><dt class="text-sm text-slate-500">Konselor aktif</dt><dd class="mt-1 font-display text-3xl font-bold">{{ $counselorCount }}</dd></div>
        <div class="p-6"><dt class="text-sm text-slate-500">Mahasiswa terdaftar</dt><dd class="mt-1 font-display text-3xl font-bold">{{ $studentCount }}</dd></div>
        <div class="p-6"><dt class="text-sm text-slate-500">Sesi selesai</dt><dd class="mt-1 font-display text-3xl font-bold">{{ $done }}</dd></div>
        <div class="p-6"><dt class="text-sm text-slate-500">Rata-rata rating</dt><dd class="mt-1 font-display text-3xl font-bold">{{ $ratingCount ? number_format($avgRating, 1) : '-' }}</dd>
            @if($ratingCount)<div class="text-xs text-slate-500">dari {{ $ratingCount }} rating</div>@endif</div>
    </dl>

    <div class="mt-4 grid gap-4 md:grid-cols-12">
        <div class="card md:col-span-7">
            <h3 class="text-lg font-bold">Tipe masalah yang diajukan</h3>
            @if($total >= 5)
                <ul class="mt-5 space-y-3">
                    @foreach($byType as $row)
                        <li>
                            <div class="mb-1 flex justify-between text-sm"><span>{{ $row->name }}</span><span class="text-slate-500">{{ $row->total }} sesi ({{ round($row->total / $total * 100) }}%)</span></div>
                            <div class="h-2.5 rounded-full bg-ink-50"><div class="h-2.5 rounded-full bg-ink-600" style="width: {{ round($row->total / $maxType * 100) }}%"></div></div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mt-4 text-sm text-slate-500">Rincian per tipe baru ditampilkan setelah ada minimal 5 sesi, supaya tidak ada sesi yang bisa dikenali dari angkanya.</p>
            @endif
        </div>

        <div class="card md:col-span-5">
            <h3 class="text-lg font-bold">Status sesi</h3>
            @if($active > 0)
                <div class="mt-5 flex items-center gap-6">
                    <div class="relative h-32 w-32 shrink-0 rounded-full" style="background: conic-gradient(#43438F 0 {{ $donePct }}%, #E2E2F4 0)">
                        <div class="absolute inset-4 grid place-items-center rounded-full bg-white text-center">
                            <div><div class="font-display text-2xl font-bold leading-6">{{ $donePct }}%</div><div class="text-[11px] text-slate-500">selesai</div></div>
                        </div>
                    </div>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-ink-600"></span>Selesai ({{ $done }})</li>
                        <li class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-ink-100"></span>Terjadwal ({{ $booked }})</li>
                        <li class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-ink-100"></span>Menunggu konfirmasi ({{ $waiting }})</li>
                    </ul>
                </div>
                <p class="mt-4 text-xs text-slate-500">Sesi yang ditolak atau dibatalkan tidak dihitung.</p>
            @else
                <p class="mt-4 text-sm text-slate-500">Belum ada sesi. Angka muncul setelah mahasiswa pertama membuat sesi.</p>
            @endif
        </div>
    </div>
</section>

<section id="bergabung" class="mt-20 scroll-mt-24" aria-labelledby="bergabung-title">
    <div class="mx-auto max-w-2xl text-center">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-ink-600">Mari mulai dari sini</p>
        <h2 id="bergabung-title" class="mt-3 text-3xl font-bold md:text-4xl">Pilih cara kamu ingin hadir</h2>
        <p class="mt-4 leading-relaxed text-slate-600">Baik sedang mencari ruang bercerita maupun ingin mendampingi orang lain, ada tempat untukmu.</p>
    </div>

    <br>




    <div class="mt-8 grid gap-5 md:grid-cols-2">
        <article class="flex flex-col rounded-3xl border border-ink-100 bg-white p-7 shadow-sm md:p-9">
            <div class="grid h-12 w-12 place-items-center rounded-2xl bg-ink-50 text-ink-700" aria-hidden="true">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8m-8 4h5m-8 6 2.4-2.4A3 3 0 0 1 9.5 17H17a3 3 0 0 0 3-3V6a3 3 0 0 0-3-3H7a3 3 0 0 0-3 3v14Z"/></svg>
            </div>
            <h3 class="mt-5 text-2xl font-bold">Untuk mahasiswa</h3>
            <p class="mt-3 flex-1 leading-relaxed text-slate-600">Ingin punya ruang untuk bercerita dan mencari dukungan? Daftar sebagai mahasiswa, lalu mulai dengan langkah yang terasa nyaman bagimu.</p>
            <a href="{{ route('register.student') }}" class="btn btn-primary mt-6 w-full">Daftar sebagai mahasiswa</a>
        </article>

        <article class="flex flex-col rounded-3xl border border-sage-200 bg-sage-50 p-7 shadow-sm md:p-9">
            <div class="grid h-12 w-12 place-items-center rounded-2xl bg-white text-sage-800" aria-hidden="true">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-8-4.4-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 6.6-8 11-8 11Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h2l1.2-2.5L13 15l1.2-3H16"/></svg>
            </div>
            <h3 class="mt-5 text-2xl font-bold">Untuk konselor</h3>
            <p class="mt-3 flex-1 leading-relaxed text-slate-600">Siap menjadi pendengar yang hadir dengan empati dan keahlianmu? Daftar sebagai konselor dan ikut menemani perjalanan mahasiswa.</p>
            <a href="{{ route('register.counselor') }}" class="btn mt-6 w-full border-sage-700 bg-sage-700 text-white hover:bg-sage-800">Daftar sebagai konselor</a>
        </article>
    </div>
</section>
@endsection
