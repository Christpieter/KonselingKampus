@extends('layouts.app')
@section('title', 'Permintaan sesi')
@section('content')
@if($profile->status !== 'approved')
    <div class="mb-5 rounded-lg border border-butter-200 bg-butter-50 px-4 py-3 text-sm text-butter-900">
        @if($profile->status === 'pending') Profil Anda sedang diperiksa admin. Selama itu, Anda belum tampil di daftar konselor. @else Pendaftaran Anda ditolak admin. Perbarui profil, lalu hubungi admin. @endif
    </div>
@endif
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-3xl font-bold">Permintaan sesi</h1>
    <div>@include('partials.stars', ['avg' => $avg, 'count' => $ratingCount])</div>
</div>

<h2 class="mb-2 font-semibold">Menunggu konfirmasi ({{ $pending->count() }})</h2>
<div class="card mb-8 overflow-x-auto p-0">
    <table class="w-full text-sm">
        <thead><tr><th>Tanggal</th><th>Mahasiswa</th><th>Tipe</th><th>Skor</th><th></th></tr></thead>
        <tbody>
        @forelse($pending as $b)
            <tr class="border-t border-slate-100">
                <td>{{ $b->session_date->format('d/m/Y') }}<br><span class="text-slate-500">{{ $b->timeRange() }}</span></td>
                <td>{{ $b->student->name }}<br><span class="text-slate-500">{{ $b->student->nim }}</span></td>
                <td>{{ $b->problemType->name }}</td>
                <td>{{ $b->score }}/30 ({{ \App\Models\Booking::scoreLevel($b->score) }})
                    @if($b->needs_priority)<span class="badge ml-1 bg-rose-100 text-rose-800">Prioritas</span>@endif</td>
                <td><a class="btn" href="{{ route('counselor.booking', $b) }}">Buka</a></td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-slate-400">Tidak ada permintaan baru.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<h2 class="mb-2 font-semibold">Sesi terkonfirmasi ({{ $upcoming->count() }})</h2>
<div class="card mb-8 overflow-x-auto p-0">
    <table class="w-full text-sm">
        <thead><tr><th>Tanggal</th><th>Mahasiswa</th><th>Tipe</th><th></th></tr></thead>
        <tbody>
        @forelse($upcoming as $b)
            <tr class="border-t border-slate-100">
                <td>{{ $b->session_date->format('d/m/Y') }}<br><span class="text-slate-500">{{ $b->timeRange() }}</span></td>
                <td>{{ $b->student->name }}</td><td>{{ $b->problemType->name }}</td>
                <td>
                    <div class="flex flex-wrap justify-end gap-2">
                        <a class="btn" href="{{ route('counselor.booking', $b) }}">Buka</a>
                        <a class="btn btn-primary" href="{{ route('counselor.chat.index', $b) }}">
                            Chat
                            @if(($b->unread_count ?? 0) > 0)
                                <span class="badge ml-2 bg-rose-100 text-rose-800" aria-label="{{ $b->unread_count }} pesan belum dibaca">{{ $b->unread_count }}</span>
                            @endif
                        </a>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-slate-400">Belum ada sesi terkonfirmasi.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<h2 class="mb-2 font-semibold">Riwayat terakhir</h2>
<div class="card overflow-x-auto p-0">
    <table class="w-full text-sm">
        <tbody>
        @forelse($history as $b)
            <tr class="border-t border-slate-100 first:border-0">
                <td>{{ $b->session_date->format('d/m/Y') }} {{ $b->timeRange() }}</td><td>{{ $b->student->name }}</td><td>{{ $b->statusLabel() }}</td>
                <td>
                    <div class="flex flex-col items-start gap-2">
                        <a class="underline" href="{{ route('counselor.booking', $b) }}">Lihat</a>
                        @if($b->status === 'completed')
                            @include('counselor.partials.report-actions', ['booking' => $b])
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td class="text-center text-slate-400">Belum ada riwayat.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection