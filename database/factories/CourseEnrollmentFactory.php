<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;
use App\Enums\EnrollmentStatus;
use App\Models\CourseEnrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseEnrollment>
 */
class CourseEnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'student_id' => Student::factory(),
            'enrollment_date' => now(),
            'status' => EnrollmentStatus::Pending,
            'approval_date' => null,
            'approved_by' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EnrollmentStatus::Approved,
            'approval_date' => now(),
            'approved_by' => Teacher::factory(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status' => EnrollmentStatus::Rejected,
            'approval_date' => now(),
        ]);
    }

    public function active(): static
    {
        return $this->state([
            'status' => EnrollmentStatus::Active,
            'approval_date' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => EnrollmentStatus::Completed,
            'approval_date' => now(),
        ]);
    }
}
