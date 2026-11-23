@extends('layouts.app')
@section('title', 'Sesi saya')
@section('content')
<div class="mb-5 flex items-center justify-between">
    <h1 class="text-3xl font-bold">Sesi saya</h1>
    <div class="flex flex-wrap gap-2">
        <a class="btn" href="{{ route('student.companion.index') }}">Teman bicara</a>
        <a class="btn btn-primary" href="{{ route('student.booking.start') }}">Buat sesi baru</a>
    </div>
</div>
<div class="space-y-4">
    @forelse($bookings as $b)
        @php $stripe = ['pending' => 'border-l-butter-300', 'confirmed' => 'border-l-sage-400', 'completed' => 'border-l-ink-400', 'rejected' => 'border-l-rose-300', 'cancelled' => 'border-l-slate-300'][$b->status]; $color = ['pending' => 'bg-butter-100 text-butter-800', 'confirmed' => 'bg-sage-100 text-sage-800', 'completed' => 'bg-ink-100 text-ink-800', 'rejected' => 'bg-rose-100 text-rose-800', 'cancelled' => 'bg-slate-200 text-slate-700'][$b->status]; @endphp
        <div class="card border-l-4 {{ $stripe }}">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <div class="font-semibold">{{ $b->session_date->locale('id')->translatedFormat('l, d F Y') }}, {{ $b->timeRange() }}</div>
                    <div class="text-sm text-slate-600">Dengan <a class="underline" href="{{ route('counselors.show', $b->counselor->profile) }}">{{ $b->counselor->name }}</a> &middot; {{ $b->problemType->name }}</div>
                </div>
                <span class="badge {{ $color }}">{{ $b->statusLabel() }}</span>
            </div>
            @if($b->status === 'rejected' && $b->counselor_note)
                <p class="mt-3 rounded-lg bg-rose-50 p-3 text-sm text-rose-800">Alasan konselor: {{ $b->counselor_note }}</p>
            @endif
            <div class="mt-3 flex flex-wrap gap-2">
                @if(in_array($b->status, ['pending','confirmed']))
                    <form method="POST" action="{{ route('student.cancel', $b) }}" onsubmit="return confirm('Batalkan sesi ini?')">@csrf<button class="btn btn-danger">Batalkan</button></form>
                @endif
                @if($b->status === 'rejected')
                    <a class="btn" href="{{ route('student.booking.start', ['konselor' => $b->counselor_id]) }}">Pilih jadwal lain</a>
                @endif
                @if($b->status === 'confirmed')
                    <a class="btn btn-primary" href="{{ route('student.chat.index', $b) }}">Chat dengan Konselor</a>
                @endif
            </div>
            @if($b->status === 'completed')
                @if($b->rating)
                    <p class="mt-3 text-sm text-slate-600">Rating Anda: <span class="text-butter-500">{{ str_repeat('★', $b->rating->stars) }}</span>@if($b->rating->comment) &middot; {{ $b->rating->comment }}@endif</p>
                @else
                    <form method="POST" action="{{ route('student.rate', $b) }}" class="mt-4 space-y-2 border-t border-slate-100 pt-4">@csrf
                        <div class="text-sm font-medium">Beri rating untuk sesi ini</div>
                        <div class="flex gap-2">
                            @foreach([1,2,3,4,5] as $n)
                                <label class="cursor-pointer rounded-lg border border-slate-200 px-3 py-1 text-sm has-[:checked]:border-butter-500 has-[:checked]:bg-butter-50"><input type="radio" name="stars" value="{{ $n }}" required class="mr-1">{{ $n }} ★</label>
                            @endforeach
                        </div>
                        <textarea name="comment" rows="2" maxlength="600" class="input" placeholder="Komentar (opsional)"></textarea>
                        <button class="btn btn-primary">Kirim rating</button>
                    </form>
                @endif
            @endif
        </div>
    @empty
        <div class="card text-center text-slate-500">Anda belum punya sesi. <a class="text-ink-700 underline" href="{{ route('student.booking.start') }}">Buat sesi pertama</a>.</div>
    @endforelse
</div>
@endsection
