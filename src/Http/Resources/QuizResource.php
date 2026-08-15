<?php

namespace Osoobe\Quiz\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'itemcode' => $this->itemcode,
            'name' => $this->name,
            'description' => $this->description,
            'topic_ids' => $this->topic_ids,
            'category_ids' => $this->category_ids,
            'difficulty' => $this->difficulty?->value,
            'question_count' => $this->question_count,
            'randomize_questions' => $this->randomize_questions,
            'time_limit_minutes' => $this->time_limit_minutes,
            'max_attempts' => $this->max_attempts,
            'is_active' => $this->is_active,
            'audience' => $this->audience,
            'is_scoped' => $this->isScoped(),
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
        ];
    }
}
