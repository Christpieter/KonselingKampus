@extends('layouts.app')
@section('title', 'Booking')
@section('content')
<h1 class="mb-1 text-3xl font-bold">Booking terbaru</h1>
<p class="mb-5 text-sm text-slate-500">Menampilkan 100 terbaru. Isi cerita dan jawaban kuesioner sengaja tidak ditampilkan di sini.</p>
<div class="card overflow-x-auto p-0">
    <table class="w-full text-sm">
        <thead><tr><th>Tanggal sesi</th><th>Mahasiswa</th><th>Konselor</th><th>Tipe</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($bookings as $b)
            <tr class="border-t border-slate-100">
                <td>{{ $b->session_date->format('d/m/Y') }} {{ $b->timeRange() }}</td>
                <td>{{ $b->student->name }}</td><td>{{ $b->counselor->name }}</td>
                <td>{{ $b->problemType->name }}</td><td>{{ $b->statusLabel() }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-slate-400">Belum ada booking.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
