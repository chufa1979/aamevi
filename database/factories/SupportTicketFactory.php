<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Student;
use App\Enums\TicketStatus;
use App\Models\SupportTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicket>
 */
class SupportTicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'student_id' => Student::factory(),
            'subject' => 'No me abre el video de la clase 3',
            'status' => TicketStatus::Open,
        ];
    }

    public function answered(): static
    {
        return $this->state(['status' => TicketStatus::Answered]);
    }

    public function closed(): static
    {
        return $this->state([
            'status' => TicketStatus::Closed,
            'closed_at' => now(),
        ]);
    }
}
