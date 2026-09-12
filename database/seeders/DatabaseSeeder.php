<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserRole;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\QueuedEmail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

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

        $yaEncoladosAntes = QueuedEmail::pluck('id');

        $this->call([
            StudentSeeder::class,
            CourseSeeder::class,
        ]);

        $this->recortarColaDeEmails($yaEncoladosAntes);
    }

    /**
     * `CourseSeeder` encola un aviso real por cada inscripción aprobada,
     * entrega corregida y comunicación general —así se ve la cola con los tres
     * tipos, no sólo uno—, pero los destinatarios son los veinte alumnos de
     * prueba (`alumno01@aamevi.ar` … `alumno20@aamevi.ar`), que no existen.
     * Si algún día se corre `emails:enviar` contra un SMTP real, esas
     * direcciones rebotan en bloque y dañan la reputación del dominio recién
     * autenticado — ya pasó una vez.
     *
     * Se dejan sólo dos: alcanza para confirmar que el envío real funciona sin
     * mandar decenas de correos a cuentas que no existen. Sólo se tocan las
     * filas nuevas de esta corrida —nunca las que ya estaban antes de
     * sembrar—, para que volver a correr el seeder sobre un servidor con uso
     * real no le borre la cola a un alumno de verdad.
     *
     * @param  Collection<int, string>  $yaEncoladosAntes
     */
    private function recortarColaDeEmails($yaEncoladosAntes): void
    {
        $aBorrar = QueuedEmail::query()
            ->whereNotIn('id', $yaEncoladosAntes)
            ->orderBy('created_at')
            ->pluck('id')
            ->slice(2);

        QueuedEmail::whereIn('id', $aBorrar)->delete();
    }
}
