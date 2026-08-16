<?php

use Illuminate\Support\Facades\Gate;
use Osoobe\Quiz\Enums\AttemptStatus;
use Osoobe\Quiz\Exceptions\MaxAttemptsReachedException;
use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Models\QuizAttempt;
use Osoobe\Quiz\Services\AttemptLimiter;
use Workbench\App\Models\User;

beforeEach(function () {
    config(['quiz.auth_driver' => 'gate']);
});

it('allows starting while under max_attempts and blocks at the limit', function () {
    $user = User::factory()->create();
    $quiz = Quiz::factory()->create(['max_attempts' => 2]);

    app(AttemptLimiter::class)->assertMayStart($user, $quiz);

    QuizAttempt::create([
        'quiz_id' => $quiz->id, 'user_id' => (string) $user->getKey(), 'started_at' => now(),
        'total_questions' => 1, 'answers' => [], 'status' => AttemptStatus::Completed->value,
    ]);

    app(AttemptLimiter::class)->assertMayStart($user, $quiz); // 1 used, 2 allowed — still fine

    QuizAttempt::create([
        'quiz_id' => $quiz->id, 'user_id' => (string) $user->getKey(), 'started_at' => now(),
        'total_questions' => 1, 'answers' => [], 'status' => AttemptStatus::Completed->value,
    ]);

    expect(fn () => app(AttemptLimiter::class)->assertMayStart($user, $quiz))
        ->toThrow(MaxAttemptsReachedException::class);
});

it('counts incomplete attempts toward the limit when count_incomplete_attempts is true', function () {
    config(['quiz.count_incomplete_attempts' => true]);

    $user = User::factory()->create();
    $quiz = Quiz::factory()->create(['max_attempts' => 1]);

    QuizAttempt::create([
        'quiz_id' => $quiz->id, 'user_id' => (string) $user->getKey(), 'started_at' => now(),
        'total_questions' => 1, 'answers' => [], 'status' => AttemptStatus::InProgress->value,
    ]);

    expect(fn () => app(AttemptLimiter::class)->assertMayStart($user, $quiz))
        ->toThrow(MaxAttemptsReachedException::class);
});

it('ignores incomplete attempts when count_incomplete_attempts is false', function () {
    config(['quiz.count_incomplete_attempts' => false]);

    $user = User::factory()->create();
    $quiz = Quiz::factory()->create(['max_attempts' => 1]);

    QuizAttempt::create([
        'quiz_id' => $quiz->id, 'user_id' => (string) $user->getKey(), 'started_at' => now(),
        'total_questions' => 1, 'answers' => [], 'status' => AttemptStatus::Abandoned->value,
    ]);

    app(AttemptLimiter::class)->assertMayStart($user, $quiz);

    expect(true)->toBeTrue(); // reaching here means no exception was thrown
});

it('lets staff bypass the limit entirely', function () {
    Gate::define('quiz.staff', fn () => true);

    $user = User::factory()->create();
    $quiz = Quiz::factory()->create(['max_attempts' => 1]);

    QuizAttempt::create([
        'quiz_id' => $quiz->id, 'user_id' => (string) $user->getKey(), 'started_at' => now(),
        'total_questions' => 1, 'answers' => [], 'status' => AttemptStatus::Completed->value,
    ]);

    app(AttemptLimiter::class)->assertMayStart($user, $quiz);

    expect(true)->toBeTrue();
});
