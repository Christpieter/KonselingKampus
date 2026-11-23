@extends('layouts.app')
@section('title', 'Jadwal mingguan')
@section('content')
<h1 class="mb-1 text-3xl font-bold">Jadwal mingguan</h1>
<p class="mb-5 text-sm text-slate-600">Setiap slot berulang tiap pekan pada hari yang dipilih. Mahasiswa hanya bisa memesan slot ini, paling cepat untuk besok.</p>
<div class="grid gap-5 md:grid-cols-3">
    <form method="POST" action="{{ route('counselor.slot.add') }}" class="card h-fit space-y-3">@csrf
        <h2 class="font-semibold">Tambah slot</h2>
        <div><label class="label">Hari</label>
            <select name="day_of_week" class="input">@foreach(\App\Models\Availability::DAYS as $n => $d)<option value="{{ $n }}" @selected(old('day_of_week') == $n)>{{ $d }}</option>@endforeach</select></div>
        <div class="grid grid-cols-2 gap-2">
            <div><label class="label">Mulai</label><input type="time" name="start_time" class="input" value="{{ old('start_time') }}" required></div>
            <div><label class="label">Selesai</label><input type="time" name="end_time" class="input" value="{{ old('end_time') }}" required></div>
        </div>
        <button class="btn btn-primary w-full">Tambah</button>
    </form>
    <div class="space-y-3 md:col-span-2">
        @foreach(\App\Models\Availability::DAYS as $n => $d)
            <div class="card py-3">
                <div class="font-medium">{{ $d }}</div>
                <div class="mt-2 flex flex-wrap gap-2">
                    @forelse($slots->get($n, collect()) as $s)
                        <form method="POST" action="{{ route('counselor.slot.remove', $s) }}" class="flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-1 text-sm">
                            @csrf @method('DELETE')
                            {{ substr($s->start_time, 0, 5) }} - {{ substr($s->end_time, 0, 5) }}
                            <button class="text-rose-600 hover:text-rose-800" title="Hapus slot">&times;</button>
                        </form>
                    @empty
                        <span class="text-sm text-slate-400">Tidak ada slot.</span>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
