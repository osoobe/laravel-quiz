<?php

namespace Osoobe\Quiz\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public catalogue card — no owner/audience-internals beyond what the card UI needs.
 */
class QuizCatalogueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'question_count' => $this->question_count,
            'time_limit_minutes' => $this->time_limit_minutes,
            'difficulty' => $this->difficulty?->value,
            'topic' => $this->whenLoaded('topic', fn () => $this->topic?->name),
            'audience' => $this->audience,
            'max_attempts' => $this->max_attempts,
        ];
    }
}
