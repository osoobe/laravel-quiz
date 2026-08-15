<?php

namespace Osoobe\Quiz\Actions;

use Illuminate\Support\Collection;
use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Models\QuizCategory;
use Osoobe\Quiz\Models\QuizTopic;

class ExportQuizzes
{
    public function execute(): Collection
    {
        $topicNames = QuizTopic::pluck('name', 'id');
        $categoryNames = QuizCategory::pluck('name', 'id');

        return Quiz::query()->orderBy('name')->get()->map(fn (Quiz $quiz) => [
            'itemcode' => $quiz->itemcode,
            'name' => $quiz->name,
            'description' => $quiz->description,
            // Referenced by name, not raw UUID, so the export stays meaningful
            // (and re-importable by name) in a different database.
            'topics' => collect($quiz->topic_ids)->map(fn ($id) => $topicNames->get($id))->filter()->values()->all(),
            'categories' => collect($quiz->category_ids)->map(fn ($id) => $categoryNames->get($id))->filter()->values()->all(),
            'difficulty' => $quiz->difficulty?->value,
            'question_count' => $quiz->question_count,
            'randomize_questions' => $quiz->randomize_questions,
            'time_limit_minutes' => $quiz->time_limit_minutes,
            'max_attempts' => $quiz->max_attempts,
            'is_active' => $quiz->is_active,
            'audience' => $quiz->audience,
        ]);
    }
}
