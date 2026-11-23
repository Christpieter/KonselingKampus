@extends('layouts.app')
@section('title', 'Konseling Kampus')
@section('content')
@php
    // Kalender contoh untuk hero. Datanya ilustrasi, bukan jadwal konselor sungguhan.
    $m = now()->addMonthNoOverflow()->startOfMonth();
    $lead = $m->dayOfWeekIso - 1;
    $pattern = ['available', 'partial', 'available', 'full', 'partial', 'available', 'full', 'available'];
    $demo = [];
    for ($d = 1; $d <= $m->daysInMonth; $d++) {
        $demo[$d] = $m->copy()->day($d)->dayOfWeekIso >= 6 ? 'none' : $pattern[($d * 3) % count($pattern)];
    }
    $pick = collect($demo)->filter(fn ($s, $d) => $d >= 10 && $s === 'partial')->keys()->first();
    $demoStyle = ['available' => 'bg-sage-100 text-sage-900', 'partial' => 'bg-butter-100 text-butter-900', 'full' => 'bg-rose-100 text-rose-800', 'none' => 'text-slate-300'];
    $demoLabel = ['available' => 'kosong', 'partial' => 'sebagian', 'full' => 'penuh', 'none' => ''];

    // Angka platform
    $done = (int) ($byStatus['completed'] ?? 0);
    $booked = (int) ($byStatus['confirmed'] ?? 0);
    $waiting = (int) ($byStatus['pending'] ?? 0);
    $active = $done + $booked + $waiting;
    $donePct = $active > 0 ? round($done / $active * 100) : 0;
    $maxType = (int) ($byType->max('total') ?? 1);
@endphp

{{-- Hero --}}
<section class="grid items-center gap-10 rounded-[2rem] bg-ink-800 px-6 py-12 text-white md:grid-cols-12 md:px-14 md:py-16">
    <div class="md:col-span-7">
        <h1 class="font-display text-4xl font-bold leading-[1.05] tracking-tight text-white md:text-6xl">Lihat dulu, baru pesan sesinya</h1>
        <p class="mt-6 max-w-xl text-lg leading-relaxed text-ink-100">
            Ceritakan masalah Anda, pilih konselor terdaftar, lalu pilih tanggal di kalender yang menandai slot kosong, sebagian terisi, dan penuh. Konselor menerima permintaan itu dan mengonfirmasinya sesuai jadwal mereka.
        </p>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('student.booking.start') }}" class="btn border-white bg-white text-ink-900 hover:bg-ink-50">Mulai konseling</a>
            <a href="#cara-kerja" class="btn border-ink-400 bg-transparent text-white hover:bg-ink-700">Bagaimana alurnya?</a>
        </div>
    </div>

    <div class="relative md:col-span-5">
        <div class="rounded-3xl bg-white p-5 text-ink-900 shadow-2xl shadow-ink-900/40 md:rotate-[1.5deg]">
            <div class="flex items-baseline justify-between">
                <div class="font-display text-lg font-bold">{{ $m->locale('id')->translatedFormat('F Y') }}</div>
                <div class="text-xs text-slate-500">Contoh tampilan kalender</div>
            </div>
            <div class="mt-3 grid grid-cols-7 gap-1 text-center text-[11px] font-semibold text-slate-400">
                @foreach(['Sen','Sel','Rab','Kam','Jum','Sab','Min'] as $d)<div>{{ $d }}</div>@endforeach
            </div>
            <div class="mt-1 grid grid-cols-7 gap-1">
                @for($i = 0; $i < $lead; $i++)<div></div>@endfor
                @foreach($demo as $d => $st)
                    <div class="cal-in rounded-lg py-1.5 text-center {{ $demoStyle[$st] }} {{ $d === $pick ? 'ring-2 ring-ink-800 ring-offset-1' : '' }}" style="animation-delay: {{ $d * 22 }}ms">
                        <div class="font-display text-sm font-bold leading-4">{{ $d }}</div>
                        <div class="h-2.5 text-[8px] leading-[10px]">{{ $demoLabel[$st] }}</div>
                    </div>
                @endforeach
            </div>
            @if($pick)
                <div class="mt-4 border-t border-ink-100 pt-3">
                    <div class="mb-2 text-xs font-semibold text-slate-500">Jam pada tanggal {{ $pick }}</div>
                    <div class="grid grid-cols-3 gap-2 text-center text-sm">
                        <div class="rounded-lg bg-sage-100 py-1.5 font-semibold text-sage-900">09.00</div>
                        <div class="rounded-lg bg-ink-50 py-1.5 text-slate-400 line-through">10.00</div>
                        <div class="rounded-lg bg-sage-100 py-1.5 font-semibold text-sage-900">13.00</div>
                    </div>
                </div>
            @endif
        </div>
        <div class="mt-4 rounded-2xl bg-butter-100 p-4 text-sm text-butter-900 shadow-lg md:absolute md:-bottom-8 md:-left-10 md:mt-0 md:w-60">
            <div class="font-display font-bold">Konselor disetujui admin</div>
            <p class="mt-1 text-butter-900/80">Profil baru tampil di daftar setelah admin memeriksa pendaftarannya.</p>
        </div>
    </div>
</section>

{{-- Angka platform (data nyata dari database) --}}
<section class="mt-20" aria-labelledby="angka">
    <h2 id="angka" class="text-3xl font-bold">Angka di platform ini</h2>
    <p class="mt-2 text-slate-600">Dihitung langsung dari data yang ada saat halaman dibuka.</p>

    <dl class="mt-6 grid grid-cols-2 divide-ink-100 overflow-hidden rounded-3xl border border-ink-100 bg-white md:grid-cols-4 md:divide-x">
        <div class="p-6"><dt class="text-sm text-slate-500">Konselor aktif</dt><dd class="mt-1 font-display text-3xl font-bold">{{ $counselorCount }}</dd></div>
        <div class="p-6"><dt class="text-sm text-slate-500">Mahasiswa terdaftar</dt><dd class="mt-1 font-display text-3xl font-bold">{{ $studentCount }}</dd></div>
        <div class="p-6"><dt class="text-sm text-slate-500">Sesi selesai</dt><dd class="mt-1 font-display text-3xl font-bold">{{ $done }}</dd></div>
        <div class="p-6"><dt class="text-sm text-slate-500">Rata-rata rating</dt><dd class="mt-1 font-display text-3xl font-bold">{{ $ratingCount ? number_format($avgRating, 1) : '-' }}</dd>
            @if($ratingCount)<div class="text-xs text-slate-500">dari {{ $ratingCount }} rating</div>@endif</div>
    </dl>

    <div class="mt-4 grid gap-4 md:grid-cols-12">
        <div class="card md:col-span-7">
            <h3 class="text-lg font-bold">Tipe masalah yang diajukan</h3>
            @if($total >= 5)
                <ul class="mt-5 space-y-3">
                    @foreach($byType as $row)
                        <li>
                            <div class="mb-1 flex justify-between text-sm"><span>{{ $row->name }}</span><span class="text-slate-500">{{ $row->total }} sesi ({{ round($row->total / $total * 100) }}%)</span></div>
                            <div class="h-2.5 rounded-full bg-ink-50"><div class="h-2.5 rounded-full bg-ink-600" style="width: {{ round($row->total / $maxType * 100) }}%"></div></div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mt-4 text-sm text-slate-500">Rincian per tipe baru ditampilkan setelah ada minimal 5 sesi, supaya tidak ada sesi yang bisa dikenali dari angkanya.</p>
            @endif
        </div>

        <div class="card md:col-span-5">
            <h3 class="text-lg font-bold">Status sesi</h3>
            @if($active > 0)
                <div class="mt-5 flex items-center gap-6">
                    <div class="relative h-32 w-32 shrink-0 rounded-full" style="background: conic-gradient(#43438F 0 {{ $donePct }}%, #E2E2F4 0)">
                        <div class="absolute inset-4 grid place-items-center rounded-full bg-white text-center">
                            <div><div class="font-display text-2xl font-bold leading-6">{{ $donePct }}%</div><div class="text-[11px] text-slate-500">selesai</div></div>
                        </div>
                    </div>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-ink-600"></span>Selesai ({{ $done }})</li>
                        <li class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-ink-100"></span>Terjadwal ({{ $booked }})</li>
                        <li class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-ink-100"></span>Menunggu konfirmasi ({{ $waiting }})</li>
                    </ul>
                </div>
                <p class="mt-4 text-xs text-slate-500">Sesi yang ditolak atau dibatalkan tidak dihitung.</p>
            @else
                <p class="mt-4 text-sm text-slate-500">Belum ada sesi. Angka muncul setelah mahasiswa pertama membuat sesi.</p>
            @endif
        </div>
    </div>
</section>

{{-- Yang ada di platform --}}
<section class="mt-20" aria-labelledby="fitur">
    <h2 id="fitur" class="text-3xl font-bold">Yang Anda dapat di sini</h2>
    <div class="mt-6 grid gap-4 md:grid-cols-12">
        <div class="rounded-3xl bg-ink-900 p-8 text-white md:col-span-7">
            <h3 class="text-2xl font-bold text-white">Cerita yang hanya dibaca konselor pilihan Anda</h3>
            <p class="mt-3 max-w-lg leading-relaxed text-ink-200">Cerita singkat dan jawaban kuesioner tampil pada konselor yang Anda pilih. Admin melihat daftar sesi, tanggal, dan statusnya, tanpa isi cerita.</p>
        </div>
        <div class="card md:col-span-5">
            <h3 class="text-xl font-bold">Jadwal yang kelihatan</h3>
            <p class="mt-2 leading-relaxed text-slate-600">Setiap tanggal di kalender ditandai kosong, sebagian terisi, atau penuh. Anda tahu jam mana yang masih bisa dipilih sebelum mengirim permintaan.</p>
        </div>
        <div class="card md:col-span-5">
            <h3 class="text-xl font-bold">Rating dari mahasiswa</h3>
            <p class="mt-2 leading-relaxed text-slate-600">Setelah sesi selesai, mahasiswa memberi bintang 1 sampai 5 dan komentar. Rata-ratanya tampil di profil konselor.</p>
            @if($topCounselors->isNotEmpty())
                <div class="mt-5 flex items-center">
                    @foreach($topCounselors as $c)
                        <span class="-ml-2 first:ml-0 rounded-full ring-2 ring-white">@include('partials.avatar', ['name' => $c->user->name, 'size' => 'h-10 w-10 text-sm'])</span>
                    @endforeach
                    <a href="{{ route('counselors.index') }}" class="ml-4 text-sm font-semibold text-ink-700 underline underline-offset-4">Lihat {{ $counselorCount }} konselor</a>
                </div>
            @endif
        </div>
        <div class="rounded-3xl bg-sage-100 p-8 md:col-span-7">
            <h3 class="text-2xl font-bold text-sage-900">Latar belakang konselor yang terbuka</h3>
            <p class="mt-3 max-w-lg leading-relaxed text-sage-900/80">Profil memuat bidang lulusan, pendidikan terakhir, keahlian, dan daftar sertifikasi, sehingga Anda bisa menimbang siapa yang paling cocok untuk masalah Anda. Pendaftar konselor diperiksa admin sebelum tampil.</p>
        </div>
    </div>
</section>

{{-- Cara kerja --}}
<section id="cara-kerja" class="mt-20 grid scroll-mt-24 gap-12 md:grid-cols-12">
    <div class="md:col-span-7">
        <h2 class="text-3xl font-bold">Cara kerja</h2>
        <ol class="mt-8 ml-4 space-y-8 border-l-2 border-ink-200">
            @foreach([
                ['Pilih tipe masalah', 'Akademik, karier, relasi, keluarga, stres, atau tipe lain yang paling dekat dengan keadaan Anda.'],
                ['Tulis cerita singkat', 'Beberapa kalimat sudah cukup. Konselor membacanya sebelum sesi.'],
                ['Jawab 10 pertanyaan', 'Skala 0 sampai 3 tentang dua minggu terakhir. Hasilnya gambaran awal, bukan diagnosis.'],
                ['Pilih konselor', 'Konselor yang punya slot kosong tampil lebih dulu, lengkap dengan bidang lulusan, keahlian, dan sertifikasi.'],
                ['Pilih tanggal dan jam', 'Kalender menandai tanggal kosong, sebagian terisi, atau penuh. Sesi paling awal adalah besok.'],
            ] as $i => [$t, $d])
                <li class="relative pl-8">
                    <span class="absolute -left-[17px] top-0 grid h-8 w-8 place-items-center rounded-full bg-ink-800 font-display text-sm font-bold text-white">{{ $i + 1 }}</span>
                    <h3 class="text-lg font-bold">{{ $t }}</h3>
                    <p class="mt-1 max-w-lg text-slate-600">{{ $d }}</p>
                    @if($i === 0)<a href="{{ route('student.booking.start') }}" class="btn btn-primary mt-3">Isi data awal</a>@endif
                </li>
            @endforeach
        </ol>
    </div>

    <div class="md:col-span-5">
        <div class="rounded-3xl border border-ink-100 bg-white md:sticky md:top-24">
            <div class="p-6">
                <h3 class="text-lg font-bold">Mahasiswa</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">Membuat sesi, melihat profil dan rating konselor, membatalkan sesi yang belum berjalan, dan memberi rating setelah sesi selesai.</p>
                <a class="mt-3 inline-block text-sm font-semibold text-ink-700 underline underline-offset-4" href="{{ route('register.student') }}">Daftar sebagai mahasiswa</a>
            </div>
            <div class="border-t border-ink-100 p-6">
                <h3 class="text-lg font-bold">Konselor</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">Mendaftar dengan bidang lulusan, keahlian, dan sertifikasi; mengatur slot mingguan; membaca cerita dan hasil kuesioner; lalu mengonfirmasi, menolak, atau menandai sesi selesai.</p>
                <a class="mt-3 inline-block text-sm font-semibold text-ink-700 underline underline-offset-4" href="{{ route('register.counselor') }}">Daftar sebagai konselor</a>
            </div>
            <div class="border-t border-ink-100 p-6">
                <h3 class="text-lg font-bold">Admin</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">Memeriksa pendaftar konselor, mengaktifkan atau menonaktifkan akun, mengelola tipe masalah, dan melihat ringkasan sesi.</p>
            </div>
        </div>
    </div>
</section>

{{-- Kuesioner dan batasan --}}
<section class="mt-20 grid gap-8 rounded-[2rem] bg-ink-50 p-8 md:grid-cols-2 md:p-12">
    <div>
        <h2 class="text-2xl font-bold">Soal kuesioner</h2>
        <p class="mt-3 leading-relaxed text-slate-700">
            Sepuluh jawaban dijumlahkan menjadi skor 0 sampai 30 dan dikelompokkan rendah, sedang, atau tinggi. Skor ini membantu konselor menyiapkan sesi; ia tidak menggantikan pemeriksaan oleh psikolog atau dokter. Pertanyaan terakhir menyinggung pikiran menyakiti diri: bila dijawab selain "tidak pernah", permintaan Anda diberi tanda prioritas di antrean konselor.
        </p>
    </div>
    <div>
        <h2 class="text-2xl font-bold">Batasan</h2>
        <p class="mt-3 leading-relaxed text-slate-700">
            Situs ini mengatur jadwal dan data awal sesi. Sesi sendiri berlangsung di luar situs, sesuai kesepakatan dengan konselor. Untuk keadaan darurat, hubungi layanan darurat setempat atau orang terdekat tanpa menunggu konfirmasi jadwal.
        </p>
    </div>
</section>
@endsection
