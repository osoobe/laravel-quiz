<?php

namespace Osoobe\Quiz\Support;

use Illuminate\Support\Collection;

class AnswerCollection extends Collection
{
    /**
     * @return array<int, string>
     */
    public function correctIds(): array
    {
        return $this->filter(fn (Answer $answer) => $answer->isCorrect)
            ->map(fn (Answer $answer) => $answer->id)
            ->values()
            ->all();
    }

    public function toPublicArray(): array
    {
        return $this->map(fn (Answer $answer) => $answer->toPublicArray())->values()->all();
    }
}
