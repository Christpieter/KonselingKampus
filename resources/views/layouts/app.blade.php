<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Konseling Kampus')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..800&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
    {{-- Tailwind via CDN agar tidak perlu build. Untuk produksi, pindahkan ke Vite (lihat README). --}}
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                colors: {
                    paper: '#F6F6FB',
                    ink:    { 50:'#F1F1FA',100:'#E2E2F4',200:'#C5C5E8',300:'#9E9ED6',400:'#7676C0',500:'#5555A8',600:'#43438F',700:'#353577',800:'#2A2A5E',900:'#1E1E45' },
                    sage:   { 50:'#F2F9F0',100:'#DDEFD9',200:'#BFE0B8',300:'#98CC91',400:'#6FB46A',500:'#4C9A4F',600:'#3B7E3F',700:'#2F6334',800:'#274F2B',900:'#1E3C22' },
                    butter: { 50:'#FFFBEB',100:'#FBF0C4',200:'#F5E08C',300:'#EDCB55',400:'#E3B52D',500:'#C99A12',600:'#A67C0C',700:'#856009',800:'#6B4C08',900:'#4E3706' },
                    rose:   { 50:'#FEF3F1',100:'#FBDAD6',200:'#F4B6AE',300:'#EB8F85',400:'#DE6A5E',500:'#C94D41',600:'#AB3A30',700:'#8C2F27',800:'#70261F',900:'#521B16' },
                },
                fontFamily: {
                    display: ['"Bricolage Grotesque"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    sans: ['Figtree', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                },
            } },
        };
    </script>
    <style type="text/tailwindcss">
        @layer base {
            h1, h2, h3 { @apply font-display tracking-tight text-ink-900; }
            :focus-visible { @apply outline-none ring-2 ring-ink-500 ring-offset-2 ring-offset-paper; }
        }
        @layer components {
            .btn { @apply inline-flex items-center justify-center rounded-full border border-ink-200 bg-white px-5 py-2.5 text-sm font-semibold text-ink-900 transition-colors hover:bg-ink-50; }
            .btn-primary { @apply border-ink-800 bg-ink-800 text-white hover:bg-ink-700; }
            .btn-danger { @apply border-rose-300 bg-white text-rose-700 hover:bg-rose-50; }
            .card { @apply rounded-2xl border border-ink-100 bg-white p-6; }
            .input { @apply w-full rounded-xl border border-ink-200 bg-white px-3.5 py-2.5 text-sm placeholder:text-slate-400 focus:border-ink-500 focus:outline-none focus:ring-2 focus:ring-ink-300; }
            .label { @apply mb-1.5 block text-sm font-semibold text-ink-900; }
            .badge { @apply inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold; }
            th { @apply text-left text-xs font-semibold text-slate-500; }
            td, th { @apply px-4 py-3 align-top; }
        }
    </style>
    <style>
        @keyframes cal-in { from { opacity: 0; transform: scale(.82); } to { opacity: 1; transform: none; } }
        .cal-in { animation: cal-in .45s cubic-bezier(.2,.8,.2,1) both; }
        @media (prefers-reduced-motion: reduce) { .cal-in { animation: none; } }
    </style>
</head>
<body class="flex min-h-screen flex-col bg-paper font-sans text-ink-900 antialiased">
<header class="sticky top-0 z-20 border-b border-ink-100 bg-paper/90 backdrop-blur">
    <nav class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3 text-sm font-medium">
        <a href="{{ route('home') }}" class="flex items-center gap-2 font-display text-lg font-bold text-ink-900">
            <svg width="30" height="30" viewBox="0 0 30 30" aria-hidden="true"><circle cx="11" cy="15" r="9" fill="#43438F"/><circle cx="19" cy="15" r="9" fill="#98CC91" fill-opacity=".9"/></svg>
            Konseling Kampus
        </a>
        <a href="{{ route('counselors.index') }}" class="{{ request()->routeIs('counselors.*') ? 'text-ink-900 underline decoration-2 underline-offset-8' : 'text-slate-600 hover:text-ink-900' }}">Lihat daftar konselor</a>
        <span class="flex-1"></span>
        @auth
            @if(auth()->user()->isStudent())
                <a href="{{ route('student.dashboard') }}" class="{{ request()->routeIs('student.dashboard', 'student.chat.*') ? 'text-ink-900 underline decoration-2 underline-offset-8' : 'text-slate-600 hover:text-ink-900' }}">Sesi saya</a>
                <a href="{{ route('student.companion.index') }}" class="{{ request()->routeIs('student.companion.*') ? 'text-ink-900 underline decoration-2 underline-offset-8' : 'text-slate-600 hover:text-ink-900' }}">Teman bicara</a>
                <a href="{{ route('student.booking.start') }}" class="btn btn-primary">Buat sesi baru</a>
            @elseif(auth()->user()->isCounselor())
                <a href="{{ route('counselor.dashboard') }}" class="{{ request()->routeIs('counselor.dashboard','counselor.booking','counselor.chat.*') ? 'text-ink-900 underline decoration-2 underline-offset-8' : 'text-slate-600 hover:text-ink-900' }}">Permintaan</a>
                <a href="{{ route('counselor.availability') }}" class="{{ request()->routeIs('counselor.availability') ? 'text-ink-900 underline decoration-2 underline-offset-8' : 'text-slate-600 hover:text-ink-900' }}">Jadwal</a>
                <a href="{{ route('counselor.profile') }}" class="{{ request()->routeIs('counselor.profile') ? 'text-ink-900 underline decoration-2 underline-offset-8' : 'text-slate-600 hover:text-ink-900' }}">Profil</a>
            @else
                @foreach(['admin.dashboard' => 'Ringkasan', 'admin.counselors' => 'Konselor', 'admin.users' => 'Pengguna', 'admin.types' => 'Tipe masalah', 'admin.bookings' => 'Booking'] as $r => $l)
                    <a href="{{ route($r) }}" class="{{ request()->routeIs($r) ? 'text-ink-900 underline decoration-2 underline-offset-8' : 'text-slate-600 hover:text-ink-900' }}">{{ $l }}</a>
                @endforeach
            @endif
            <details id="profile-menu" class="relative ml-2">
                <summary class="flex cursor-pointer list-none items-center rounded-full focus-visible:ring-2 focus-visible:ring-ink-500 focus-visible:ring-offset-2" aria-label="Menu profil {{ auth()->user()->name }}">
                    @include('partials.avatar', ['name' => auth()->user()->name, 'size' => 'h-10 w-10 text-sm'])
                </summary>
                <div class="absolute right-0 z-30 mt-3 w-56 overflow-hidden rounded-2xl border border-ink-100 bg-white py-2 shadow-lg">
                    <div class="border-b border-ink-100 px-4 py-2">
                        <p class="truncate text-sm font-semibold text-ink-900">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="p-2">
                        @csrf
                        <button type="submit" class="w-full rounded-xl px-3 py-2 text-left text-sm font-semibold text-rose-700 hover:bg-rose-50">Keluar</button>
                    </form>
                </div>
            </details>
        @else
            <a href="{{ route('login') }}" class="btn btn-primary">Masuk</a>
        @endauth
    </nav>
</header>

<main class="mx-auto w-full max-w-6xl flex-1 px-4 py-10">
    @if(session('ok'))<div class="mb-6 rounded-2xl border border-sage-200 bg-sage-50 px-5 py-3 text-sm text-sage-900">{{ session('ok') }}</div>@endif
    @if(session('err'))<div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-3 text-sm text-rose-800">{{ session('err') }}</div>@endif
    @if($errors->any())
        <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-3 text-sm text-rose-800">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @yield('content')
</main>

<footer class="bg-ink-900 text-ink-200">
    <div class="mx-auto flex max-w-6xl flex-wrap items-start justify-between gap-4 px-4 py-6 text-sm">
        <p class="max-w-2xl">Situs ini untuk menjadwalkan sesi konseling dan bukan layanan darurat. Bila Anda merasa dalam bahaya saat ini, hubungi layanan darurat setempat atau orang terdekat tanpa menunggu jadwal sesi.</p>
        <a href="{{ route('counselors.index') }}" class="font-semibold text-white underline underline-offset-4">Lihat daftar konselor</a>
    </div>
</footer>

@stack('scripts')
@auth
<script>
    const profileMenu = document.getElementById('profile-menu');
    document.addEventListener('click', event => {
        if (profileMenu.open && !profileMenu.contains(event.target)) profileMenu.open = false;
    });
    profileMenu.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            profileMenu.open = false;
            profileMenu.querySelector('summary').focus();
        }
    });
</script>
<script>
    document.querySelectorAll('[data-report-widget][data-status-url]').forEach(widget => {
        const message = widget.querySelector('[data-report-message]');
        const requestForm = widget.querySelector('[data-report-request]');
        const downloadLink = widget.querySelector('[data-report-download]');

        const checkReport = async () => {
            try {
                const response = await fetch(widget.dataset.statusUrl, {
                    headers: { 'Accept': 'application/json' }
                });
                if (!response.ok) throw new Error('Status rekap tidak dapat diperiksa.');
                const report = await response.json();

                if (report.status === 'completed') {
                    widget.dataset.reportState = 'completed';
                    message.textContent = 'Rekap PDF sudah tersedia.';
                    requestForm.hidden = true;
                    downloadLink.href = report.download_url;
                    downloadLink.hidden = false;
                    return true;
                }
                if (report.status === 'failed') {
                    widget.dataset.reportState = 'failed';
                    message.textContent = 'Rekap gagal dibuat. Anda dapat mencoba lagi.';
                    requestForm.hidden = false;
                    requestForm.querySelector('button').textContent = 'Coba buat ulang';
                    return true;
                }

                message.textContent = 'Rekap sedang diproses oleh n8n…';
                return false;
            } catch (error) {
                message.textContent = 'Status rekap belum dapat diperiksa. Muat ulang halaman untuk mencoba lagi.';
                return true;
            }
        };

        if (widget.dataset.reportState === 'processing') {
            let attempts = 0;
            const poll = window.setInterval(async () => {
                attempts++;
                if (await checkReport() || attempts >= 120) window.clearInterval(poll);
                if (attempts >= 120 && widget.dataset.reportState === 'processing') {
                    message.textContent = 'Rekap masih diproses. Muat ulang halaman untuk memeriksa kembali.';
                }
            }, 3000);
            checkReport();
        }
    });
</script>
@endauth
</body>
</html>