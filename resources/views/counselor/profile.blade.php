@extends('layouts.app')
@section('title', 'Profil konselor')
@section('content')
<h1 class="mb-5 text-3xl font-bold">Profil konselor</h1>
<div class="grid gap-5 md:grid-cols-2">
    <form method="POST" action="{{ route('counselor.profile.update') }}" class="card space-y-4">@csrf @method('PUT')
        <div><label class="label">Nama</label><input class="input" name="name" value="{{ old('name', auth()->user()->name) }}" required></div>
        <div><label class="label">Nomor HP</label><input class="input" name="phone" value="{{ old('phone', auth()->user()->phone) }}"></div>
        <div><label class="label">Lulusan bidang</label><input class="input" name="graduate_field" value="{{ old('graduate_field', $profile->graduate_field) }}" required></div>
        <div><label class="label">Pendidikan terakhir dan kampus</label><input class="input" name="education" value="{{ old('education', $profile->education) }}" required></div>
        <div><label class="label">Keahlian (pisahkan dengan koma)</label><input class="input" name="specializations" value="{{ old('specializations', $profile->specializations) }}" required></div>
        <div><label class="label">Tentang Anda</label><textarea class="input" rows="5" name="bio">{{ old('bio', $profile->bio) }}</textarea></div>
        <button class="btn btn-primary">Simpan profil</button>
    </form>

    <div class="space-y-5">
        <div class="card">
            <h2 class="mb-3 font-semibold">Sertifikasi</h2>
            <ul class="space-y-2 text-sm">
                @forelse($profile->certifications as $c)
                    <li class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2">
                        <span>{{ $c->name }}@if($c->issuer), {{ $c->issuer }}@endif @if($c->year)({{ $c->year }})@endif</span>
                        <form method="POST" action="{{ route('counselor.cert.remove', $c->id) }}" onsubmit="return confirm('Hapus sertifikasi ini?')">@csrf @method('DELETE')<button class="text-rose-600">Hapus</button></form>
                    </li>
                @empty
                    <li class="text-slate-400">Belum ada sertifikasi.</li>
                @endforelse
            </ul>
        </div>
        <form method="POST" action="{{ route('counselor.cert.add') }}" class="card space-y-3">@csrf
            <h2 class="font-semibold">Tambah sertifikasi</h2>
            <input class="input" name="name" placeholder="Nama sertifikasi" required>
            <div class="grid grid-cols-3 gap-2">
                <input class="input col-span-2" name="issuer" placeholder="Penerbit">
                <input class="input" name="year" placeholder="Tahun">
            </div>
            <button class="btn">Tambah</button>
        </form>
    </div>
</div>
@endsection
