<?php

namespace Osoobe\Quiz\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Osoobe\Quiz\Support\ItemCode;

class ImportQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question' => ['required', 'string'],
            'questions.*.description' => ['nullable', 'string'],
            'questions.*.topic' => ['nullable', 'string'],
            'questions.*.topic_id' => ['nullable', 'string'],
            'questions.*.category' => ['nullable', 'string'],
            'questions.*.category_id' => ['nullable', 'string'],
            'questions.*.difficulty' => ['nullable', 'string'],
            'questions.*.question_type' => ['nullable', 'string'],
            'questions.*.answers' => ['required', 'array', 'min:2'],
            'questions.*.answers.*.text' => ['required', 'string'],
            'questions.*.answers.*.is_correct' => ['boolean'],
            // No DB-level unique check here — a matching itemcode is the intended
            // signal for the action to update that row rather than an error.
            'questions.*.itemcode' => ['nullable', 'string', 'regex:'.ItemCode::PATTERN],
        ];
    }
}
