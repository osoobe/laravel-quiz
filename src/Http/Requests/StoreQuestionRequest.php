<?php

namespace Osoobe\Quiz\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Osoobe\Quiz\Enums\QuizDifficulty;
use Osoobe\Quiz\Enums\QuizQuestionType;
use Osoobe\Quiz\Support\ItemCode;

/**
 * Used for both create and update. itemcode's unique check ignores the route's
 * {question} binding, which is simply null (a no-op) on the store route.
 */
class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'topic_id' => ['nullable', 'uuid', 'exists:quiz_topics,id'],
            'category_id' => ['nullable', 'uuid', 'exists:quiz_categories,id'],
            'difficulty' => ['required', Rule::enum(QuizDifficulty::class)],
            'question_type' => ['required', Rule::enum(QuizQuestionType::class)],
            'answers' => ['required', 'array', 'min:2'],
            'answers.*.text' => ['required', 'string'],
            'answers.*.is_correct' => ['boolean'],
            'itemcode' => [
                'nullable', 'string', 'regex:'.ItemCode::PATTERN,
                Rule::unique('quiz_questions', 'itemcode')->ignore($this->route('question')),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $answers = collect($this->input('answers', []));
            $correctCount = $answers->filter(fn ($answer) => (bool) ($answer['is_correct'] ?? false))->count();

            if ($correctCount < 1) {
                $validator->errors()->add('answers', 'At least one answer must be marked as correct.');
            }

            if ($this->input('question_type') === QuizQuestionType::Radio->value && $correctCount > 1) {
                $validator->errors()->add('answers', 'Only one answer may be marked correct for a single-answer question.');
            }
        });
    }
}
