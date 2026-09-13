<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\StudentNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentNote>
 */
class StudentNoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'author_id' => null,
            'body' => 'Pago confirmado por transferencia.',
            'created_at' => now(),
        ];
    }
}
