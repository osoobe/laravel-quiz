<?php

namespace Osoobe\Quiz\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Osoobe\Quiz\Enums\QuizDifficulty;
use Osoobe\Quiz\Support\ItemCode;

/**
 * Used for both create and update. itemcode's unique check ignores the route's
 * {quiz} binding, which is simply null (a no-op) on the store route.
 */
class StoreQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'topic_ids' => ['array'],
            'topic_ids.*' => ['uuid', 'exists:quiz_topics,id'],
            'category_ids' => ['array'],
            'category_ids.*' => ['uuid', 'exists:quiz_categories,id'],
            'difficulty' => ['nullable', Rule::enum(QuizDifficulty::class)],
            'question_count' => ['required', 'integer', 'min:1'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'max_attempts' => ['required', 'integer', 'min:1'],
            'randomize_questions' => ['boolean'],
            'is_active' => ['boolean'],
            'audience' => [
                'required', 'string',
                Rule::in(['everyone', 'logged_in', 'private']),
            ],
            'itemcode' => [
                'nullable', 'string', 'regex:'.ItemCode::PATTERN,
                Rule::unique('quizzes', 'itemcode')->ignore($this->route('quiz')),
            ],
        ];
    }
}
