<?php

namespace Osoobe\Quiz\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user?->getKey(),
                'name' => $this->user?->quizDisplayName(),
                'email' => $this->user?->email,
            ],
            'invited_at' => $this->created_at,
        ];
    }
}
