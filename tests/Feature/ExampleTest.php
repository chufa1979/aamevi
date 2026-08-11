<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_home_responde_a_un_usuario_autenticado(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertStatus(200);
    }

    /** El health check queda público a propósito: no expone ningún dato. */
    public function test_api_health_check(): void
    {
        $this->get('/api/health')
            ->assertStatus(200)
            ->assertJson(['status' => 'ok']);
    }
}
