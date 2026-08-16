<?php

use Illuminate\Support\Facades\Gate;
use Osoobe\Quiz\Models\Quiz;
use Workbench\App\Models\User;

it('blocks a non-staff user from the staff-only topics endpoint', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/quiz/admin/topics', ['name' => 'Nope'])
        ->assertStatus(403);
});

it('allows a staff user to manage topics', function () {
    Gate::define('quiz.staff', fn () => true);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/quiz/admin/topics', ['name' => 'Software Engineering'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Software Engineering');
});

it('lets a non-staff quiz creator update and delete their own quiz, but not someone else\'s', function () {
    $creator = User::factory()->create();
    $stranger = User::factory()->create();
    $quiz = Quiz::factory()->create(['created_by' => (string) $creator->getKey(), 'name' => 'Original']);

    $this->actingAs($creator)
        ->putJson("/api/quiz/admin/quizzes/{$quiz->id}", [
            'name' => 'Renamed',
            'question_count' => $quiz->question_count,
            'max_attempts' => $quiz->max_attempts,
            'audience' => $quiz->audience,
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Renamed');

    $this->actingAs($stranger)
        ->deleteJson("/api/quiz/admin/quizzes/{$quiz->id}")
        ->assertStatus(403);

    $this->actingAs($creator)
        ->deleteJson("/api/quiz/admin/quizzes/{$quiz->id}")
        ->assertOk();

    expect(Quiz::find($quiz->id))->toBeNull();
});

it('blocks a non-staff, non-creator user from listing all quizzes in the admin index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/quiz/admin/quizzes')
        ->assertStatus(403);
});
