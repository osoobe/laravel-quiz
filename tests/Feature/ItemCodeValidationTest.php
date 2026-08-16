<?php

use Illuminate\Support\Facades\Gate;
use Osoobe\Quiz\Models\QuizTopic;
use Workbench\App\Models\User;

beforeEach(function () {
    Gate::define('quiz.staff', fn () => true);
});

it('rejects an itemcode shorter than 6 characters', function () {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/quiz/admin/topics', ['name' => 'Topic A', 'itemcode' => 'AB1'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('itemcode');
});

it('rejects an itemcode with characters outside alphanumeric/dash/underscore', function () {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/quiz/admin/topics', ['name' => 'Topic B', 'itemcode' => 'CODE 01!'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('itemcode');
});

it('accepts a well-formed itemcode with dashes and underscores', function () {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/quiz/admin/topics', ['name' => 'Topic C', 'itemcode' => 'TOPIC-01_A'])
        ->assertCreated()
        ->assertJsonPath('data.itemcode', 'TOPIC-01_A');
});

it('rejects a duplicate itemcode on create', function () {
    QuizTopic::factory()->create(['itemcode' => 'DUP-CODE1']);

    $this->actingAs(User::factory()->create())
        ->postJson('/api/quiz/admin/topics', ['name' => 'Another Topic', 'itemcode' => 'DUP-CODE1'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('itemcode');
});

it('allows updating a topic while keeping its own itemcode', function () {
    $topic = QuizTopic::factory()->create(['itemcode' => 'KEEP-MINE']);

    $this->actingAs(User::factory()->create())
        ->putJson("/api/quiz/admin/topics/{$topic->id}", [
            'name' => 'Renamed',
            'itemcode' => 'KEEP-MINE',
        ])
        ->assertOk()
        ->assertJsonPath('data.itemcode', 'KEEP-MINE');
});

it('rejects updating a topic to use another topic\'s itemcode', function () {
    QuizTopic::factory()->create(['itemcode' => 'TAKEN-001']);
    $topic = QuizTopic::factory()->create(['itemcode' => 'MINE-0001']);

    $this->actingAs(User::factory()->create())
        ->putJson("/api/quiz/admin/topics/{$topic->id}", [
            'name' => $topic->name,
            'itemcode' => 'TAKEN-001',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('itemcode');
});

it('enforces the same itemcode rules for categories', function () {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/quiz/admin/categories', ['name' => 'Category A', 'itemcode' => 'short'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('itemcode');
});

it('leaves itemcode auto-generated when not provided at all', function () {
    $response = $this->actingAs(User::factory()->create())
        ->postJson('/api/quiz/admin/categories', ['name' => 'Category B'])
        ->assertCreated();

    expect($response->json('data.itemcode'))->not->toBeNull();
});
