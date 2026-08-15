<?php

namespace Osoobe\Quiz\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Accepts either a single identifier, an array (bulk, already split client-side),
 * or a raw string (bulk textarea paste — split on whitespace/commas/semicolons/newlines).
 */
class InviteUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifiers' => ['required'],
        ];
    }

    public function normalizedIdentifiers(): array
    {
        $value = $this->input('identifiers');

        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value)));
        }

        return array_values(array_filter(preg_split('/[\s,;]+/', (string) $value)));
    }
}
