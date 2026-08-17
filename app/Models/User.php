<?php

namespace App\Models;

use Filament\Panel;
use App\Enums\UserRole;
use Filament\Facades\Filament;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\URL;
use App\Services\NotificationService;
use Filament\Models\Contracts\HasName;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    /**
     * Inscripciones del alumno, sin pasar por la ficha.
     *
     * `course_enrollments.student_id` apunta a `students.id`, que es la misma
     * clave que `users.id`. Tenerla acá permite contar inscripciones en el
     * listado con un `withCount` en vez de una consulta por fila.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class, 'student_id');
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
            'profesores' => $this->isTeacher() && $this->is_active,
            default => false,
        };
    }

    /**
     * El aviso de verificación va a la cola, no al correo.
     *
     * Laravel manda una notificación en línea; acá todo pasa por `email_queue`,
     * así que se sobrescribe para que este aviso siga el mismo camino que los
     * demás y se vea en el panel junto a ellos.
     */
    public function sendEmailVerificationNotification(): void
    {
        $enlace = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes((int) config('auth.verification.expire', 60)),
            ['id' => $this->getKey(), 'hash' => sha1($this->getEmailForVerification())],
        );

        app(NotificationService::class)->verification($this, $enlace);
    }

    /**
     * Dónde le corresponde entrar a este usuario.
     *
     * Cada rol trabaja en una superficie distinta y la portada no es la de
     * nadie: al alumno le sirve ver sus cursos, y al docente o al administrador
     * su panel. Sin esto, entrar dejaba a los tres en la misma pantalla de
     * bienvenida, con un clic de más para todos.
     *
     * Se usa en dos lados —después de iniciar sesión, y al abrir /login con la
     * sesión ya iniciada— por eso vive acá y no en el controlador.
     */
    public function homeUrl(): string
    {
        return match (true) {
            $this->isAdmin() => Filament::getPanel('admin')->getUrl(),
            $this->isTeacher() => Filament::getPanel('profesores')->getUrl(),
            default => route('classroom.courses'),
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
