<?php

use Osoobe\Quiz\Enums\AttemptStatus;
use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Models\QuizAttempt;
use Workbench\App\Models\User;

it('is public and orders entries by score desc, then completed_at asc', function () {
    $quiz = Quiz::factory()->create();

    $low = User::factory()->create();
    $high = User::factory()->create();

    QuizAttempt::create([
        'quiz_id' => $quiz->id, 'user_id' => (string) $low->getKey(), 'started_at' => now(),
        'completed_at' => now(), 'score' => 40, 'total_questions' => 10, 'correct_answers' => 4,
        'answers' => [], 'status' => AttemptStatus::Completed->value,
    ]);
    QuizAttempt::create([
        'quiz_id' => $quiz->id, 'user_id' => (string) $high->getKey(), 'started_at' => now(),
        'completed_at' => now(), 'score' => 90, 'total_questions' => 10, 'correct_answers' => 9,
        'answers' => [], 'status' => AttemptStatus::Completed->value,
    ]);
    // in_progress attempts must never appear on the leaderboard.
    QuizAttempt::create([
        'quiz_id' => $quiz->id, 'user_id' => (string) User::factory()->create()->getKey(), 'started_at' => now(),
        'total_questions' => 10, 'answers' => [], 'status' => AttemptStatus::InProgress->value,
    ]);

    $response = $this->getJson("/api/quiz/quizzes/{$quiz->id}/leaderboard")->assertOk();

    expect($response->json('entries'))->toHaveCount(2);
    expect($response->json('entries.0.score'))->toBe(90);
    expect($response->json('entries.1.score'))->toBe(40);
    expect($response->json('entries.0.user'))->not->toHaveKey('email');
});

it('collapses to one entry per user when best_per_user is enabled', function () {
    config(['quiz.leaderboard.best_per_user' => true]);

    $quiz = Quiz::factory()->create();
    $user = User::factory()->create();

    QuizAttempt::create([
        'quiz_id' => $quiz->id, 'user_id' => (string) $user->getKey(), 'started_at' => now(),
        'completed_at' => now(), 'score' => 90, 'total_questions' => 10, 'correct_answers' => 9,
        'answers' => [], 'status' => AttemptStatus::Completed->value,
    ]);
    QuizAttempt::create([
        'quiz_id' => $quiz->id, 'user_id' => (string) $user->getKey(), 'started_at' => now(),
        'completed_at' => now(), 'score' => 50, 'total_questions' => 10, 'correct_answers' => 5,
        'answers' => [], 'status' => AttemptStatus::Completed->value,
    ]);

    $response = $this->getJson("/api/quiz/quizzes/{$quiz->id}/leaderboard")->assertOk();

    expect($response->json('entries'))->toHaveCount(1);
    expect($response->json('entries.0.score'))->toBe(90);
});
