@extends('layouts.auth')
@section('title', 'Masuk')
@section('aside')
    <h1 class="font-display text-3xl font-bold leading-tight text-white">Masuk ke akun Anda</h1>
    <p class="mt-4 text-ink-100">Satu halaman masuk untuk semua peran. Setelah masuk, Anda diarahkan sesuai peran:</p>
    <dl class="mt-6 space-y-4 text-sm">
        <div><dt class="font-semibold text-white">Mahasiswa</dt><dd class="text-ink-200">Sesi saya dan pembuatan sesi baru</dd></div>
        <div><dt class="font-semibold text-white">Konselor</dt><dd class="text-ink-200">Permintaan sesi, jadwal mingguan, dan profil</dd></div>
        <div><dt class="font-semibold text-white">Admin</dt><dd class="text-ink-200">Persetujuan konselor dan pengelolaan akun</dd></div>
    </dl>
@endsection
@section('form')
    <form method="POST" action="{{ route('login') }}" class="mx-auto max-w-sm space-y-4">@csrf
        <div><label class="label" for="email">Email</label><input id="email" class="input" type="email" name="email" value="{{ old('email') }}" required autofocus></div>
        <div><label class="label" for="password">Kata sandi</label><input id="password" class="input" type="password" name="password" required></div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" class="rounded border-ink-300"> Ingat saya</label>
        <button class="btn btn-primary w-full">Masuk</button>
        <p class="pt-2 text-sm text-slate-600">Belum punya akun? <a class="font-semibold text-ink-700 underline underline-offset-4" href="{{ route('register.student') }}">Daftar mahasiswa</a> atau <a class="font-semibold text-ink-700 underline underline-offset-4" href="{{ route('register.counselor') }}">daftar konselor</a>.</p>
    </form>
@endsection
