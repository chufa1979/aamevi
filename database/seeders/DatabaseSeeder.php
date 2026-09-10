<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserRole;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Seeder;

/**
 * Usuarios de prueba, uno por rol. Es idempotente: se puede volver a correr
 * sin chocar contra el unique de `email`.
 *
 * Contraseña de los tres: "password". No usar en producción.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@aamevi.ar'],
            [
                'password' => 'password',
                'first_name' => 'Administración',
                'last_name' => 'AAMEVi',
                'role' => UserRole::Admin,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $teacher = User::firstOrCreate(
            ['email' => 'profesor@aamevi.ar'],
            [
                'password' => 'password',
                'first_name' => 'Profesor',
                'last_name' => 'De Prueba',
                'role' => UserRole::Teacher,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        Teacher::firstOrCreate(
            ['id' => $teacher->id],
            [
                'bio' => 'Docente de prueba para el entorno de desarrollo.',
                'specialization' => 'Nutrición',
            ]
        );

        $student = User::firstOrCreate(
            ['email' => 'alumno@aamevi.ar'],
            [
                'password' => 'password',
                'first_name' => 'Alumno',
                'last_name' => 'De Prueba',
                'role' => UserRole::Student,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        Student::firstOrCreate(
            ['id' => $student->id],
            [
                'dni' => '30000000',
                'date_of_birth' => '1985-06-15',
                'phone' => '+54 9 11 40000000',
                'country' => 'Argentina',
                'city' => 'CABA',
                'graduation_date' => '2010-12-10',
                'university' => 'Universidad de Buenos Aires',
                'profession' => 'Médico/a',
                'membership_number' => '10001',
            ]
        );

        $registrar = User::firstOrCreate(
            ['email' => 'administracion@aamevi.ar'],
            [
                'password' => 'password',
                'first_name' => 'Administrativo',
                'last_name' => 'De Prueba',
                'role' => UserRole::Registrar,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command?->info(
            "Usuarios de prueba: {$admin->email}, {$teacher->email}, {$student->email}, {$registrar->email} (contraseña: password)"
        );

        $this->call([
            StudentSeeder::class,
            CourseSeeder::class,
        ]);
    }
}
