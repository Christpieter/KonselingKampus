<?php

namespace App\Http\Controllers;

use App\Models\TriageSession;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class N8nTriageWebhookController extends Controller
{
    public function receive(Request $request): JsonResponse
    {
        // Validate webhook secret
        $secret = $request->header('X-Webhook-Secret');
        $expectedSecret = config('services.n8n.webhook_secret');

        if (! $expectedSecret || ! hash_equals($expectedSecret, $secret)) {
            Log::warning('n8n triage webhook: invalid secret');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'triage_session_id' => ['required', 'integer', 'exists:triage_sessions,id'],
            'answers' => ['required', 'array', 'size:10'],
            'answers.*' => ['integer', 'between:0,3'],
        ]);

        $triageSession = TriageSession::findOrFail($data['triage_session_id']);

        $answers = $data['answers'];

        // Calculate 10-item triage scores
        // Anxiety: items 0,1,2 (3 items, raw 0-9)
        // Depression: items 3,4,5 (3 items, raw 0-9)
        // Stress: items 6,7,8,9 (4 items, raw 0-12)
        $anxietyItems = [0, 1, 2];
        $depressionItems = [3, 4, 5];
        $stressItems = [6, 7, 8, 9];

        $anxietyRaw = array_sum(array_intersect_key($answers, array_flip($anxietyItems)));
        $depressionRaw = array_sum(array_intersect_key($answers, array_flip($depressionItems)));
        $stressRaw = array_sum(array_intersect_key($answers, array_flip($stressItems)));

        // No multiplication by 2 (unlike DASS-21). Scores are raw sums.
        $anxietyScore = $anxietyRaw;
        $depressionScore = $depressionRaw;
        $stressScore = $stressRaw;
        $totalScore = $anxietyScore + $depressionScore + $stressScore;

        // Determine levels
        $anxietyLevel = TriageSession::calculateLevel('anxiety', $anxietyScore);
        $depressionLevel = TriageSession::calculateLevel('depression', $depressionScore);
        $stressLevel = TriageSession::calculateLevel('stress', $stressScore);

        $needsPriority = TriageSession::requiresPriority($depressionLevel, $anxietyLevel, $stressLevel);

        // Update triage session
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

        Log::info('n8n triage webhook: scores calculated', [
            'triage_session_id' => $triageSession->id,
            'anxiety' => $anxietyScore,
            'depression' => $depressionScore,
            'stress' => $stressScore,
            'total' => $totalScore,
            'needs_priority' => $needsPriority,
        ]);

        return response()->json([
            'message' => 'Skor triage berhasil dihitung dan disimpan.',
            'triage_session_id' => $triageSession->id,
        ]);
    }

    /**
     * Alternative: Calculate scores directly in Laravel (if n8n not used)
     * This can be called from frontend after triage completes
     */
    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'triage_session_id' => ['required', 'integer', 'exists:triage_sessions,id'],
            'answers' => ['required', 'array', 'size:10'],
            'answers.*' => ['integer', 'between:0,3'],
        ]);

        $student = auth()->user();
        $triageSession = TriageSession::where('id', $request->triage_session_id)
            ->where('student_id', $student->id)
            ->where('status', 'in_progress')
            ->firstOrFail();

        $answers = $request->input('answers');

        $anxietyItems = [0, 1, 2];
        $depressionItems = [3, 4, 5];
        $stressItems = [6, 7, 8, 9];

        $anxietyRaw = array_sum(array_intersect_key($answers, array_flip($anxietyItems)));
        $depressionRaw = array_sum(array_intersect_key($answers, array_flip($depressionItems)));
        $stressRaw = array_sum(array_intersect_key($answers, array_flip($stressItems)));

        $anxietyScore = $anxietyRaw;
        $depressionScore = $depressionRaw;
        $stressScore = $stressRaw;
        $totalScore = $anxietyScore + $depressionScore + $stressScore;

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
            'triage_session' => [
                'id' => $triageSession->id,
                'anxiety_score' => $anxietyScore,
                'depression_score' => $depressionScore,
                'stress_score' => $stressScore,
                'total_score' => $totalScore,
                'anxiety_level' => $anxietyLevel,
                'depression_level' => $depressionLevel,
                'stress_level' => $stressLevel,
                'needs_priority' => $needsPriority,
                'level_labels' => [
                    'anxiety' => $triageSession->levelLabel('anxiety'),
                    'depression' => $triageSession->levelLabel('depression'),
                    'stress' => $triageSession->levelLabel('stress'),
                ],
            ],
        ]);
    }
}