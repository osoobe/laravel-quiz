<?php

namespace Osoobe\Quiz\Actions;

use Illuminate\Support\Facades\DB;
use Osoobe\Quiz\Enums\AttemptStatus;
use Osoobe\Quiz\Exceptions\AttemptAlreadyCompletedException;
use Osoobe\Quiz\Exceptions\AttemptExpiredException;
use Osoobe\Quiz\Models\QuizAttempt;
use Osoobe\Quiz\Models\QuizQuestion;

class SubmitAttempt
{
    /**
     * @param  array<string, array<int, string>>  $submittedAnswers  questionId => [answerId, ...]
     */
    public function execute(QuizAttempt $attempt, array $submittedAnswers): QuizAttempt
    {
        return DB::transaction(function () use ($attempt, $submittedAnswers) {
            $attempt->refresh();
            $attempt->loadMissing('quiz');

            if ($attempt->status !== AttemptStatus::InProgress) {
                throw new AttemptAlreadyCompletedException;
            }

            $timeLimit = $attempt->quiz->time_limit_minutes;

            if ($timeLimit && now()->greaterThan($attempt->started_at->copy()->addMinutes($timeLimit))) {
                throw new AttemptExpiredException;
            }

            $questionIds = collect($attempt->answers)->pluck('question_id');
            $questions = QuizQuestion::whereIn('id', $questionIds)->get()->keyBy('id');

            $correctCount = 0;
            $finalAnswers = [];

            foreach ($attempt->answers as $row) {
                $question = $questions->get($row['question_id']);
                $selected = array_values($submittedAnswers[$row['question_id']] ?? []);
                $correctIds = $question?->answers->correctIds() ?? [];
                $isCorrect = $question !== null && $this->sameSet($selected, $correctIds);

                if ($isCorrect) {
                    $correctCount++;
                }

                $finalAnswers[] = [
                    'question_id' => $row['question_id'],
                    'selected_answers' => $selected,
                    'is_correct' => $isCorrect,
                ];
            }

            $total = count($finalAnswers);
            $score = $total > 0 ? (int) round($correctCount / $total * 100) : 0;

            $attempt->update([
                'answers' => $finalAnswers,
                'score' => $score,
                'correct_answers' => $correctCount,
                'completed_at' => now(),
                'status' => AttemptStatus::Completed->value,
            ]);

            return $attempt;
        });
    }

    /**
     * Exact-set match, order-independent.
     */
    private function sameSet(array $a, array $b): bool
    {
        sort($a);
        sort($b);

        return $a === $b;
    }
}
