<?php

use Illuminate\Support\Str;
use Osoobe\Quiz\Actions\SubmitAttempt;
use Osoobe\Quiz\Enums\AttemptStatus;
use Osoobe\Quiz\Exceptions\AttemptAlreadyCompletedException;
use Osoobe\Quiz\Http\Resources\PublicQuestionResource;
use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Models\QuizAttempt;
use Osoobe\Quiz\Models\QuizQuestion;

function scoringTestMakeInProgressAttempt(Quiz $quiz, array $questions): QuizAttempt
{
    return QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'user_id' => (string) Str::uuid(),
        'started_at' => now(),
        'total_questions' => count($questions),
        'answers' => collect($questions)->map(fn (QuizQuestion $q) => [
            'question_id' => $q->id,
            'selected_answers' => [],
            'is_correct' => null,
        ])->all(),
        'status' => AttemptStatus::InProgress->value,
    ]);
}

it('scores a single-answer question correct only on an exact match', function () {
    $quiz = Quiz::factory()->create();
    $question = QuizQuestion::factory()->create(); // radio, 1 correct of 3
    $correctId = $question->answers->correctIds()[0];

    $attempt = scoringTestMakeInProgressAttempt($quiz, [$question]);

    $result = (new SubmitAttempt)->execute($attempt, [$question->id => [$correctId]]);

    expect($result->score)->toBe(100)
        ->and($result->correct_answers)->toBe(1)
        ->and($result->status)->toBe(AttemptStatus::Completed);
});

it('marks a checkbox question wrong when the selected set is not an exact match', function () {
    $quiz = Quiz::factory()->create();
    $question = QuizQuestion::factory()->checkbox()->create(); // 2 correct of 3
    $correctIds = $question->answers->correctIds();
    $wrongIds = $question->answers->pluck('id')->diff($correctIds)->values()->all();

    $attempt = scoringTestMakeInProgressAttempt($quiz, [$question]);

    // Only one of the two correct answers selected — not an exact set match.
    $result = (new SubmitAttempt)->execute($attempt, [$question->id => [$correctIds[0]]]);
    expect($result->score)->toBe(0);

    $attempt2 = scoringTestMakeInProgressAttempt($quiz, [$question]);
    $result2 = (new SubmitAttempt)->execute($attempt2, [$question->id => $correctIds]);
    expect($result2->score)->toBe(100);

    $attempt3 = scoringTestMakeInProgressAttempt($quiz, [$question]);
    $result3 = (new SubmitAttempt)->execute($attempt3, [$question->id => array_merge($correctIds, [$wrongIds[0]])]);
    expect($result3->score)->toBe(0);
});

it('computes a rounded percentage across multiple questions', function () {
    $quiz = Quiz::factory()->create();
    $questions = QuizQuestion::factory()->count(3)->create();

    $attempt = scoringTestMakeInProgressAttempt($quiz, $questions->all());

    // Answer only the first question correctly -> 1/3 = 33%.
    $answers = [];
    foreach ($questions as $i => $question) {
        $answers[$question->id] = $i === 0 ? $question->answers->correctIds() : [];
    }

    $result = (new SubmitAttempt)->execute($attempt, $answers);

    expect($result->score)->toBe(33)
        ->and($result->correct_answers)->toBe(1)
        ->and($result->total_questions)->toBe(3);
});

it('rejects submitting an attempt that is already completed', function () {
    $quiz = Quiz::factory()->create();
    $question = QuizQuestion::factory()->create();
    $attempt = scoringTestMakeInProgressAttempt($quiz, [$question]);

    (new SubmitAttempt)->execute($attempt, [$question->id => $question->answers->correctIds()]);

    expect(fn () => (new SubmitAttempt)->execute($attempt->fresh(), [$question->id => []]))
        ->toThrow(AttemptAlreadyCompletedException::class);
});

it('never leaks is_correct to the taker before submission', function () {
    $question = QuizQuestion::factory()->create();

    $public = (new PublicQuestionResource($question))->resolve();

    expect($public['answers'][0])->not->toHaveKey('is_correct');
});
