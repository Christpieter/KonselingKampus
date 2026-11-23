<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class CompanionChatController extends Controller
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
    Kamu adalah teman bicara yang suportif bagi mahasiswa. Dengarkan dengan empati, gunakan bahasa Indonesia yang hangat dan tidak menghakimi, dan bantu mahasiswa mengeksplorasi perasaannya dengan pertanyaan terbuka yang sederhana.

    Batasan penting:
    - Jangan mendiagnosis, memberi label klinis, menyarankan obat, atau mengklaim sebagai tenaga profesional.
    - Berikan dukungan umum dan langkah kecil yang aman; sarankan berbicara dengan konselor kampus atau orang tepercaya bila dibutuhkan.
    - Jangan meminta data identitas pribadi seperti nama lengkap, NIM, alamat, atau nomor telepon.
    - Jika mahasiswa menyebut bahwa dirinya atau orang lain dalam bahaya sekarang, utamakan keselamatan: sarankan menghubungi layanan darurat setempat dan orang tepercaya yang bisa menemani sekarang. Jangan meminta mereka menunggu balasan AI.
    - Jawab dengan ringkas dan fokus pada kebutuhan mahasiswa.
    PROMPT;

    public function index(): View
    {
        return view('student.companion-chat', [
            'chatAvailable' => $this->chatAvailable(),
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'history' => ['sometimes', 'array', 'max:12'],
            'history.*.role' => ['required', 'in:user,assistant'],
            'history.*.content' => ['required', 'string', 'max:2000'],
        ]);

        if (! $this->chatAvailable()) {
            return response()->json(['message' => 'Asisten AI belum siap digunakan.'], 503);
        }

        $history = array_map(
            static fn (array $message): array => [
                'role' => $message['role'],
                'content' => $message['content'],
            ],
            $validated['history'] ?? []
        );

        $messages = [
            ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
            ...$history,
            ['role' => 'user', 'content' => trim($validated['message'])],
        ];

        try {
            $response = Http::acceptJson()
                ->withToken(config('services.nvidia_nim.key'))
                ->timeout(60)
                ->post(rtrim((string) config('services.nvidia_nim.base_url'), '/') . '/chat/completions', [
                    'model' => config('services.nvidia_nim.model'),
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

        if ($reply === '') {
            return response()->json(['message' => 'Asisten mengirim jawaban kosong. Coba lagi nanti.'], 502);
        }

        return response()->json(['reply' => $reply]);
    }

    private function chatAvailable(): bool
    {
        return (bool) config('services.nvidia_nim.chat_enabled')
            && filled(config('services.nvidia_nim.base_url'))
            && filled(config('services.nvidia_nim.key'))
            && filled(config('services.nvidia_nim.model'));
    }
}
