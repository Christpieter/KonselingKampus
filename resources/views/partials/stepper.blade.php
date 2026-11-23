<ol class="mb-8 flex flex-wrap items-center gap-2 text-sm">
    @php
        $steps = ['Triage', 'Data awal', 'Konselor', 'Jadwal'];
        // If triage was completed, show it as step 0 (done).
        // Views pass step=1 for intake, 2 for counselors, 3 for schedule.
        // With 4 steps, these map directly to indices 1, 2, 3.
        $triageDone = session('booking_draft.triage_session_id') ?? false;
        $currentStep = $step; // 1, 2, or 3 from view
    @endphp
    @foreach($steps as $i => $label)
        @php
            $state = $triageDone && $i === 0 ? 'done' : ($i < $currentStep ? 'done' : ($i === $currentStep ? 'now' : 'todo'));
        @endphp
        <li class="flex items-center gap-2" @if($state === 'now') aria-current="step" @endif>
            <span class="grid h-7 w-7 place-items-center rounded-full text-xs font-bold {{ ['done' => 'bg-sage-300 text-sage-900', 'now' => 'bg-ink-800 text-white', 'todo' => 'bg-ink-100 text-ink-400'][$state] }}">{{ $state === 'done' ? '✓' : ($i + 1) }}</span>
            <span class="{{ $state === 'todo' ? 'text-slate-400' : 'font-semibold' }}">{{ $label }}</span>
        </li>
        @if(! $loop->last)<li class="h-px w-8 bg-ink-200" aria-hidden="true"></li>@endif
    @endforeach
</ol>
