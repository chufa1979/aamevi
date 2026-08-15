<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserRole;
use App\Models\Student;
use Illuminate\Database\Seeder;

/**
 * Veinte alumnos de prueba, más el `alumno@aamevi.ar` que crea DatabaseSeeder.
 *
 * Son suficientes para que la grilla de seguimiento se vea como se va a ver en
 * uso: con dos o tres alumnos no se distingue una pantalla que funciona de una
 * que hace una consulta por celda.
 *
 * Los nombres son inventados. Las contraseñas, todas «password»: es un seeder
 * de desarrollo y no debe correrse en producción.
 */
class StudentSeeder extends Seeder
{
    /** @var array<int, array{string, string, string, string}> nombre, apellido, delegación, subdelegación */
    private const ALUMNOS = [
        ['Camila', 'Ferreyra', 'Buenos Aires', 'La Plata'],
        ['Joaquín', 'Basualdo', 'Buenos Aires', 'CABA'],
        ['Malena', 'Ocampo', 'Córdoba', 'Villa María'],
        ['Nicolás', 'Zabala', 'Santa Fe', 'Rosario'],
        ['Rocío', 'Bustamante', 'Mendoza', 'Godoy Cruz'],
        ['Tomás', 'Iriarte', 'Buenos Aires', 'Bahía Blanca'],
        ['Agustina', 'Peralta', 'Tucumán', 'San Miguel'],
        ['Bruno', 'Maldonado', 'Neuquén', 'Neuquén Capital'],
        ['Florencia', 'Quiroga', 'Entre Ríos', 'Paraná'],
        ['Ignacio', 'Sanabria', 'Salta', 'Salta Capital'],
        ['Julieta', 'Vergara', 'Buenos Aires', 'Mar del Plata'],
        ['Lucas', 'Andrada', 'Chubut', 'Comodoro Rivadavia'],
        ['Micaela', 'Toledo', 'Córdoba', 'Río Cuarto'],
        ['Federico', 'Aguirre', 'Santa Fe', 'Santa Fe Capital'],
        ['Valentina', 'Cardozo', 'Corrientes', 'Corrientes Capital'],
        ['Matías', 'Bengoechea', 'Buenos Aires', 'San Isidro'],
        ['Paula', 'Recalde', 'Misiones', 'Posadas'],
        ['Santiago', 'Olmedo', 'San Juan', 'San Juan Capital'],
        ['Delfina', 'Arrieta', 'Río Negro', 'Bariloche'],
        ['Gonzalo', 'Ledesma', 'Jujuy', 'San Salvador'],
    ];

    public function run(): void
    {
        foreach (self::ALUMNOS as $i => [$nombre, $apellido, $delegacion, $subdelegacion]) {
            $numero = str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);

            $user = User::firstOrCreate(
                ['email' => "alumno{$numero}@aamevi.ar"],
                [
                    'password' => 'password',
                    'first_name' => $nombre,
                    'last_name' => $apellido,
                    'role' => UserRole::Student,
                    // Dos cuentas desactivadas, para que el filtro de estado del
                    // listado tenga algo que filtrar
                    'is_active' => ! in_array($i, [7, 18], true),
                    'email_verified_at' => now(),
                ],
            );

            Student::firstOrCreate(
                ['id' => $user->getKey()],
                [
                    'dni' => (string) (28_000_000 + $i * 137_411),
                    'date_of_birth' => now()->subYears(28 + ($i % 22))->subDays($i * 11)->toDateString(),
                    'phone' => '011'.str_pad((string) (4000_0000 + $i * 5_431), 8, '0', STR_PAD_LEFT),
                    'cell_phone' => '011'.str_pad((string) (1500_0000 + $i * 7_919), 8, '0', STR_PAD_LEFT),
                    'delegation' => $delegacion,
                    'sub_delegation' => $subdelegacion,
                ],
            );
        }

        $this->command?->info('Alumnos de prueba: '.count(self::ALUMNOS).' (alumno01@aamevi.ar … contraseña: password)');
    }
}
