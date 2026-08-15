<?php

namespace Osoobe\Quiz\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Taker-facing — is_correct is never serialized while an attempt is in progress.
 */
class PublicQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'description' => $this->description,
            'question_type' => $this->question_type->value,
            'answers' => $this->answers->toPublicArray(),
        ];
    }
}
