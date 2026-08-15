<?php

namespace Osoobe\Quiz\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Osoobe\Quiz\Support\ItemCode;

class StoreTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191', 'unique:quiz_topics,name'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'itemcode' => ['nullable', 'string', 'regex:'.ItemCode::PATTERN, 'unique:quiz_topics,itemcode'],
        ];
    }
}
