<?php

namespace Tests\Feature;

use App\Models\ProblemType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentIntakeTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_submit_intake_without_booking_questionnaire_answers(): void
    {
        $student = User::factory()->create([
            'role' => 'mahasiswa',
            'is_active' => true,
        ]);
        $problemType = ProblemType::create(['name' => 'Akademik']);
        $bookingDraft = ['triage_session_id' => 1];

        $this->actingAs($student)
            ->withSession(['booking_draft' => $bookingDraft])
            ->get(route('student.booking.intake'))
            ->assertOk()
            ->assertDontSee('3. Kuesioner')
            ->assertDontSee('name="answers[');

        $this->actingAs($student)
            ->withSession(['booking_draft' => $bookingDraft])
            ->post(route('student.booking.intake.save'), [
                'problem_type_id' => $problemType->id,
                'story' => 'Saya ingin membicarakan tekanan yang saya alami di kampus.',
            ])
            ->assertRedirect(route('student.booking.counselors'))
            ->assertSessionMissing('booking_draft.answers')
            ->assertSessionHas('booking_draft.story');
    }
}
