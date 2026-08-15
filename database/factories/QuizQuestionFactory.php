<?php

namespace Osoobe\Quiz\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Osoobe\Quiz\Models\QuizQuestion;

class QuizQuestionFactory extends Factory
{
    protected $model = QuizQuestion::class;

    public function definition(): array
    {
        return [
            'question' => $this->faker->sentence().'?',
            'description' => null,
            'difficulty' => $this->faker->randomElement(['easy', 'medium', 'hard', 'expert']),
            'question_type' => 'radio',
            'answers' => [
                ['id' => (string) Str::uuid(), 'text' => $this->faker->unique()->word(), 'is_correct' => true],
                ['id' => (string) Str::uuid(), 'text' => $this->faker->unique()->word(), 'is_correct' => false],
                ['id' => (string) Str::uuid(), 'text' => $this->faker->unique()->word(), 'is_correct' => false],
            ],
            'is_active' => true,
            'created_by' => (string) Str::uuid(),
        ];
    }

    public function checkbox(): static
    {
        return $this->state(fn () => [
            'question_type' => 'checkbox',
            'answers' => [
                ['id' => (string) Str::uuid(), 'text' => $this->faker->unique()->word(), 'is_correct' => true],
                ['id' => (string) Str::uuid(), 'text' => $this->faker->unique()->word(), 'is_correct' => true],
                ['id' => (string) Str::uuid(), 'text' => $this->faker->unique()->word(), 'is_correct' => false],
            ],
        ]);
    }
}
