<?php

namespace Osoobe\Quiz\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin-facing — includes is_correct. Never used for the taker payload
 * (see PublicQuestionResource).
 */
class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'itemcode' => $this->itemcode,
            'question' => $this->question,
            'description' => $this->description,
            'topic' => $this->whenLoaded('topic', fn () => $this->topic ? new TopicResource($this->topic) : null),
            'category' => $this->whenLoaded('category', fn () => $this->category ? new CategoryResource($this->category) : null),
            'difficulty' => $this->difficulty->value,
            'question_type' => $this->question_type->value,
            'answers' => $this->answers->map->toArray()->all(),
            'is_active' => $this->is_active,
        ];
    }
}
