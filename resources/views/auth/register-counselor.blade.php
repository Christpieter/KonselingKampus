@extends('layouts.auth')
@section('title', 'Daftar konselor')
@section('aside')
    <h1 class="font-display text-3xl font-bold leading-tight text-white">Daftar sebagai konselor</h1>
    <p class="mt-4 text-ink-100">Admin memeriksa pendaftaran Anda sebelum profil tampil di daftar konselor. Yang perlu disiapkan:</p>
    <ul class="mt-4 space-y-3 text-sm text-ink-100">
        <li>bidang lulusan dan pendidikan terakhir,</li>
        <li>keahlian yang ingin Anda tampilkan,</li>
        <li>sertifikasi yang Anda punya (maksimal 3 di formulir ini, sisanya bisa ditambah di profil).</li>
    </ul>
    <p class="mt-6 text-sm text-ink-200">Slot jadwal mingguan diatur setelah Anda masuk.</p>
@endsection
@section('form')
    <form method="POST" action="{{ route('register.counselor') }}" class="space-y-4">@csrf
        <div class="grid gap-4 md:grid-cols-2">
            <div><label class="label">Nama lengkap (beserta gelar)</label><input class="input" name="name" value="{{ old('name') }}" required></div>
            <div><label class="label">Nomor HP (opsional)</label><input class="input" name="phone" value="{{ old('phone') }}"></div>
            <div class="md:col-span-2"><label class="label">Email</label><input class="input" type="email" name="email" value="{{ old('email') }}" required></div>
            <div><label class="label">Kata sandi (min. 8 karakter)</label><input class="input" type="password" name="password" required></div>
            <div><label class="label">Ulangi kata sandi</label><input class="input" type="password" name="password_confirmation" required></div>
        </div>
        <hr class="border-ink-100">
        <div class="grid gap-4 md:grid-cols-2">
            <div><label class="label">Lulusan bidang</label><input class="input" name="graduate_field" value="{{ old('graduate_field') }}" placeholder="mis. Psikologi, Bimbingan dan Konseling" required></div>
            <div><label class="label">Pendidikan terakhir dan kampus</label><input class="input" name="education" value="{{ old('education') }}" required></div>
        </div>
        <div><label class="label">Keahlian (pisahkan dengan koma)</label><input class="input" name="specializations" value="{{ old('specializations') }}" placeholder="mis. Stres dan Kecemasan, Karier, Akademik" required></div>
        <div><label class="label">Tentang Anda (opsional)</label><textarea class="input" rows="4" name="bio">{{ old('bio') }}</textarea></div>
        <div>
            <div class="label">Sertifikasi</div>
            @for($i = 0; $i < 3; $i++)
                <div class="mb-2 grid gap-2 md:grid-cols-6">
                    <input class="input md:col-span-3" name="certs[{{ $i }}][name]" value="{{ old("certs.$i.name") }}" placeholder="Nama sertifikasi">
                    <input class="input md:col-span-2" name="certs[{{ $i }}][issuer]" value="{{ old("certs.$i.issuer") }}" placeholder="Penerbit">
                    <input class="input" name="certs[{{ $i }}][year]" value="{{ old("certs.$i.year") }}" placeholder="Tahun">
                </div>
            @endfor
        </div>
        <button class="btn btn-primary w-full">Kirim pendaftaran</button>
    </form>
@endsection
