<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_api_health_check(): void
    {
        $response = $this->get('/api/health');

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);
    }
}
