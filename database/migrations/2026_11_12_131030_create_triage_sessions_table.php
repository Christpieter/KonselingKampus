<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('triage_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $t->json('answers');                    // DASS-21 answers (21 items, 0-3 each)
            $t->json('conversation_log');           // Full chat transcript
            $t->unsignedSmallInteger('depression_score')->default(0);  // 0-63 (x2 for DASS-21)
            $t->unsignedSmallInteger('anxiety_score')->default(0);     // 0-63
            $t->unsignedSmallInteger('stress_score')->default(0);      // 0-63
            $t->unsignedSmallInteger('total_score')->default(0);       // 0-189
            $t->string('depression_level', 20)->default('normal');     // normal/mild/moderate/severe/extremely_severe
            $t->string('anxiety_level', 20)->default('normal');
            $t->string('stress_level', 20)->default('normal');
            $t->boolean('needs_priority')->default(false);             // Any severe/extreme flag
            $t->string('status', 20)->default('in_progress');          // in_progress/completed/archived
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();

            $t->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('triage_sessions');
    }
};