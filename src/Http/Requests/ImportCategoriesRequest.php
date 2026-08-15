<?php

namespace Osoobe\Quiz\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Osoobe\Quiz\Support\ItemCode;

class ImportCategoriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'categories' => ['required', 'array', 'min:1'],
            'categories.*.name' => ['required', 'string'],
            'categories.*.description' => ['nullable', 'string'],
            'categories.*.is_active' => ['boolean'],
            // No DB-level unique check here — a matching itemcode is the intended
            // signal for the action to update that row rather than an error.
            'categories.*.itemcode' => ['nullable', 'string', 'regex:'.ItemCode::PATTERN],
        ];
    }
}
