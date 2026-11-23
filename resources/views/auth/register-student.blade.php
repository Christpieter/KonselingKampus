@extends('layouts.auth')
@section('title', 'Daftar mahasiswa')
@section('aside')
    <h1 class="font-display text-3xl font-bold leading-tight text-white">Daftar sebagai mahasiswa</h1>
    <p class="mt-4 text-ink-100">Dengan akun ini Anda bisa:</p>
    <ul class="mt-4 space-y-3 text-sm text-ink-100">
        <li>melihat profil, sertifikasi, dan rating konselor,</li>
        <li>memilih jam sesi dari kalender yang menandai slot kosong,</li>
        <li>membatalkan sesi dan memberi rating setelah sesi selesai.</li>
    </ul>
@endsection
@section('form')
    <form method="POST" action="{{ route('register.student') }}" class="mx-auto max-w-sm space-y-4">@csrf
        <div><label class="label">Nama lengkap</label><input class="input" name="name" value="{{ old('name') }}" required></div>
        <div><label class="label">NIM</label><input class="input" name="nim" value="{{ old('nim') }}" required></div>
        <div><label class="label">Nomor HP (opsional)</label><input class="input" name="phone" value="{{ old('phone') }}"></div>
        <div><label class="label">Email</label><input class="input" type="email" name="email" value="{{ old('email') }}" required></div>
        <div><label class="label">Kata sandi (min. 8 karakter)</label><input class="input" type="password" name="password" required></div>
        <div><label class="label">Ulangi kata sandi</label><input class="input" type="password" name="password_confirmation" required></div>
        <button class="btn btn-primary w-full">Buat akun</button>
    </form>
@endsection
