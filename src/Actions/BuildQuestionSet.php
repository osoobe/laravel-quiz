<?php

namespace Osoobe\Quiz\Actions;

use Illuminate\Support\Collection;
use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Models\QuizQuestion;

class BuildQuestionSet
{
    public function execute(Quiz $quiz): Collection
    {
        $query = QuizQuestion::query()->active();

        if (! empty($quiz->topic_ids)) {
            $query->whereIn('topic_id', $quiz->topic_ids);
        }

        if (! empty($quiz->category_ids)) {
            $query->whereIn('category_id', $quiz->category_ids);
        }

        if ($quiz->difficulty) {
            $query->where('difficulty', $quiz->difficulty->value);
        }

        $questions = $query->get()->unique('id')->values();

        if ($quiz->randomize_questions) {
            // Collection::shuffle() delegates to PHP's shuffle(), an unbiased Fisher-Yates —
            // unlike MadeIn's original sort-comparator shuffle, which skewed results.
            $questions = $questions->shuffle();
        }

        return $questions->take($quiz->question_count)->values();
    }
}
