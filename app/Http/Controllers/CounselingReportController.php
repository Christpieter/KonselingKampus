<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CounselingReport;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CounselingReportController extends Controller
{
    private const MAX_PDF_BYTES = 5 * 1024 * 1024;

    public function requestReport(Booking $booking)
    {
        abort_unless($booking->counselor_id === auth()->id(), 403);
        abort_unless($booking->status === 'completed', 422, 'Rekap hanya tersedia untuk sesi yang sudah selesai.');

        $webhookUrl = config('services.n8n.counseling_report_webhook_url');
        $webhookSecret = config('services.n8n.webhook_secret');

        if (! filled($webhookUrl) || ! filled($webhookSecret)) {
            return back()->with('err', 'Webhook rekap n8n belum dikonfigurasi.');
        }

        $report = $booking->report;
        if ($report?->status === 'completed') {
            return back()->with('ok', 'Rekap sesi sudah tersedia untuk diunduh.');
        }
        if ($report?->status === 'processing' && $report->updated_at->gt(now()->subMinutes(5))) {
            return back()->with('ok', 'Rekap sesi masih diproses. Halaman akan diperbarui saat file siap.');
        }

        $callbackToken = Str::random(64);
        if (! $report) {
            $report = new CounselingReport([
                'booking_id' => $booking->id,
                'counselor_id' => auth()->id(),
            ]);
        }
        $report->fill([
            'status' => 'processing',
            'callback_token_hash' => hash('sha256', $callbackToken),
            'file_path' => null,
            'generated_at' => null,
        ])->save();

        try {
            $response = Http::withHeaders([
                'X-Webhook-Secret' => $webhookSecret,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($webhookUrl, [
                'report_id' => $report->id,
                'callback_token' => $callbackToken,
                'callback_url' => route('webhook.n8n.counseling-report'),
                'report' => $this->reportPayload($booking),
            ]);

            if (! $response->successful()) {
                $this->markFailed($report, $callbackToken);
                Log::error('n8n counseling report request failed', [
                    'status' => $response->status(),
                    'report_id' => $report->id,
                ]);

                return back()->with('err', 'n8n gagal menerima permintaan rekap. Silakan coba lagi.');
            }
        } catch (\Illuminate\Http\Client\ConnectionException $exception) {
            $this->markFailed($report, $callbackToken);
            Log::error('n8n counseling report connection failed', [
                'report_id' => $report->id,
                'exception' => $exception->getMessage(),
            ]);

            return back()->with('err', 'Tidak dapat menghubungi n8n. Silakan coba lagi nanti.');
        }

        return back()->with('ok', 'Permintaan rekap dikirim. File PDF akan tersedia setelah n8n selesai memprosesnya.');
    }

    public function receive(Request $request)
    {
        $expectedSecret = config('services.n8n.webhook_secret');
        $providedSecret = $request->header('X-Webhook-Secret');
        abort_unless(
            filled($expectedSecret) && is_string($providedSecret) && hash_equals($expectedSecret, $providedSecret),
            401,
            'Unauthorized'
        );

        $data = $request->validate([
            'report_id' => ['required', 'integer', 'exists:counseling_reports,id'],
            'callback_token' => ['required', 'string', 'size:64'],
            'pdf_base64' => ['required', 'string', 'max:6990508'],
        ]);

        $report = CounselingReport::findOrFail($data['report_id']);
        abort_unless(
            hash_equals($report->callback_token_hash, hash('sha256', $data['callback_token'])),
            401,
            'Invalid callback token'
        );

        if ($report->status === 'completed') {
            return response()->json(['message' => 'Rekap sudah diterima.']);
        }
        abort_unless($report->status === 'processing', 409, 'Permintaan rekap tidak sedang diproses.');

        $pdf = base64_decode($data['pdf_base64'], true);
        abort_unless(
            is_string($pdf)
                && strlen($pdf) <= self::MAX_PDF_BYTES
                && str_starts_with($pdf, '%PDF-'),
            422,
            'Payload harus berupa file PDF valid maksimal 5 MB.'
        );

        $path = "counseling-reports/{$report->id}.pdf";
        if (! Storage::disk('local')->put($path, $pdf)) {
            $report->update(['status' => 'failed']);
            Log::error('Failed to store n8n counseling report PDF', ['report_id' => $report->id]);

            return response()->json(['message' => 'File rekap gagal disimpan.'], 500);
        }

        $report->update([
            'status' => 'completed',
            'file_path' => $path,
            'generated_at' => now(),
        ]);

        return response()->json(['message' => 'Rekap berhasil diterima.']);
    }

    public function status(CounselingReport $report)
    {
        abort_unless($report->counselor_id === auth()->id(), 403);

        if ($report->status === 'processing' && $report->updated_at->lt(now()->subMinutes(5))) {
            $report->update(['status' => 'failed']);
        }

        return response()->json([
            'status' => $report->status,
            'download_url' => $report->status === 'completed'
                ? route('counselor.report.download', $report)
                : null,
        ]);
    }

    public function download(CounselingReport $report)
    {
        abort_unless($report->counselor_id === auth()->id(), 403);
        abort_unless($report->status === 'completed' && $report->file_path, 404);
        abort_unless(Storage::disk('local')->exists($report->file_path), 404, 'File rekap tidak ditemukan.');

        return Storage::disk('local')->download(
            $report->file_path,
            "rekap-sesi-{$report->booking_id}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }

    private function reportPayload(Booking $booking): array
    {
        $answers = $booking->answers ?? [];
        $answerIsList = array_is_list($answers);
        $answerLabels = ['Tidak pernah', 'Beberapa hari', 'Lebih dari separuh hari', 'Hampir setiap hari'];

        return [
            'session' => [
                'booking_id' => $booking->id,
                'date' => $booking->session_date->toDateString(),
                'start_time' => substr($booking->start_time, 0, 5),
                'end_time' => substr($booking->end_time, 0, 5),
                'status' => $booking->statusLabel(),
                'problem_type' => $booking->problemType->name,
                'story' => $booking->story,
                'counselor_note' => $booking->counselor_note,
            ],
            'student' => [
                'name' => $booking->student->name,
                'nim' => $booking->student->nim,
                'email' => $booking->student->email,
            ],
            'counselor' => [
                'name' => $booking->counselor->name,
                'email' => $booking->counselor->email,
            ],
            'triage' => [
                'total_score' => $booking->score,
                'score_level' => Booking::scoreLevel($booking->score),
                'needs_priority' => $booking->needs_priority,
                'answers' => Question::orderBy('position')->get()->map(function (Question $question, int $index) use ($answers, $answerIsList, $answerLabels) {
                    $answer = $answerIsList
                        ? ($answers[$index] ?? null)
                        : ($answers[$question->id] ?? null);

                    return [
                        'position' => $question->position,
                        'question' => $question->text,
                        'answer' => $answer,
                        'answer_label' => is_numeric($answer) ? ($answerLabels[(int) $answer] ?? null) : null,
                    ];
                })->values()->all(),
            ],
        ];
    }

    private function markFailed(CounselingReport $report, string $callbackToken): void
    {
        CounselingReport::whereKey($report->id)
            ->where('status', 'processing')
            ->where('callback_token_hash', hash('sha256', $callbackToken))
            ->update(['status' => 'failed']);
    }
}
