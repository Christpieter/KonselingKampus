<?php

namespace Tests\Feature;

use App\Models\TriageSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TriageChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_triage_calculation_accepts_numeric_and_text_answers(): void
    {
        $student = User::factory()->create([
            'role' => 'mahasiswa',
            'is_active' => true,
        ]);
        $answers = [
            '0',
            'Jawab: 1',
            '2',
            '3',
            'Tidak pernah',
            'Beberapa hari',
            'Lebih dari separuh hari',
            'Hampir setiap hari',
            'Jawab: 2',
            'Jawab: Hampir setiap hari',
        ];
        $conversation = [];
        foreach ($answers as $answer) {
            $conversation[] = ['role' => 'user', 'content' => $answer];
        }

        $triage = TriageSession::create([
            'student_id' => $student->id,
            'answers' => [],
            'conversation_log' => $conversation,
            'status' => 'in_progress',
        ]);

        $this->actingAs($student)
            ->postJson(route('student.triage.calculate'), ['triage_session_id' => $triage->id])
            ->assertOk()
            ->assertJsonPath('triage_session.anxiety_score', 3)
            ->assertJsonPath('triage_session.depression_score', 4)
            ->assertJsonPath('triage_session.stress_score', 10)
            ->assertJsonPath('triage_session.total_score', 17);

        $this->assertSame([0, 1, 2, 3, 0, 1, 2, 3, 2, 3], $triage->fresh()->answers);
    }

    public function test_ai_replies_do_not_repeat_the_answer_scale(): void
    {
        config([
            'services.openrouter.chat_enabled' => true,
            'services.openrouter.base_url' => 'https://openrouter.example/api/v1',
            'services.openrouter.key' => 'test-key',
            'services.openrouter.model' => 'test-model',
        ]);
        Http::fake([
            'https://openrouter.example/api/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => 'Terima kasih sudah berbagi. (Jawab: 0=Tidak pernah, 1=Beberapa hari, 2=Lebih dari separuh hari, 3=Hampir setiap hari)',
                    ],
                ]],
            ]),
        ]);
        $student = User::factory()->create([
            'role' => 'mahasiswa',
            'is_active' => true,
        ]);

        $this->actingAs($student)
            ->postJson(route('student.triage.send'), ['message' => 'Beberapa hari'])
            ->assertOk()
            ->assertJsonPath('reply', 'Terima kasih sudah berbagi.');

        Http::assertSent(fn ($request) => str_contains(
            $request['messages'][0]['content'],
            'Jangan mengulang angka, label skala'
        ));
    }
}
