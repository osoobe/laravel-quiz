<?php

namespace Osoobe\Quiz\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Osoobe\Quiz\Support\ItemCode;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191', Rule::unique('quiz_categories', 'name')->ignore($this->route('category'))],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'itemcode' => [
                'nullable', 'string', 'regex:'.ItemCode::PATTERN,
                Rule::unique('quiz_categories', 'itemcode')->ignore($this->route('category')),
            ],
        ];
    }
}
