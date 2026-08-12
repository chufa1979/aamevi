<?php

namespace Database\Factories;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            // En texto plano a propósito: el cast `hashed` del modelo lo hashea
            // al asignarlo. Hashearlo acá lo dejaría hasheado dos veces.
            'password' => 'password',
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'role' => UserRole::Student,
            'is_active' => true,
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => UserRole::Admin]);
    }

    public function teacher(): static
    {
        return $this->state(['role' => UserRole::Teacher]);
    }

    public function student(): static
    {
        return $this->state(['role' => UserRole::Student]);
    }

    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    /** Cuenta creada por Google OAuth: sin contraseña propia. */
    public function fromGoogle(): static
    {
        return $this->state([
            'password' => null,
            'oauth_provider' => 'google',
            'oauth_id' => (string) fake()->unique()->randomNumber(9, true),
        ]);
    }
}
