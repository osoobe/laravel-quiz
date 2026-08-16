<?php

use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Models\QuizCategory;
use Osoobe\Quiz\Models\QuizQuestion;
use Osoobe\Quiz\Models\QuizTopic;
use Osoobe\Quiz\Support\ItemCode;

it('auto-generates a valid, unique itemcode on create when none is given', function () {
    $topic = QuizTopic::factory()->create();

    expect($topic->itemcode)->not->toBeNull();
    expect(preg_match(ItemCode::PATTERN, $topic->itemcode))->toBe(1);
});

it('preserves an explicitly provided itemcode instead of generating one', function () {
    $topic = QuizTopic::factory()->create(['itemcode' => 'MY-CODE_01']);

    expect($topic->itemcode)->toBe('MY-CODE_01');
});

it('applies auto-generation to all four itemcode-bearing models', function () {
    $category = QuizCategory::factory()->create();
    $question = QuizQuestion::factory()->create();
    $quiz = Quiz::factory()->create();

    foreach ([$category, $question, $quiz] as $model) {
        expect($model->itemcode)->not->toBeNull();
        expect(preg_match(ItemCode::PATTERN, $model->itemcode))->toBe(1);
    }
});

it('never generates a duplicate itemcode for the same model', function () {
    $topics = QuizTopic::factory()->count(20)->create();

    expect($topics->pluck('itemcode')->unique())->toHaveCount(20);
});
