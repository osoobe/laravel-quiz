<?php

namespace Osoobe\Quiz\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Osoobe\Quiz\Models\Quiz;

class QuizFactory extends Factory
{
    protected $model = Quiz::class;

    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->words(3, true)),
            'description' => $this->faker->sentence(),
            'topic_ids' => [],
            'category_ids' => [],
            'difficulty' => null,
            'question_count' => 5,
            'randomize_questions' => true,
            'time_limit_minutes' => null,
            'max_attempts' => 1,
            'is_active' => true,
            'audience' => 'everyone',
            'created_by' => (string) Str::uuid(),
        ];
    }

    public function private(): static
    {
        return $this->state(fn () => ['audience' => 'private']);
    }

    public function loggedIn(): static
    {
        return $this->state(fn () => ['audience' => 'logged_in']);
    }
}
