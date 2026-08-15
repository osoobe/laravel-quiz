<?php

namespace Osoobe\Quiz\Actions;

use Illuminate\Support\Collection;
use Osoobe\Quiz\Models\QuizQuestion;

class ExportQuestions
{
    public function execute(): Collection
    {
        return QuizQuestion::with(['topic', 'category'])->get()->map(fn (QuizQuestion $question) => [
            'itemcode' => $question->itemcode,
            'question' => $question->question,
            'description' => $question->description,
            'topic' => $question->topic?->name,
            'category' => $question->category?->name,
            'difficulty' => $question->difficulty?->value,
            'question_type' => $question->question_type?->value,
            'answers' => $question->answers->map(fn ($answer) => [
                'text' => $answer->text,
                'is_correct' => $answer->isCorrect,
            ])->all(),
        ]);
    }
}
