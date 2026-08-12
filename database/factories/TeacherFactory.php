<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Teacher>
 */
class TeacherFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => User::factory()->teacher(),
            'bio' => fake()->paragraph(),
            'specialization' => fake()->randomElement([
                'Nutrición',
                'Actividad física',
                'Manejo del estrés',
                'Sueño reparador',
                'Vínculos sociales',
                'Conductas de riesgo',
            ]),
        ];
    }
}
