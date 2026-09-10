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
            'phone' => fake()->numerify('+54 9 11 ########'),
            'country' => 'Argentina',
            'city' => fake()->city(),
            'graduation_date' => fake()->dateTimeBetween('-15 years', '-1 years'),
            'university' => fake()->randomElement([
                'Universidad de Buenos Aires',
                'Universidad Austral',
                'Universidad Nacional de Córdoba',
                'Universidad Nacional de La Plata',
            ]),
            'profession' => fake()->randomElement([
                'Médico/a', 'Nutricionista', 'Kinesiólogo/a', 'Psicólogo/a', 'Enfermero/a',
            ]),
            'membership_number' => fake()->boolean(40) ? (string) fake()->unique()->numberBetween(10000, 99999) : null,
        ];
    }
}
