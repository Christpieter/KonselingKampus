@extends('layouts.app')
@section('title', 'Tipe masalah')
@section('content')
<h1 class="mb-5 text-3xl font-bold">Tipe masalah</h1>
<div class="grid gap-5 md:grid-cols-3">
    <form method="POST" action="{{ route('admin.types.store') }}" class="card h-fit space-y-3">@csrf
        <h2 class="font-semibold">Tambah tipe</h2>
        <input class="input" name="name" placeholder="Nama" required>
        <input class="input" name="description" placeholder="Keterangan singkat">
        <button class="btn btn-primary w-full">Tambah</button>
    </form>
    <div class="card overflow-x-auto p-0 md:col-span-2">
        <table class="w-full text-sm">
            <thead><tr><th>Nama</th><th>Dipakai</th><th></th></tr></thead>
            <tbody>
            @foreach($types as $t)
                <tr class="border-t border-slate-100">
                    <td>{{ $t->name }}<div class="text-xs text-slate-500">{{ $t->description }}</div></td>
                    <td>{{ $t->used }}x</td>
                    <td><form method="POST" action="{{ route('admin.types.destroy', $t) }}" onsubmit="return confirm('Hapus tipe ini?')">@csrf @method('DELETE')<button class="text-rose-600">Hapus</button></form></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
