<?php

namespace Osoobe\Quiz\Support;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class AnswerListCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): AnswerCollection
    {
        $decoded = json_decode($value ?? '[]', true) ?: [];

        return new AnswerCollection(array_map(fn (array $row) => Answer::fromArray($row), $decoded));
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        $answers = $value instanceof AnswerCollection ? $value : new AnswerCollection($value ?? []);

        $normalized = $answers->map(function ($answer) {
            $answer = $answer instanceof Answer ? $answer : Answer::fromArray($answer);

            return $answer->toArray();
        })->values()->all();

        return json_encode($normalized);
    }
}
