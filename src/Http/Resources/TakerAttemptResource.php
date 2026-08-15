<?php

namespace Osoobe\Quiz\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The in-progress taker payload — answers are exposed as {questionId: [answerId, ...]},
 * with is_correct always stripped (never sent to the client before submission).
 */
class TakerAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $timeLimit = $this->quiz?->time_limit_minutes;

        return [
            'id' => $this->id,
            'started_at' => $this->started_at,
            'expires_at' => $timeLimit ? $this->started_at->copy()->addMinutes($timeLimit) : null,
            'answers' => collect($this->answers)
                ->mapWithKeys(fn ($row) => [$row['question_id'] => $row['selected_answers']])
                ->all(),
        ];
    }
}
