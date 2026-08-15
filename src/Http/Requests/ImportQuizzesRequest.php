<?php

namespace Osoobe\Quiz\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Osoobe\Quiz\Support\ItemCode;

class ImportQuizzesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quizzes' => ['required', 'array', 'min:1'],
            'quizzes.*.name' => ['required', 'string'],
            'quizzes.*.description' => ['nullable', 'string'],
            'quizzes.*.topics' => ['nullable', 'array'],
            'quizzes.*.categories' => ['nullable', 'array'],
            'quizzes.*.difficulty' => ['nullable', 'string'],
            'quizzes.*.question_count' => ['nullable', 'integer', 'min:1'],
            'quizzes.*.randomize_questions' => ['boolean'],
            'quizzes.*.time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'quizzes.*.max_attempts' => ['nullable', 'integer', 'min:1'],
            'quizzes.*.is_active' => ['boolean'],
            'quizzes.*.audience' => ['nullable', 'string'],
            // No DB-level unique check here — a matching itemcode is the intended
            // signal for the action to update that row rather than an error.
            'quizzes.*.itemcode' => ['nullable', 'string', 'regex:'.ItemCode::PATTERN],
        ];
    }
}
