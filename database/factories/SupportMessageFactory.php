<?php

namespace Database\Factories;

use App\Models\SupportTicket;
use App\Models\SupportMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportMessage>
 */
class SupportMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_id' => SupportTicket::factory(),
            'author_id' => null,
            'body' => 'Probé desde dos navegadores y no carga.',
        ];
    }
}
