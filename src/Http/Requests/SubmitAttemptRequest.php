<?php

namespace Osoobe\Quiz\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Osoobe\Quiz\Enums\QuizQuestionType;
use Osoobe\Quiz\Models\QuizQuestion;

class SubmitAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $attempt = $this->route('attempt');
            $validQuestionIds = collect($attempt->answers)->pluck('question_id')->all();
            $questions = QuizQuestion::whereIn('id', $validQuestionIds)->get()->keyBy('id');

            foreach ($this->input('answers', []) as $questionId => $answerIds) {
                if (! in_array($questionId, $validQuestionIds, true)) {
                    $validator->errors()->add("answers.{$questionId}", 'This question does not belong to the attempt.');

                    continue;
                }

                if (! is_array($answerIds)) {
                    $validator->errors()->add("answers.{$questionId}", 'Answers must be an array of answer ids.');

                    continue;
                }

                $question = $questions->get($questionId);

                if (! $question) {
                    continue;
                }

                $validAnswerIds = $question->answers->pluck('id')->all();

                foreach ($answerIds as $answerId) {
                    if (! in_array($answerId, $validAnswerIds, true)) {
                        $validator->errors()->add("answers.{$questionId}", 'Invalid answer id for this question.');
                    }
                }

                if ($question->question_type === QuizQuestionType::Radio && count($answerIds) > 1) {
                    $validator->errors()->add("answers.{$questionId}", 'Only one answer may be selected for this question.');
                }
            }
        });
    }
}
