<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            // La clave sale del usuario que extiende, no se genera aparte
            'id' => User::factory()->student(),
            'dni' => fake()->unique()->numerify('########'),
            'date_of_birth' => fake()->dateTimeBetween('-70 years', '-20 years'),
            'phone' => fake()->numerify('11########'),
            'cell_phone' => fake()->numerify('11########'),
            'sub_delegation' => fake()->city(),
            'delegation' => fake()->state(),
        ];
    }
}
