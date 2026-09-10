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
    /** @var array<int, array{string, string, string}> nombre, apellido, ciudad */
    private const ALUMNOS = [
        ['Camila', 'Ferreyra', 'La Plata'],
        ['Joaquín', 'Basualdo', 'CABA'],
        ['Malena', 'Ocampo', 'Villa María'],
        ['Nicolás', 'Zabala', 'Rosario'],
        ['Rocío', 'Bustamante', 'Godoy Cruz'],
        ['Tomás', 'Iriarte', 'Bahía Blanca'],
        ['Agustina', 'Peralta', 'San Miguel de Tucumán'],
        ['Bruno', 'Maldonado', 'Neuquén Capital'],
        ['Florencia', 'Quiroga', 'Paraná'],
        ['Ignacio', 'Sanabria', 'Salta Capital'],
        ['Julieta', 'Vergara', 'Mar del Plata'],
        ['Lucas', 'Andrada', 'Comodoro Rivadavia'],
        ['Micaela', 'Toledo', 'Río Cuarto'],
        ['Federico', 'Aguirre', 'Santa Fe Capital'],
        ['Valentina', 'Cardozo', 'Corrientes Capital'],
        ['Matías', 'Bengoechea', 'San Isidro'],
        ['Paula', 'Recalde', 'Posadas'],
        ['Santiago', 'Olmedo', 'San Juan Capital'],
        ['Delfina', 'Arrieta', 'Bariloche'],
        ['Gonzalo', 'Ledesma', 'San Salvador de Jujuy'],
    ];

    /** @var array<int, string> rota entre los veinte alumnos para dar variedad sin inventar veinte universidades */
    private const UNIVERSIDADES = [
        'Universidad de Buenos Aires',
        'Universidad Austral',
        'Universidad Nacional de Córdoba',
        'Universidad Nacional de Rosario',
    ];

    private const PROFESIONES = ['Médico/a', 'Nutricionista', 'Kinesiólogo/a', 'Psicólogo/a', 'Enfermero/a'];

    public function run(): void
    {
        foreach (self::ALUMNOS as $i => [$nombre, $apellido, $ciudad]) {
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
                    'phone' => '+54 9 11 '.str_pad((string) (4000_0000 + $i * 5_431), 8, '0', STR_PAD_LEFT),
                    'country' => 'Argentina',
                    'city' => $ciudad,
                    'graduation_date' => now()->subYears(3 + ($i % 10))->toDateString(),
                    'university' => self::UNIVERSIDADES[$i % count(self::UNIVERSIDADES)],
                    'profession' => self::PROFESIONES[$i % count(self::PROFESIONES)],
                    // Uno de cada tres tiene número de socio: es opcional, y así
                    // se ve el «—» del listado para el resto
                    'membership_number' => $i % 3 === 0 ? (string) (10_000 + $i) : null,
                ],
            );
        }

        $this->command?->info('Alumnos de prueba: '.count(self::ALUMNOS).' (alumno01@aamevi.ar … contraseña: password)');
    }
}
