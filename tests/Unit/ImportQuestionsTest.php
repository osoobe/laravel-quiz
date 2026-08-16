<?php

use Illuminate\Support\Str;
use Osoobe\Quiz\Actions\ImportQuestions;
use Osoobe\Quiz\Models\QuizCategory;
use Osoobe\Quiz\Models\QuizQuestion;
use Osoobe\Quiz\Models\QuizTopic;

it('resolves topic/category by id when the value is a uuid', function () {
    $topic = QuizTopic::factory()->create();

    $summary = (new ImportQuestions)->execute([
        [
            'topic_id' => $topic->id,
            'question' => 'Q1',
            'answers' => [['text' => 'a', 'is_correct' => true], ['text' => 'b', 'is_correct' => false]],
        ],
    ], (string) Str::uuid());

    expect($summary['imported'])->toBe(1)->and($summary['failed'])->toBe(0);
    expect(QuizQuestion::first()->topic_id)->toBe($topic->id);
});

it('resolves topic/category by name, case-insensitively', function () {
    $category = QuizCategory::factory()->create(['name' => 'Programming Fundamentals']);

    $summary = (new ImportQuestions)->execute([
        [
            'category' => 'programming fundamentals',
            'question' => 'Q1',
            'answers' => [['text' => 'a', 'is_correct' => true], ['text' => 'b', 'is_correct' => false]],
        ],
    ], (string) Str::uuid());

    expect($summary['imported'])->toBe(1);
    expect(QuizQuestion::first()->category_id)->toBe($category->id);
});

it('leaves topic/category null when no match is found', function () {
    $summary = (new ImportQuestions)->execute([
        [
            'topic' => 'Does Not Exist',
            'question' => 'Q1',
            'answers' => [['text' => 'a', 'is_correct' => true], ['text' => 'b', 'is_correct' => false]],
        ],
    ], (string) Str::uuid());

    expect($summary['imported'])->toBe(1);
    expect(QuizQuestion::first()->topic_id)->toBeNull();
});

it('reports failed rows without aborting the whole import', function () {
    $summary = (new ImportQuestions)->execute([
        ['question' => 'Good', 'answers' => [['text' => 'a', 'is_correct' => true], ['text' => 'b', 'is_correct' => false]]],
        ['question' => null, 'answers' => []], // will fail at the DB layer (question NOT NULL)
    ], (string) Str::uuid());

    expect($summary['imported'])->toBe(1)->and($summary['failed'])->toBe(1);
    expect($summary['errors'])->toHaveCount(1);
});
