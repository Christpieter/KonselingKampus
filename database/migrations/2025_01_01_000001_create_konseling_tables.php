<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role', 20)->default('mahasiswa')->after('password')->index();
            $t->string('nim', 30)->nullable()->after('role');
            $t->string('phone', 30)->nullable()->after('nim');
            $t->boolean('is_active')->default(true)->after('phone');
        });

        Schema::create('counselor_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('graduate_field');
            $t->string('education');
            $t->text('specializations');
            $t->text('bio')->nullable();
            $t->string('status', 20)->default('pending'); // pending|approved|rejected
            $t->timestamps();
        });

        Schema::create('certifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('counselor_profile_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('issuer')->nullable();
            $t->unsignedSmallInteger('year')->nullable();
            $t->timestamps();
        });

        Schema::create('problem_types', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->string('description')->nullable();
            $t->timestamps();
        });

        Schema::create('questions', function (Blueprint $t) {
            $t->id();
            $t->unsignedTinyInteger('position');
            $t->string('text');
            $t->timestamps();
        });

        Schema::create('availabilities', function (Blueprint $t) {
            $t->id();
            $t->foreignId('counselor_id')->constrained('users')->cascadeOnDelete();
            $t->unsignedTinyInteger('day_of_week'); // 1 = Senin ... 7 = Minggu
            $t->time('start_time');
            $t->time('end_time');
            $t->timestamps();
            $t->unique(['counselor_id', 'day_of_week', 'start_time']);
        });

        Schema::create('bookings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('counselor_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('problem_type_id')->constrained();
            $t->text('story');
            $t->json('answers');
            $t->unsignedTinyInteger('score');
            $t->boolean('needs_priority')->default(false);
            $t->date('session_date');
            $t->time('start_time');
            $t->time('end_time');
            $t->string('status', 20)->default('pending'); // pending|confirmed|rejected|completed|cancelled
            $t->text('counselor_note')->nullable();
            $t->timestamps();
            $t->index(['counselor_id', 'session_date', 'start_time']);
        });

        Schema::create('ratings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('counselor_id')->constrained('users')->cascadeOnDelete();
            $t->unsignedTinyInteger('stars');
            $t->text('comment')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('availabilities');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('problem_types');
        Schema::dropIfExists('certifications');
        Schema::dropIfExists('counselor_profiles');
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['role', 'nim', 'phone', 'is_active']);
        });
    }
};
