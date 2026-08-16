<?php

use Illuminate\Support\Facades\Gate;
use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Models\QuizCategory;
use Osoobe\Quiz\Models\QuizQuestion;
use Osoobe\Quiz\Models\QuizTopic;
use Workbench\App\Models\User;

it('bundles topics, categories, questions, and quizzes into a single export', function () {
    Gate::define('quiz.staff', fn () => true);

    $topic = QuizTopic::factory()->create(['name' => 'Recursion']);
    $category = QuizCategory::factory()->create(['name' => 'Algorithms']);
    QuizQuestion::factory()->create(['topic_id' => $topic->id, 'category_id' => $category->id]);
    Quiz::factory()->create(['name' => 'Sample Quiz', 'topic_ids' => [$topic->id], 'category_ids' => [$category->id]]);

    $response = $this->actingAs(User::factory()->create())
        ->getJson('/api/quiz/admin/export-all')
        ->assertOk();

    expect($response->json('topics'))->toHaveCount(1);
    expect($response->json('categories'))->toHaveCount(1);
    expect($response->json('questions'))->toHaveCount(1);
    expect($response->json('quizzes'))->toHaveCount(1);

    expect($response->json('quizzes.0.name'))->toBe('Sample Quiz');
    expect($response->json('quizzes.0.topics'))->toBe(['Recursion']);
    expect($response->json('quizzes.0.categories'))->toBe(['Algorithms']);
    expect($response->json('quizzes.0.itemcode'))->not->toBeNull();
});

it('blocks a non-staff user from the combined export', function () {
    Gate::define('quiz.staff', fn () => false);

    $this->actingAs(User::factory()->create())
        ->getJson('/api/quiz/admin/export-all')
        ->assertStatus(403);
});
