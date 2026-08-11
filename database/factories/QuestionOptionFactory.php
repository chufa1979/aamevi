<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionOption>
 */
class QuestionOptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'option_text' => fake()->words(4, true),
            'is_correct' => false,
            'order_number' => fake()->unique()->numberBetween(1, 10000),
        ];
    }

    public function correct(): static
    {
        return $this->state(['is_correct' => true]);
    }
}
