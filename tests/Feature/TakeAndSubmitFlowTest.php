<?php

use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Models\QuizQuestion;
use Osoobe\Quiz\Models\QuizTopic;
use Workbench\App\Models\User;

it('lets an authenticated user take a public quiz end to end via the API', function () {
    $user = User::factory()->create();
    $topic = QuizTopic::factory()->create();
    QuizQuestion::factory()->count(3)->create(['topic_id' => $topic->id]);

    $quiz = Quiz::factory()->create([
        'topic_ids' => [$topic->id],
        'question_count' => 3,
        'audience' => 'everyone',
    ]);

    $this->actingAs($user);

    $this->getJson("/api/quiz/quizzes/{$quiz->id}")
        ->assertOk()
        ->assertJsonPath('attempt', null);

    $start = $this->postJson("/api/quiz/quizzes/{$quiz->id}/attempts");
    $start->assertOk();
    $attemptId = $start->json('attempt.id');
    expect($start->json('questions'))->toHaveCount(3);

    foreach ($start->json('questions') as $question) {
        expect($question['answers'][0])->not->toHaveKey('is_correct');
    }

    $answers = [];
    foreach ($start->json('questions') as $question) {
        $answers[$question['id']] = [$question['answers'][0]['id']];
    }

    $submit = $this->postJson("/api/quiz/quizzes/{$quiz->id}/attempts/{$attemptId}/submit", ['answers' => $answers]);
    $submit->assertOk()->assertJsonStructure(['score', 'correct_answers', 'total_questions', 'status']);
    expect($submit->json('status'))->toBe('completed');

    $this->postJson("/api/quiz/quizzes/{$quiz->id}/attempts/{$attemptId}/submit", ['answers' => $answers])
        ->assertStatus(403)
        ->assertJsonPath('error_code', 'quiz.attempt_already_completed');
});

it('autosaves answers via PATCH without scoring', function () {
    $user = User::factory()->create();
    QuizQuestion::factory()->create();
    $quiz = Quiz::factory()->create(['question_count' => 1]);

    $this->actingAs($user);
    $start = $this->postJson("/api/quiz/quizzes/{$quiz->id}/attempts");
    $attemptId = $start->json('attempt.id');
    $questionId = $start->json('questions.0.id');
    $answerId = $start->json('questions.0.answers.0.id');

    $patch = $this->patchJson("/api/quiz/quizzes/{$quiz->id}/attempts/{$attemptId}", [
        'answers' => [$questionId => [$answerId]],
    ]);

    $patch->assertOk();
    expect($patch->json("data.answers.{$questionId}"))->toBe([$answerId]);
});

it('blocks a guest from starting an attempt with a clean 401, not a crash', function () {
    QuizQuestion::factory()->create();
    $quiz = Quiz::factory()->create(['audience' => 'everyone', 'question_count' => 1]);

    $this->postJson("/api/quiz/quizzes/{$quiz->id}/attempts")->assertStatus(401);
});

it('resumes an in-progress attempt instead of starting a new one', function () {
    $user = User::factory()->create();
    QuizQuestion::factory()->create();
    $quiz = Quiz::factory()->create(['question_count' => 1, 'max_attempts' => 1]);

    $this->actingAs($user);
    $first = $this->postJson("/api/quiz/quizzes/{$quiz->id}/attempts");
    $second = $this->postJson("/api/quiz/quizzes/{$quiz->id}/attempts");

    expect($second->json('attempt.id'))->toBe($first->json('attempt.id'));
});

it('rejects a submission with an answer id that does not belong to the question', function () {
    $user = User::factory()->create();
    QuizQuestion::factory()->create();
    $quiz = Quiz::factory()->create(['question_count' => 1]);

    $this->actingAs($user);
    $start = $this->postJson("/api/quiz/quizzes/{$quiz->id}/attempts");
    $attemptId = $start->json('attempt.id');
    $questionId = $start->json('questions.0.id');

    $this->postJson("/api/quiz/quizzes/{$quiz->id}/attempts/{$attemptId}/submit", [
        'answers' => [$questionId => ['not-a-real-answer-id']],
    ])->assertStatus(422);
});
