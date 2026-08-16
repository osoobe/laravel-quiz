<?php

use Illuminate\Support\Str;
use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Models\QuizInvitation;
use Workbench\App\Models\User;

it('denies a private quiz to an uninvited user with the access_denied error code', function () {
    $user = User::factory()->create();
    $quiz = Quiz::factory()->private()->create();

    $this->actingAs($user)
        ->getJson("/api/quiz/quizzes/{$quiz->id}")
        ->assertStatus(403)
        ->assertJsonPath('error_code', 'quiz.access_denied');
});

it('allows a private quiz once the user has an invitation', function () {
    $user = User::factory()->create();
    $quiz = Quiz::factory()->private()->create();

    QuizInvitation::create([
        'quiz_id' => $quiz->id,
        'user_id' => (string) $user->getKey(),
        'invited_by' => $quiz->created_by,
    ]);

    $this->actingAs($user)
        ->getJson("/api/quiz/quizzes/{$quiz->id}")
        ->assertOk();
});

it('reports an inactive quiz with the inactive error code, distinct from access_denied', function () {
    $user = User::factory()->create();
    $quiz = Quiz::factory()->create(['audience' => 'everyone', 'is_active' => false]);

    $this->actingAs($user)
        ->getJson("/api/quiz/quizzes/{$quiz->id}")
        ->assertStatus(403)
        ->assertJsonPath('error_code', 'quiz.inactive');
});

it('excludes scoped quizzes from the public catalogue', function () {
    Quiz::factory()->create(['audience' => 'everyone', 'name' => 'Visible Quiz']);
    Quiz::factory()->create(['audience' => 'scope-'.Str::uuid(), 'name' => 'Scoped Quiz']);

    $names = $this->getJson('/api/quiz/quizzes')->json('data.*.name');

    expect($names)->toContain('Visible Quiz')->not->toContain('Scoped Quiz');
});
