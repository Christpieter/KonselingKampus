<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    protected $fillable = [
        'student_id', 'counselor_id', 'problem_type_id', 'story', 'answers', 'score',
        'triage_session_id', 'needs_priority', 'session_date', 'start_time', 'end_time', 'status', 'counselor_note',
    ];

    /** Status yang menahan slot: slot tidak bisa dipesan orang lain. */
    public const OCCUPYING = ['pending', 'confirmed'];

    public const STATUS_LABEL = [
        'pending' => 'Menunggu konfirmasi',
        'confirmed' => 'Dikonfirmasi',
        'rejected' => 'Ditolak',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'needs_priority' => 'boolean',
            'session_date' => 'date',
        ];
    }

    public function student(): BelongsTo { return $this->belongsTo(User::class, 'student_id'); }
    public function counselor(): BelongsTo { return $this->belongsTo(User::class, 'counselor_id'); }
    public function problemType(): BelongsTo { return $this->belongsTo(ProblemType::class); }
    public function triageSession(): BelongsTo { return $this->belongsTo(TriageSession::class); }
    public function rating(): HasOne { return $this->hasOne(Rating::class); }
    public function messages(): HasMany { return $this->hasMany(Message::class); }
    public function report(): HasOne { return $this->hasOne(CounselingReport::class); }

    public function statusLabel(): string
    {
        return self::STATUS_LABEL[$this->status] ?? $this->status;
    }

    public function timeRange(): string
    {
        return substr($this->start_time, 0, 5).' - '.substr($this->end_time, 0, 5);
    }

    /** Kategori skor kuesioner (0-30). Bukan diagnosis. */
    public static function scoreLevel(int $score): string
    {
        return $score >= 20 ? 'Tinggi' : ($score >= 10 ? 'Sedang' : 'Rendah');
    }
}