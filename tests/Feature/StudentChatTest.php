<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StudentChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_the_student_chat(): void
    {
        $this->get('/mahasiswa/teman-bicara')->assertRedirect('/masuk');
    }

    public function test_student_can_open_the_chat_and_see_its_limits(): void
    {
        $student = User::factory()->create([
            'role' => 'mahasiswa',
            'is_active' => true,
        ]);

        $this->actingAs($student)
            ->get('/mahasiswa/teman-bicara')
            ->assertOk()
            ->assertSee('Teman bicara')
            ->assertSee('bukan pengganti konselor', false)
            ->assertSee('layanan darurat setempat');
    }

    public function test_non_students_cannot_open_the_chat(): void
    {
        $counselor = User::factory()->create([
            'role' => 'konselor',
            'is_active' => true,
        ]);

        $this->actingAs($counselor)
            ->get('/mahasiswa/teman-bicara')
            ->assertForbidden();
    }

    public function test_chat_is_unavailable_until_nim_credentials_are_present(): void
    {
        config([
            'services.nvidia_nim.chat_enabled' => true,
            'services.nvidia_nim.base_url' => 'https://integrate.api.nvidia.com/v1',
            'services.nvidia_nim.key' => null,
        ]);

        $student = User::factory()->create([
            'role' => 'mahasiswa',
            'is_active' => true,
        ]);

        $this->actingAs($student)
            ->postJson('/mahasiswa/teman-bicara', ['message' => 'Saya merasa cemas'])
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'Asisten AI belum siap digunakan.');
    }

    public function test_student_message_is_forwarded_to_nvidia_nim_and_reply_is_returned(): void
    {
        config([
            'services.nvidia_nim.chat_enabled' => true,
            'services.nvidia_nim.base_url' => 'https://integrate.api.nvidia.com/v1/',
            'services.nvidia_nim.key' => 'nim-test-secret',
            'services.nvidia_nim.model' => 'openai/gpt-oss-20b',
        ]);

        Http::fake([
            'https://integrate.api.nvidia.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => ['content' => 'Saya turut mendengar. Apa yang paling terasa berat?'],
                ]],
            ], 200),
        ]);

        $student = User::factory()->create([
            'role' => 'mahasiswa',
            'is_active' => true,
        ]);

        $this->actingAs($student)
            ->postJson('/mahasiswa/teman-bicara', [
                'message' => 'Saya merasa cemas',
                'history' => [],
            ])
            ->assertOk()
            ->assertJsonPath('reply', 'Saya turut mendengar. Apa yang paling terasa berat?');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://integrate.api.nvidia.com/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer nim-test-secret')
                && $request['model'] === 'openai/gpt-oss-20b'
                && $request['messages'][0]['role'] === 'system'
                && str_contains($request['messages'][0]['content'], 'Jangan mendiagnosis')
                && $request['messages'][1] === ['role' => 'user', 'content' => 'Saya merasa cemas'];
        });
    }

    public function test_chat_rejects_oversized_messages_without_calling_nvidia_nim(): void
    {
        config([
            'services.nvidia_nim.chat_enabled' => true,
            'services.nvidia_nim.base_url' => 'https://integrate.api.nvidia.com/v1',
            'services.nvidia_nim.key' => 'nim-test-secret',
        ]);

        Http::fake();

        $student = User::factory()->create([
            'role' => 'mahasiswa',
            'is_active' => true,
        ]);

        $this->actingAs($student)
            ->postJson('/mahasiswa/teman-bicara', ['message' => str_repeat('a', 2001)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('message');

        Http::assertNothingSent();
    }
}
