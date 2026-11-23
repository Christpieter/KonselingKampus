@if(($count ?? 0) > 0)
    <span class="text-butter-500">{{ str_repeat('★', (int) round($avg)) }}<span class="text-slate-300">{{ str_repeat('★', 5 - (int) round($avg)) }}</span></span>
    <span class="text-sm text-slate-600">{{ number_format($avg, 1) }} ({{ $count }} rating)</span>
@else
    <span class="text-sm text-slate-400">Belum ada rating</span>
@endif
