<?php

namespace Osoobe\Quiz\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Post-submission — safe to include is_correct.
 */
class AttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quiz_id' => $this->quiz_id,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'score' => $this->score,
            'correct_answers' => $this->correct_answers,
            'total_questions' => $this->total_questions,
            'status' => $this->status->value,
            'answers' => $this->answers,
        ];
    }
}
