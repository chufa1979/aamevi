<?php

namespace Database\Factories;

use App\Models\CourseClass;
use App\Models\CourseModule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseClass>
 */
class CourseClassFactory extends Factory
{
    public function definition(): array
    {
        return [
            'module_id' => CourseModule::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'order_number' => fake()->unique()->numberBetween(1, 1000),
            'activation_date' => now(),
            'is_live_session' => false,
            'is_live_recording_available' => false,
        ];
    }

    public function live(): static
    {
        return $this->state([
            'is_live_session' => true,
            'meet_link' => 'https://meet.google.com/'.fake()->lexify('???-????-???'),
        ]);
    }

    /** Todavía no disponible para los alumnos. */
    public function upcoming(): static
    {
        return $this->state(['activation_date' => now()->addWeek()]);
    }
}
