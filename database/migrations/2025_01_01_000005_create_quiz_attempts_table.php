<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->uuid('user_id')->index();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->unsignedInteger('total_questions')->nullable();
            $table->unsignedInteger('correct_answers')->nullable();
            $table->json('answers');
            $table->string('status', 16)->default('in_progress');
            $table->timestamps();

            $table->index(['quiz_id', 'status', 'score', 'completed_at'], 'quiz_attempts_leaderboard_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
