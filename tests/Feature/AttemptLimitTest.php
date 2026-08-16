<?php

use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Models\QuizQuestion;
use Workbench\App\Models\User;

it('rejects starting a new attempt once max_attempts is reached', function () {
    $user = User::factory()->create();
    QuizQuestion::factory()->create();
    $quiz = Quiz::factory()->create(['question_count' => 1, 'max_attempts' => 1]);

    $this->actingAs($user);

    $start = $this->postJson("/api/quiz/quizzes/{$quiz->id}/attempts");
    $attemptId = $start->json('attempt.id');
    $questionId = $start->json('questions.0.id');
    $answerId = $start->json('questions.0.answers.0.id');

    $this->postJson("/api/quiz/quizzes/{$quiz->id}/attempts/{$attemptId}/submit", [
        'answers' => [$questionId => [$answerId]],
    ])->assertOk();

    $this->postJson("/api/quiz/quizzes/{$quiz->id}/attempts")
        ->assertStatus(403)
        ->assertJsonPath('error_code', 'quiz.max_attempts_reached');
});
