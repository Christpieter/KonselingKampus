@php
    $tones = ['bg-ink-200 text-ink-900', 'bg-sage-200 text-sage-900', 'bg-butter-200 text-butter-900', 'bg-rose-200 text-rose-900'];
    $tone = $tones[abs(crc32($name)) % 4];
    $initials = collect(preg_split('/\s+/', trim(preg_replace('/,.*/', '', $name))))
        ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp
<span class="grid shrink-0 place-items-center rounded-full font-display font-bold {{ $size ?? 'h-12 w-12 text-lg' }} {{ $tone }}" aria-hidden="true">{{ $initials }}</span>
