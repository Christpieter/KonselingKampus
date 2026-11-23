@php
    $labels = ['available' => 'kosong', 'partial' => 'sebagian', 'full' => 'penuh', 'none' => '', 'past' => ''];
    $styles = [
        'available' => 'bg-sage-100 text-sage-900 hover:bg-sage-200',
        'partial' => 'bg-butter-100 text-butter-900 hover:bg-butter-200',
        'full' => 'bg-rose-100 text-rose-800 hover:bg-rose-200',
        'none' => 'text-slate-300',
        'past' => 'text-slate-300',
    ];
    $lead = $month->copy()->startOfMonth()->dayOfWeekIso - 1;
@endphp
<div class="mb-4 flex items-center justify-between">
    @if($prevMonth)<a class="btn px-4" aria-label="Bulan sebelumnya" href="{{ route('student.booking.schedule', ['bulan' => $prevMonth->format('Y-m')]) }}">&larr;</a>@else<span></span>@endif
    <h3 class="font-display text-xl font-bold">{{ $month->locale('id')->translatedFormat('F Y') }}</h3>
    @if($nextMonth)<a class="btn px-4" aria-label="Bulan berikutnya" href="{{ route('student.booking.schedule', ['bulan' => $nextMonth->format('Y-m')]) }}">&rarr;</a>@else<span></span>@endif
</div>
<div class="grid grid-cols-7 gap-1.5 text-center text-xs font-semibold text-slate-500">
    @foreach(['Sen','Sel','Rab','Kam','Jum','Sab','Min'] as $d)<div class="py-1">{{ $d }}</div>@endforeach
</div>
<div class="mt-1 grid grid-cols-7 gap-1.5">
    @for($i = 0; $i < $lead; $i++)<div></div>@endfor
    @foreach($statuses as $date => $st)
        @php $isSel = $selected && $selected->toDateString() === $date; $clickable = in_array($st, ['available','partial','full']); @endphp
        @if($clickable)
            <a href="{{ route('student.booking.schedule', ['bulan' => $month->format('Y-m'), 'tanggal' => $date]) }}"
               class="rounded-xl px-1 py-2 text-center transition-colors {{ $styles[$st] }} {{ $isSel ? 'ring-2 ring-ink-800 ring-offset-2' : '' }}">
        @else
            <div class="rounded-xl px-1 py-2 text-center {{ $styles[$st] }}">
        @endif
                <div class="font-display text-base font-bold leading-5">{{ (int) substr($date, 8, 2) }}</div>
                <div class="h-3 text-[10px] leading-3">{{ $labels[$st] }}</div>
        @if($clickable)</a>@else</div>@endif
    @endforeach
</div>
<ul class="mt-5 flex flex-wrap gap-x-5 gap-y-2 text-xs text-slate-600">
    <li class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-sage-200"></span>Kosong: semua slot tersedia</li>
    <li class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-butter-200"></span>Sebagian: ada slot terisi</li>
    <li class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-rose-200"></span>Penuh</li>
    <li class="flex items-center gap-1.5"><span class="h-3 w-3 rounded border border-ink-100 bg-white"></span>Tidak ada jadwal</li>
</ul>
