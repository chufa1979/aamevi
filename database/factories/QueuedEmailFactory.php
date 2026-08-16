<?php

namespace Database\Factories;

use App\Models\User;
use App\Enums\EmailType;
use App\Enums\EmailStatus;
use App\Models\QueuedEmail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QueuedEmail>
 */
class QueuedEmailFactory extends Factory
{
    public function definition(): array
    {
        return [
            'recipient_id' => User::factory()->student(),
            'email_type' => EmailType::EnrollmentApproved,
            'subject' => 'Ya podés empezar el curso',
            'body' => '<p>Hola.</p>',
            'scheduled_at' => now(),
            'status' => EmailStatus::Pending,
            'retry_count' => 0,
        ];
    }

    /** Programado para más adelante: el worker todavía no lo tiene que tocar. */
    public function scheduled(): static
    {
        return $this->state(['scheduled_at' => now()->addDay()]);
    }

    public function sent(): static
    {
        return $this->state([
            'status' => EmailStatus::Sent,
            'sent_at' => now(),
        ]);
    }

    /** Agotó los reintentos. */
    public function failed(): static
    {
        return $this->state([
            'status' => EmailStatus::Failed,
            'retry_count' => 3,
            'last_error' => 'Connection could not be established with host smtp.example.com',
        ]);
    }
}
