<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\CounselorProfile;
use App\Models\CounselingReport;
use App\Models\ProblemType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CounselingReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_counselor_can_request_a_report_for_a_completed_booking(): void
    {
        config([
            'services.n8n.counseling_report_webhook_url' => 'https://n8n.example/webhook/report',
            'services.n8n.webhook_secret' => 'test-webhook-secret',
        ]);
        Http::fake(['https://n8n.example/webhook/report' => Http::response(['accepted' => true])]);

        [$counselor, $booking] = $this->completedBooking();
        CounselorProfile::create([
            'user_id' => $counselor->id,
            'graduate_field' => 'Psikologi',
            'education' => 'S2',
            'specializations' => 'Konseling',
            'status' => 'approved',
        ]);

        $this->actingAs($counselor)
            ->get(route('counselor.dashboard'))
            ->assertOk()
            ->assertSee('Rekap data')
            ->assertSee('Buat PDF rekap sesi melalui n8n.');

        $this->actingAs($counselor)
            ->post(route('counselor.report.request', $booking))
            ->assertRedirect();

        $report = CounselingReport::where('booking_id', $booking->id)->firstOrFail();
        $this->assertSame('processing', $report->status);

        Http::assertSent(fn ($request) => $request->url() === 'https://n8n.example/webhook/report'
            && $request->hasHeader('X-Webhook-Secret', 'test-webhook-secret')
            && $request['report_id'] === $report->id
            && $request['report']['student']['name'] === $booking->student->name
            && $request['report']['session']['counselor_note'] === 'Catatan sesi privat');
    }

    public function test_n8n_callback_stores_pdf_and_only_the_counselor_can_download_it(): void
    {
        config(['services.n8n.webhook_secret' => 'test-webhook-secret']);
        Storage::fake('local');
        [$counselor, $booking] = $this->completedBooking();
        $token = str_repeat('a', 64);
        $report = CounselingReport::create([
            'booking_id' => $booking->id,
            'counselor_id' => $counselor->id,
            'status' => 'processing',
            'callback_token_hash' => hash('sha256', $token),
        ]);
        $pdf = "%PDF-1.4\nTest report\n%%EOF";

        $this->withHeader('X-Webhook-Secret', 'test-webhook-secret')
            ->postJson(route('webhook.n8n.counseling-report'), [
                'report_id' => $report->id,
                'callback_token' => $token,
                'pdf_base64' => base64_encode($pdf),
            ])
            ->assertOk();

        $report->refresh();
        $this->assertSame('completed', $report->status);
        Storage::disk('local')->assertExists($report->file_path);

        $this->actingAs($counselor)
            ->get(route('counselor.report.download', $report))
            ->assertOk()
            ->assertDownload("rekap-sesi-{$booking->id}.pdf");

        $anotherCounselor = User::factory()->create([
            'role' => 'konselor',
            'is_active' => true,
        ]);
        $this->actingAs($anotherCounselor)
            ->get(route('counselor.report.download', $report))
            ->assertForbidden();
    }

    public function test_report_is_not_available_for_another_counselors_booking(): void
    {
        [$owner, $booking] = $this->completedBooking();
        $anotherCounselor = User::factory()->create([
            'role' => 'konselor',
            'is_active' => true,
        ]);

        $this->actingAs($anotherCounselor)
            ->post(route('counselor.report.request', $booking))
            ->assertForbidden();
    }

    private function completedBooking(): array
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
            'answers' => [0, 1, 2],
            'score' => 3,
            'needs_priority' => false,
            'session_date' => today(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => 'completed',
            'counselor_note' => 'Catatan sesi privat',
        ]);

        return [$counselor, $booking];
    }
}
