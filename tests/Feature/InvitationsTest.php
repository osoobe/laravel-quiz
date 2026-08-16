<?php

use Osoobe\Quiz\Models\Quiz;
use Workbench\App\Models\User;

it('lets the quiz creator invite users by email, in bulk, and reports duplicates', function () {
    $creator = User::factory()->create();
    $alice = User::factory()->create(['email' => 'alice@example.com']);
    $bob = User::factory()->create(['email' => 'bob@example.com']);
    $quiz = Quiz::factory()->private()->create(['created_by' => (string) $creator->getKey()]);

    $response = $this->actingAs($creator)
        ->postJson("/api/quiz/admin/quizzes/{$quiz->id}/invitations", [
            'identifiers' => "alice@example.com, bob@example.com\nghost@example.com",
        ])
        ->assertOk();

    expect($response->json('invited'))->toBe(2);
    expect($response->json('not_found'))->toBe(1);

    // Re-inviting the same user is reported as a duplicate, not an error.
    $this->actingAs($creator)
        ->postJson("/api/quiz/admin/quizzes/{$quiz->id}/invitations", ['identifiers' => ['alice@example.com']])
        ->assertOk()
        ->assertJsonPath('already_invited', 1);
});

it('blocks a stranger from managing invitations for a quiz they do not own', function () {
    $creator = User::factory()->create();
    $stranger = User::factory()->create();
    $quiz = Quiz::factory()->private()->create(['created_by' => (string) $creator->getKey()]);

    $this->actingAs($stranger)
        ->getJson("/api/quiz/admin/quizzes/{$quiz->id}/invitations")
        ->assertStatus(403);
});
