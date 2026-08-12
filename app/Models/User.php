<?php

namespace App\Models;

use Filament\Panel;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\HasName;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Tabla base de usuarios. `students` y `teachers` son extensiones 1:1
 * identificadas por la misma clave.
 */
class User extends Authenticatable implements FilamentUser, HasName, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'role',
        'is_active',
        'oauth_provider',
        'oauth_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class, 'id');
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class, 'id');
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => trim("{$this->first_name} {$this->last_name}"));
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Filament busca por defecto un atributo `name`, que este modelo no tiene:
     * el nombre está partido en `first_name` y `last_name`.
     */
    public function getFilamentName(): string
    {
        return $this->full_name;
    }

    /**
     * Quién puede entrar al panel. Sin esto, Filament dejaría pasar a cualquier
     * usuario autenticado, incluidos los alumnos.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->isAdmin() && $this->is_active,
            default => false,
        };
    }

    public function isTeacher(): bool
    {
        return $this->role === UserRole::Teacher;
    }

    public function isStudent(): bool
    {
        return $this->role === UserRole::Student;
    }
}
