<?php

namespace App\Http\Controllers;

use App\Models\TriageSession;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class TriageChatController extends Controller
{
    private const TOTAL_QUESTIONS = 10;

    /** Jumlah pesan terakhir dari log server yang dikirim ke AI sebagai konteks. */
    private const AI_CONTEXT_MESSAGES = 40;

    private const SYSTEM_PROMPT = <<<'PROMPT'
    Kamu adalah asisten triage kesehatan mental untuk mahasiswa sebelum sesi konseling. Tugasmu: mengajukan 10 pertanyaan secara naratif/percakapan (bukan format kuesioner kaku) dan kosakata yang benar untuk memahami kondisi emosional mahasiswa.

    ATURAN UTAMA:
    1. Gunakan bahasa Indonesia berdasarkan KBBI yang hangat, empatik, dan tidak menghakimi.
    2. JANGAN mendiagnosis, memberi label klinis, atau menyarankan pengobatan.
    3. Selalu berikan pertanyaan yang bisa dijawab dengan jawaban  0 = Tidak pernah · 1 = Beberapa hari · 2 = Lebih dari separuh hari · 3 = Hampir setiap hari. tapi jangan tampilkan dalam chat.
    4. Ajukan SATU pertanyaan per balasan. Tunggu jawaban mahasiswa sebelum lanjut.
    5. Pertanyaan harus terasa alami dalam percakapan, bukan daftar tanya jawab dan tetap masuk akal.
    6. Setelah 10 pertanyaan terjawab, ringkas dan akhiri dengan: "TRIAGE_SELESAI" di akhir pesan.
    7. Jika mahasiswa menyebut bahaya diri/ orang lain SEKARANG: hentikan, sarankan hubungi darurat/ orang tepercaya SEKARANG, jangan tunggu AI.
    8. Jangan minta data identitas (nama lengkap, NIM, alamat, telepon).
    9. Skala jawaban sudah ditampilkan sekali di layar percakapan. Jangan mengulang angka, label skala, atau instruksi format jawaban dalam setiap balasan.
    10. Mahasiswa dapat menjawab dengan angka 0-3 atau label jawaban yang tertulis pada skala. Jika jawabannya tidak cocok dengan pilihan mana pun, minta klarifikasi singkat tanpa menyalin seluruh skala.
    11. Ikuti penanda [SISTEM] di akhir prompt untuk mengetahui pertanyaan nomor berapa yang harus diajukan sekarang. Jangan mengulang pertanyaan yang sudah dijawab.

    URUTAN PERTANYAAN (10 item, 3 subskala):
    KECEMASAN (3): 1) Gelisah/kegugupan, 2) Jantung berdebar/napas sesak, 3) Takut/cemas tanpa alasan jelas
    DEPRESI (3): 4) Hilang semangat/minat, 5) Perasaan putus asa/tidak berharga, 6) Sulit memulai aktivitas
    STRES (4): 7) Sulit tenang/relax, 8) Mudah tersinggung/marah, 9) Merasa kewalahan/tertekan, 10) Sulit tidur/istirahat

    Pertanyaan pertama (Kecemasan #1) sudah diajukan di layar pembuka, jadi jawaban pertama mahasiswa adalah untuk pertanyaan #1. Lanjutkan dari pertanyaan #2.
    PROMPT;

    public function index(): View
    {
        $student = auth()->user();

        $existingTriage = TriageSession::where('student_id', $student->id)
            ->where('status', 'completed')
            ->latest()
            ->first();

        return view('student.triage', [
            'chatAvailable' => $this->chatAvailable(),
            'existingTriage' => $existingTriage,
            'questionsCount' => self::TOTAL_QUESTIONS,
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'triage_session_id' => ['sometimes', 'nullable', 'integer', 'exists:triage_sessions,id'],
        ]);

        if (! $this->chatAvailable()) {
            return response()->json(['message' => 'Asisten triage belum siap digunakan.'], 503);
        }

        $student = auth()->user();
        $userMessage = trim($validated['message']);

        // Ambil sesi yang sedang berjalan, atau buat baru
        $triageSession = null;
        if (! empty($validated['triage_session_id'])) {
            $triageSession = TriageSession::where('id', $validated['triage_session_id'])
                ->where('student_id', $student->id)
                ->where('status', 'in_progress')
                ->first();
        }

        if (! $triageSession) {
            $triageSession = TriageSession::create([
                'student_id' => $student->id,
                'answers' => [],
                'conversation_log' => [],
                'status' => 'in_progress',
            ]);
        }

        // Sumber kebenaran = log di server, bukan history dari klien
        $log = $triageSession->conversation_log ?? [];

        $answerCount = $this->countAnswers($log);
        if ($this->parseAnswer($userMessage) !== null) {
            $answerCount++;
        }

        // Jawaban ke-10 masuk -> selesai tanpa memanggil AI
        if ($answerCount >= self::TOTAL_QUESTIONS) {
            $finalReply = 'Terima kasih sudah menjawab semua pertanyaan dengan jujur. Triage selesai, skor Anda sedang dihitung.';

            $log[] = ['role' => 'user', 'content' => $userMessage, 'timestamp' => now()->toIso8601String()];
            $log[] = ['role' => 'assistant', 'content' => $finalReply, 'timestamp' => now()->toIso8601String()];
            $triageSession->update(['conversation_log' => $log]);

            return response()->json([
                'reply' => $finalReply,
                'triage_session_id' => $triageSession->id,
                'triage_complete' => true,
                'answer_count' => self::TOTAL_QUESTIONS,
            ]);
        }

        $systemPrompt = self::SYSTEM_PROMPT
            . "\n\n[SISTEM: Mahasiswa telah menjawab {$answerCount} dari 10 pertanyaan. "
            . 'Pertanyaan yang harus diajukan sekarang: nomor ' . ($answerCount + 1) . '. '
            . 'JANGAN menulis TRIAGE_SELESAI sebelum 10 pertanyaan terjawab.]';

        $context = array_map(
            static fn (array $m): array => [
                'role' => $m['role'],
                'content' => (string) $m['content'],
            ],
            array_slice($log, -self::AI_CONTEXT_MESSAGES)
        );

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ...$context,
            ['role' => 'user', 'content' => $userMessage],
        ];

        try {
            $response = Http::acceptJson()
                ->withToken(config('services.openrouter.key'))
                ->withHeaders([
                    'HTTP-Referer' => config('app.url'),
                    'X-Title' => config('app.name') . ' - Triage',
                ])
                ->timeout(60)
                ->post(rtrim((string) config('services.openrouter.base_url'), '/') . '/chat/completions', [
                    'model' => config('services.openrouter.model', 'openai/gpt-oss-20b'),
                    'messages' => $messages,
                    'stream' => false,
                ]);
        } catch (ConnectionException) {
            return response()->json(['message' => 'Asisten sedang tidak dapat dihubungi. Coba lagi nanti.'], 502);
        }

        if (! $response->successful()) {
            return response()->json(['message' => 'Asisten sedang tidak dapat menjawab. Coba lagi nanti.'], 502);
        }

        $reply = trim((string) $response->json('choices.0.message.content', ''));

        // Marker TRIAGE_SELESAI dari AI diabaikan: penyelesaian hanya ditentukan
        // oleh jumlah jawaban valid di server (lihat blok "Jawaban ke-10" di atas).
        $reply = trim(str_ireplace('TRIAGE_SELESAI', '', $reply));
        $reply = trim((string) preg_replace(
            '/\s*\(?\s*jawab\s*:\s*0\s*=\s*tidak\s+pernah\s*,\s*1\s*=\s*beberapa\s+hari\s*,\s*2\s*=\s*lebih\s+dari\s+separuh\s+hari\s*,\s*3\s*=\s*hampir\s+setiap\s+hari\s*\)?\s*$/iu',
            '',
            $reply
        ));

        if ($reply === '') {
            return response()->json(['message' => 'Asisten mengirim jawaban kosong. Coba lagi nanti.'], 502);
        }

        $log[] = ['role' => 'user', 'content' => $userMessage, 'timestamp' => now()->toIso8601String()];
        $log[] = ['role' => 'assistant', 'content' => $reply, 'timestamp' => now()->toIso8601String()];
        $triageSession->update(['conversation_log' => $log]);

        return response()->json([
            'reply' => $reply,
            'triage_session_id' => $triageSession->id,
            'triage_complete' => false,
            'answer_count' => $answerCount,
        ]);
    }

    /**
     * Opsional: kirim log ke n8n untuk perhitungan skor.
     * Alur utama saat ini memakai calculate() langsung di Laravel.
     */
    public function complete(Request $request): JsonResponse
    {
        $request->validate([
            'triage_session_id' => ['required', 'integer', 'exists:triage_sessions,id'],
        ]);

        $student = auth()->user();
        $triageSession = TriageSession::where('id', $request->triage_session_id)
            ->where('student_id', $student->id)
            ->where('status', 'in_progress')
            ->firstOrFail();

        $webhookUrl = config('services.n8n.triage_webhook_url');
        $webhookSecret = config('services.n8n.webhook_secret');

        if (! $webhookUrl) {
            return response()->json(['message' => 'Konfigurasi webhook n8n belum siap.'], 503);
        }

        try {
            $response = Http::withHeaders([
                'X-Webhook-Secret' => $webhookSecret,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($webhookUrl, [
                'triage_session_id' => $triageSession->id,
                'student_id' => $student->id,
                'conversation_log' => $triageSession->conversation_log,
            ]);

            if (! $response->successful()) {
                Log::error('n8n triage webhook failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'triage_session_id' => $triageSession->id,
                ]);

                return response()->json(['message' => 'Gagal memproses skor triage. Hubungi admin.'], 502);
            }

            return response()->json([
                'message' => 'Skor triage sedang diproses. Hasil akan tersedia sebentar.',
                'triage_session_id' => $triageSession->id,
            ]);
        } catch (ConnectionException) {
            return response()->json(['message' => 'Tidak dapat menghubungi layanan pemrosesan skor. Coba lagi nanti.'], 502);
        }
    }

    public function result(Request $request): JsonResponse
    {
        $request->validate([
            'triage_session_id' => ['required', 'integer', 'exists:triage_sessions,id'],
        ]);

        $student = auth()->user();
        $triageSession = TriageSession::where('id', $request->triage_session_id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        return response()->json([
            'triage_session' => $this->sessionPayload($triageSession),
        ]);
    }

    /**
     * Hitung skor langsung di Laravel dan set status menjadi 'completed'.
     * Idempoten: jika sesi sudah completed, hasil yang ada dikembalikan.
     */
    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'triage_session_id' => ['required', 'integer', 'exists:triage_sessions,id'],
        ]);

        $student = auth()->user();
        $triageSession = TriageSession::where('id', $request->triage_session_id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        if ($triageSession->status === 'completed') {
            return response()->json([
                'message' => 'Skor triage sudah dihitung.',
                'triage_session' => $this->sessionPayload($triageSession),
            ]);
        }

        $answers = $this->extractAnswersFromLog($triageSession->conversation_log ?? []);

        if (count($answers) !== self::TOTAL_QUESTIONS) {
            return response()->json([
                'message' => 'Jawaban tidak lengkap (dibutuhkan ' . self::TOTAL_QUESTIONS
                    . ', ditemukan ' . count($answers) . '). Silakan ulangi triage.',
            ], 422);
        }

        // Anxiety: item 0-2 (raw 0-9) | Depression: item 3-5 (raw 0-9) | Stress: item 6-9 (raw 0-12)
        $anxietyScore = array_sum(array_slice($answers, 0, 3));
        $depressionScore = array_sum(array_slice($answers, 3, 3));
        $stressScore = array_sum(array_slice($answers, 6, 4));
        $totalScore = $anxietyScore + $depressionScore + $stressScore; // 0-30

        $anxietyLevel = TriageSession::calculateLevel('anxiety', $anxietyScore);
        $depressionLevel = TriageSession::calculateLevel('depression', $depressionScore);
        $stressLevel = TriageSession::calculateLevel('stress', $stressScore);
        $needsPriority = TriageSession::requiresPriority($depressionLevel, $anxietyLevel, $stressLevel);

        $triageSession->update([
            'answers' => $answers,
            'anxiety_score' => $anxietyScore,
            'depression_score' => $depressionScore,
            'stress_score' => $stressScore,
            'total_score' => $totalScore,
            'anxiety_level' => $anxietyLevel,
            'depression_level' => $depressionLevel,
            'stress_level' => $stressLevel,
            'needs_priority' => $needsPriority,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Skor triage berhasil dihitung.',
            'triage_session' => $this->sessionPayload($triageSession->fresh()),
        ]);
    }

    private function chatAvailable(): bool
    {
        return (bool) config('services.openrouter.chat_enabled')
            && filled(config('services.openrouter.base_url'))
            && filled(config('services.openrouter.key'))
            && filled(config('services.openrouter.model'));
    }

    /**
     * Parser tunggal untuk jawaban 0-3. Dipakai oleh penghitung DAN ekstraktor
     * supaya keduanya selalu konsisten.
     * Menerima angka 0-3 atau label skala, dengan atau tanpa awalan "jawab".
     */
    private function parseAnswer(string $content): ?int
    {
        $normalized = mb_strtolower(trim($content));
        $normalized = preg_replace('/^jawab\s*[:=]?\s*/u', '', $normalized);
        $normalized = trim($normalized, " \t\n\r\0\x0B.,!?;:\"'()[]{}");

        if (preg_match('/^[0-3]$/', $normalized)) {
            return (int) $normalized;
        }

        $textAnswers = [
            0 => ['tidak pernah'],
            1 => ['beberapa hari'],
            2 => ['lebih dari separuh hari', 'lebih dari setengah hari'],
            3 => ['hampir setiap hari'],
        ];

        foreach ($textAnswers as $score => $labels) {
            if (in_array($normalized, $labels, true)) {
                return $score;
            }
        }

        return null;
    }

    private function countAnswers(array $log): int
    {
        $count = 0;
        foreach ($log as $entry) {
            if (($entry['role'] ?? '') === 'user'
                && $this->parseAnswer((string) ($entry['content'] ?? '')) !== null) {
                $count++;
            }
        }

        return $count;
    }

    private function extractAnswersFromLog(array $log): array
    {
        $answers = [];
        foreach ($log as $entry) {
            if (($entry['role'] ?? '') !== 'user') {
                continue;
            }

            $value = $this->parseAnswer((string) ($entry['content'] ?? ''));
            if ($value !== null) {
                $answers[] = $value;
            }
        }

        return $answers;
    }

    private function sessionPayload(TriageSession $session): array
    {
        return [
            'id' => $session->id,
            'anxiety_score' => $session->anxiety_score,
            'depression_score' => $session->depression_score,
            'stress_score' => $session->stress_score,
            'total_score' => $session->total_score,
            'anxiety_level' => $session->anxiety_level,
            'depression_level' => $session->depression_level,
            'stress_level' => $session->stress_level,
            'needs_priority' => $session->needs_priority,
            'status' => $session->status,
            'completed_at' => $session->completed_at,
            'level_labels' => [
                'anxiety' => $session->levelLabel('anxiety'),
                'depression' => $session->levelLabel('depression'),
                'stress' => $session->levelLabel('stress'),
            ],
        ];
    }
}