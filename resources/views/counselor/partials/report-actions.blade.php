<div
    class="space-y-2"
    data-report-widget
    data-report-state="{{ $booking->report->status ?? 'not_requested' }}"
    @if($booking->report) data-status-url="{{ route('counselor.report.status', $booking->report) }}" @endif
>
    <p data-report-message class="text-xs text-slate-600" aria-live="polite">
        @if($booking->report?->status === 'processing')
            Rekap sedang diproses oleh n8n…
        @elseif($booking->report?->status === 'failed')
            Rekap gagal dibuat. Anda dapat mencoba lagi.
        @elseif($booking->report?->status === 'completed')
            Rekap PDF sudah tersedia.
        @else
            Buat PDF rekap sesi melalui n8n.
        @endif
    </p>
    <form method="POST" action="{{ route('counselor.report.request', $booking) }}" data-report-request @if(in_array($booking->report?->status, ['processing', 'completed'], true)) hidden @endif>
        @csrf
        <button type="submit" class="btn btn-primary px-3 py-2 text-xs">
            {{ $booking->report?->status === 'failed' ? 'Coba buat ulang' : 'Rekap data' }}
        </button>
    </form>
    <a
        href="{{ $booking->report?->status === 'completed' ? route('counselor.report.download', $booking->report) : '#' }}"
        data-report-download
        class="btn px-3 py-2 text-xs"
        @if($booking->report?->status !== 'completed') hidden @endif
    >Unduh PDF</a>
</div>
