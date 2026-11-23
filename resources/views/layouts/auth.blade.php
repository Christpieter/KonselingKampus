@extends('layouts.app')
@section('content')
<div class="grid overflow-hidden rounded-[2rem] border border-ink-100 bg-white md:grid-cols-5">
    <aside class="bg-ink-800 p-8 text-white md:col-span-2 md:p-10">@yield('aside')</aside>
    <div class="p-8 md:col-span-3 md:p-10">@yield('form')</div>
</div>
@endsection
