<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\CourseClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'class_id' => CourseClass::factory(),
            'text' => fake()->sentence().'?',
            'question_type' => 'multiple_choice',
            'is_active' => true,
            'order_number' => fake()->unique()->numberBetween(1, 10000),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    /** Con cuatro opciones, una correcta. */
    public function withOptions(): static
    {
        return $this->afterCreating(function (Question $question): void {
            foreach (range(1, 4) as $i) {
                $question->options()->create([
                    'option_text' => fake()->words(4, true),
                    'is_correct' => $i === 1,
                    'order_number' => $i,
                ]);
            }
        });
    }
}
