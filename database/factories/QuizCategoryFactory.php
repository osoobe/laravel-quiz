<?php

namespace Osoobe\Quiz\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Osoobe\Quiz\Models\QuizCategory;

class QuizCategoryFactory extends Factory
{
    protected $model = QuizCategory::class;

    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->unique()->words(2, true)),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
