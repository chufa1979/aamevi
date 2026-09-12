<?php

namespace Database\Factories;

use App\Models\EnrollmentNote;
use App\Models\CourseEnrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EnrollmentNote>
 */
class EnrollmentNoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'enrollment_id' => CourseEnrollment::factory(),
            'author_id' => null,
            'body' => 'Pago confirmado por transferencia.',
            'created_at' => now(),
        ];
    }
}
