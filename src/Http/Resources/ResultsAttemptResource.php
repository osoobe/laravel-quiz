<?php

namespace Osoobe\Quiz\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Staff/creator only — exposes participant identity + per-attempt status.
 */
class ResultsAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user?->getKey(),
                'name' => $this->user?->quizDisplayName(),
                'avatar_url' => $this->user?->quizAvatarUrl(),
            ],
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'correct_answers' => $this->correct_answers,
            'total_questions' => $this->total_questions,
            'score' => $this->score,
            'status' => $this->status->value,
        ];
    }
}
