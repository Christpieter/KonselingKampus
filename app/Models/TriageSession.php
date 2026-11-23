<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TriageSession extends Model
{
    protected $fillable = [
        'student_id',
        'answers',
        'conversation_log',
        'depression_score',
        'anxiety_score',
        'stress_score',
        'total_score',
        'depression_level',
        'anxiety_level',
        'stress_level',
        'needs_priority',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'conversation_log' => 'array',
        'needs_priority' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public const LEVELS = [
        'normal' => 'Normal',
        'mild' => 'Ringan',
        'moderate' => 'Sedang',
        'severe' => 'Berat',
        'extremely_severe' => 'Sangat Berat',
    ];

    public const STATUS_LABELS = [
        'in_progress' => 'Sedang Berlangsung',
        'completed' => 'Selesai',
        'archived' => 'Diarsipkan',
    ];

    /** Hitung level berdasarkan skor triage 10-item (raw sum, tidak dikali 2) */
    public static function calculateLevel(string $subscale, int $score): string
    {
        return match ($subscale) {
            // Anxiety & Depression: 3 items each, raw 0-9
            'anxiety', 'depression' => match (true) {
                $score <= 2 => 'normal',
                $score <= 4 => 'mild',
                $score <= 6 => 'moderate',
                $score <= 8 => 'severe',
                default => 'extremely_severe',
            },
            // Stress: 4 items, raw 0-12
            'stress' => match (true) {
                $score <= 3 => 'normal',
                $score <= 6 => 'mild',
                $score <= 8 => 'moderate',
                $score <= 10 => 'severe',
                default => 'extremely_severe',
            },
            default => 'normal',
        };
    }

    /** Apakah butuh prioritas (skor severe atau extremely severe di subskala manapun) */
    public static function requiresPriority(string $depressionLevel, string $anxietyLevel, string $stressLevel): bool
    {
        $priorityLevels = ['severe', 'extremely_severe'];
        return in_array($depressionLevel, $priorityLevels, true)
            || in_array($anxietyLevel, $priorityLevels, true)
            || in_array($stressLevel, $priorityLevels, true);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function levelLabel(string $subscale): string
    {
        $level = $this->{$subscale . '_level'};
        return self::LEVELS[$level] ?? $level;
    }
}