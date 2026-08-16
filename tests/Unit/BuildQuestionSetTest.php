<?php

use Osoobe\Quiz\Actions\BuildQuestionSet;
use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Models\QuizCategory;
use Osoobe\Quiz\Models\QuizQuestion;
use Osoobe\Quiz\Models\QuizTopic;

it('filters by topic, category and difficulty, dedupes, and truncates to question_count', function () {
    $topic = QuizTopic::factory()->create();
    $category = QuizCategory::factory()->create();

    $matching = QuizQuestion::factory()->count(5)->create([
        'topic_id' => $topic->id,
        'category_id' => $category->id,
        'difficulty' => 'medium',
    ]);
    QuizQuestion::factory()->count(3)->create(['difficulty' => 'hard']); // wrong difficulty
    QuizQuestion::factory()->count(3)->create(); // wrong topic/category entirely

    $quiz = Quiz::factory()->create([
        'topic_ids' => [$topic->id],
        'category_ids' => [$category->id],
        'difficulty' => 'medium',
        'question_count' => 3,
        'randomize_questions' => false,
    ]);

    $result = (new BuildQuestionSet)->execute($quiz);

    expect($result)->toHaveCount(3);
    foreach ($result as $question) {
        expect($matching->pluck('id'))->toContain($question->id);
    }
});

it('excludes inactive questions', function () {
    QuizQuestion::factory()->count(2)->create(['is_active' => false]);
    QuizQuestion::factory()->count(2)->create(['is_active' => true]);

    $quiz = Quiz::factory()->create(['question_count' => 10]);

    $result = (new BuildQuestionSet)->execute($quiz);

    expect($result)->toHaveCount(2);
});

it('returns an empty set when nothing matches', function () {
    $quiz = Quiz::factory()->create(['question_count' => 5]);

    expect((new BuildQuestionSet)->execute($quiz))->toBeEmpty();
});
