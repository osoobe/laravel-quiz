<?php

namespace Osoobe\Quiz\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public — display name + avatar + score only, never email (docs/quiz/03-access-control.md §3.7).
 */
class LeaderboardEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user' => [
                'name' => $this->user?->quizDisplayName(),
                'avatar_url' => $this->user?->quizAvatarUrl(),
            ],
            'score' => $this->score,
            'correct_answers' => $this->correct_answers,
            'total_questions' => $this->total_questions,
            'completed_at' => $this->completed_at,
        ];
    }
}
