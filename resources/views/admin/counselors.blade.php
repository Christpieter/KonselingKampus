@extends('layouts.app')
@section('title', 'Konselor')
@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-3xl font-bold">Pendaftar dan konselor</h1>
    <div class="flex gap-2 text-sm">
        @foreach(['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'semua' => 'Semua'] as $k => $l)
            <a class="btn {{ $status === $k ? 'btn-primary' : '' }}" href="{{ route('admin.counselors', ['status' => $k]) }}">{{ $l }}</a>
        @endforeach
    </div>
</div>
<div class="space-y-4">
    @forelse($profiles as $p)
        <div class="card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="font-semibold">{{ $p->user->name }} <span class="badge bg-slate-100 text-slate-600">{{ $p->status }}</span></div>
                    <div class="text-sm text-slate-600">{{ $p->user->email }}@if($p->user->phone) &middot; {{ $p->user->phone }}@endif</div>
                    <div class="mt-2 text-sm">Lulusan {{ $p->graduate_field }} &middot; {{ $p->education }}</div>
                    <div class="text-sm">Keahlian: {{ $p->specializations }}</div>
                    <div class="mt-1 text-sm">Sertifikasi:
                        @forelse($p->certifications as $c){{ $c->name }}@if($c->issuer), {{ $c->issuer }}@endif @if($c->year)({{ $c->year }})@endif{{ ! $loop->last ? '; ' : '' }}@empty <span class="text-slate-400">tidak ada</span>@endforelse
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.counselors.status', $p) }}" class="flex gap-2">@csrf
                    @if($p->status !== 'approved')<button name="status" value="approved" class="btn btn-primary">Setujui</button>@endif
                    @if($p->status !== 'rejected')<button name="status" value="rejected" class="btn btn-danger">Tolak</button>@endif
                </form>
            </div>
        </div>
    @empty
        <p class="text-slate-500">Tidak ada data pada filter ini.</p>
    @endforelse
</div>
@endsection
