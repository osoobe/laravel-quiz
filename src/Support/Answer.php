<?php

namespace Osoobe\Quiz\Support;

use Illuminate\Support\Str;

class Answer
{
    public function __construct(
        public readonly string $id,
        public readonly string $text,
        public readonly bool $isCorrect,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? (string) Str::uuid(),
            text: (string) ($data['text'] ?? ''),
            isCorrect: (bool) ($data['is_correct'] ?? false),
        );
    }

    public function toArray(): array
    {
        return ['id' => $this->id, 'text' => $this->text, 'is_correct' => $this->isCorrect];
    }

    /**
     * The public shape sent to a quiz taker before submission — never includes is_correct.
     */
    public function toPublicArray(): array
    {
        return ['id' => $this->id, 'text' => $this->text];
    }
}
