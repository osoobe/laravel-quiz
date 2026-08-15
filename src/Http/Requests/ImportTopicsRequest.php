<?php

namespace Osoobe\Quiz\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Osoobe\Quiz\Support\ItemCode;

class ImportTopicsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'topics' => ['required', 'array', 'min:1'],
            'topics.*.name' => ['required', 'string'],
            'topics.*.description' => ['nullable', 'string'],
            'topics.*.is_active' => ['boolean'],
            // No DB-level unique check here — a matching itemcode is the intended
            // signal for the action to update that row rather than an error.
            'topics.*.itemcode' => ['nullable', 'string', 'regex:'.ItemCode::PATTERN],
        ];
    }
}
