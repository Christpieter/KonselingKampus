@extends('layouts.app')
@section('title', 'Pengguna')
@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-3xl font-bold">Pengguna</h1>
    <div class="flex gap-2 text-sm">
        @foreach(['' => 'Semua', 'mahasiswa' => 'Mahasiswa', 'konselor' => 'Konselor', 'admin' => 'Admin'] as $k => $l)
            <a class="btn {{ ($role ?? '') === $k ? 'btn-primary' : '' }}" href="{{ route('admin.users', $k ? ['peran' => $k] : []) }}">{{ $l }}</a>
        @endforeach
    </div>
</div>
<div class="card overflow-x-auto p-0">
    <table class="w-full text-sm">
        <thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach($users as $u)
            <tr class="border-t border-slate-100">
                <td>{{ $u->name }}</td><td>{{ $u->email }}</td><td>{{ $u->role }}</td>
                <td>{{ $u->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                <td>@if($u->id !== auth()->id())
                    <form method="POST" action="{{ route('admin.users.toggle', $u) }}">@csrf<button class="btn">{{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
                @endif</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
