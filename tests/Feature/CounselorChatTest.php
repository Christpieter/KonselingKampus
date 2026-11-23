<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\ProblemType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CounselorChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_counselor_can_complete_a_session_from_the_chat_room(): void
    {
        $counselor = User::factory()->create([
            'role' => 'konselor',
            'is_active' => true,
        ]);
        $student = User::factory()->create([
            'role' => 'mahasiswa',
            'is_active' => true,
        ]);
        $problemType = ProblemType::create(['name' => 'Akademik']);
        $booking = Booking::create([
            'student_id' => $student->id,
            'counselor_id' => $counselor->id,
            'problem_type_id' => $problemType->id,
            'story' => 'Membutuhkan sesi konseling.',
            'answers' => [],
            'score' => 0,
            'session_date' => today()->addDay(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => 'confirmed',
        ]);

        $this->actingAs($counselor)
            ->get(route('counselor.chat.index', $booking))
            ->assertOk()
            ->assertSee('Selesaikan sesi')
            ->assertSee(route('counselor.complete', $booking), false)
            ->assertDontSee('Tombol selesaikan sesi aktif pada hari pelaksanaan.');

        $this->actingAs($counselor)
            ->get(route('counselor.booking', $booking))
            ->assertOk()
            ->assertSee('Tandai selesai')
            ->assertDontSee('Tombol "selesai" aktif pada hari sesi.');

        $this->post(route('counselor.complete', $booking))
            ->assertRedirect(route('counselor.dashboard'));

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'completed',
        ]);
    }
}
