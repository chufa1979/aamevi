<?php

namespace Database\Factories;

use App\Models\Certificate;
use Illuminate\Support\Str;
use App\Models\CourseEnrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certificate>
 */
class CertificateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'enrollment_id' => CourseEnrollment::factory(),
            'certificate_number' => 'AAMEVI-'.now()->year.'-'.strtoupper(Str::random(6)),
            'issued_at' => now(),
        ];
    }
}
