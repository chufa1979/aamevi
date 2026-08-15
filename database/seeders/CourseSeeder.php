<?php

namespace Database\Seeders;

use App\Models\Quiz;
use App\Models\User;
use App\Models\Course;
use App\Enums\UserRole;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Question;
use App\Models\CourseClass;
use App\Models\ClassContent;
use App\Models\CourseModule;
use App\Models\QuestionOption;
use App\Enums\ClassContentType;
use App\Enums\EnrollmentStatus;
use Illuminate\Database\Seeder;
use App\Models\CourseEnrollment;

/**
 * Contenido de ejemplo: tres cursos con módulos, clases, materiales de los
 * cuatro tipos, banco de preguntas, quiz de clase y examen de módulo.
 *
 * Sirve para tener el panel y el aula con algo que mirar sin cargar todo a
 * mano. Es idempotente: cada registro se busca por su clave natural, así que
 * se puede volver a correr sin duplicar nada.
 *
 * Las fechas de activación se recalculan en cada corrida, relativas a hoy: si
 * no, el curso "en marcha" iría quedando viejo y la clase que demuestra el
 * candado terminaría abriéndose sola.
 *
 * Los videos apuntan a cortos de la Blender Foundation y los PDF a un archivo
 * de prueba del W3C. Son marcadores de posición que cargan de verdad, para
 * poder ver la previsualización funcionando; el material real se sube desde
 * el panel.
 */
class CourseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->cursos() as $datos) {
            $course = $this->crearCurso($datos);

            foreach ($datos['modules'] as $i => $datosModulo) {
                $this->crearModulo($course, $datosModulo, $i + 1);
            }

            $this->inscribirAlumno($course, $datos['inscripcion']);
        }

        $this->command?->info('Cursos de ejemplo: '.Course::count().' cursos, '
            .CourseModule::count().' módulos, '.CourseClass::count().' clases.');
    }

    private function crearCurso(array $datos): Course
    {
        return Course::updateOrCreate(
            ['title' => $datos['title']],
            [
                'description' => $datos['description'],
                'teacher_id' => $this->docente($datos['teacher'])->getKey(),
                'max_students' => $datos['max_students'],
                'is_active' => true,
            ],
        );
    }

    private function crearModulo(Course $course, array $datos, int $orden): void
    {
        $module = CourseModule::updateOrCreate(
            ['course_id' => $course->getKey(), 'order_number' => $orden],
            ['title' => $datos['title'], 'description' => $datos['description']],
        );

        foreach ($datos['classes'] as $i => $datosClase) {
            $this->crearClase($module, $datosClase, $i + 1);
        }

        // El examen de módulo se crea al final, cuando el banco de preguntas de
        // sus clases ya existe: sin preguntas, `isReady()` daría falso.
        if (isset($datos['exam_percentage'])) {
            Quiz::updateOrCreate(
                ['module_id' => $module->getKey()],
                [
                    'title' => 'Examen del módulo: '.$datos['title'],
                    'questions_percentage' => $datos['exam_percentage'],
                    'passing_score' => 70,
                    'max_attempts' => 2,
                ],
            );
        }
    }

    private function crearClase(CourseModule $module, array $datos, int $orden): void
    {
        $class = CourseClass::updateOrCreate(
            ['module_id' => $module->getKey(), 'order_number' => $orden],
            [
                'title' => $datos['title'],
                'description' => $datos['description'],
                'activation_date' => now()->addDays($datos['days']),
                'is_live_session' => isset($datos['live']),
                'meet_link' => $datos['live']['meet'] ?? null,
                'is_live_recording_available' => $datos['live']['recording'] ?? false,
            ],
        );

        foreach ($datos['contents'] as $i => $contenido) {
            ClassContent::updateOrCreate(
                ['class_id' => $class->getKey(), 'order_number' => $i + 1],
                [
                    'type' => $contenido['type'],
                    'title' => $contenido['title'],
                    'description' => $contenido['description'] ?? null,
                    'content_url' => $contenido['url'] ?? null,
                ],
            );
        }

        foreach ($datos['questions'] ?? [] as $i => $pregunta) {
            $this->crearPregunta($class, $pregunta, $i + 1);
        }

        if (isset($datos['quiz'])) {
            Quiz::updateOrCreate(
                ['class_id' => $class->getKey()],
                [
                    'title' => 'Autoevaluación: '.$datos['title'],
                    'questions_per_student' => $datos['quiz']['questions_per_student'],
                    'passing_score' => $datos['quiz']['passing_score'],
                    'max_attempts' => 3,
                ],
            );
        }
    }

    private function crearPregunta(CourseClass $class, array $datos, int $orden): void
    {
        $question = Question::updateOrCreate(
            ['class_id' => $class->getKey(), 'order_number' => $orden],
            ['text' => $datos['text'], 'is_active' => true],
        );

        foreach ($datos['options'] as $i => [$texto, $correcta]) {
            QuestionOption::updateOrCreate(
                ['question_id' => $question->getKey(), 'order_number' => $i + 1],
                ['option_text' => $texto, 'is_correct' => $correcta],
            );
        }
    }

    /**
     * Inscribe al alumno de prueba con el estado pedido.
     *
     * La aprobación pasa por `approve()` y no por un `update` del status: es la
     * misma transición que hace el panel, y así el ejemplo queda con fecha de
     * aprobación y docente aprobador como cualquier inscripción real.
     */
    private function inscribirAlumno(Course $course, ?string $estado): void
    {
        if ($estado === null) {
            return;
        }

        $student = Student::find(User::where('email', 'alumno@aamevi.ar')->value('id'));

        if ($student === null) {
            return;
        }

        // `status` se pasa explícito aunque la columna tenga default: el modelo
        // que devuelve firstOrCreate al crear no lee de vuelta los defaults de
        // la base, y sin esto `isPending()` daría falso sobre una fila nueva.
        $enrollment = CourseEnrollment::firstOrCreate(
            ['course_id' => $course->getKey(), 'student_id' => $student->getKey()],
            [
                'enrollment_date' => now()->subDays(30),
                'status' => EnrollmentStatus::Pending,
            ],
        );

        if ($estado === 'approved' && $enrollment->isPending()) {
            $enrollment->approve($course->teacher);
        }
    }

    /** El docente con ese email, creándolo si hace falta. */
    private function docente(string $email): Teacher
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'password' => 'password',
                'first_name' => 'Docente',
                'last_name' => 'Invitada',
                'role' => UserRole::Teacher,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        return Teacher::firstOrCreate(
            ['id' => $user->getKey()],
            ['bio' => 'Docente de prueba.', 'specialization' => 'Medicina del estilo de vida'],
        );
    }

    /** Marcadores de posición que cargan de verdad. Ver el comentario de clase. */
    private const VIDEO_A = 'https://www.youtube.com/watch?v=aqz-KE-bpKQ';

    private const VIDEO_B = 'https://youtu.be/YE7VzlLtp-4';

    private const PDF = 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf';

    private function cursos(): array
    {
        return [
            [
                'title' => 'Fundamentos de la Medicina del Estilo de Vida',
                'description' => '<p>Introducción a los <strong>seis pilares</strong> de la medicina del estilo de vida y a la evidencia que los respalda.</p><ul><li>Alimentación</li><li>Actividad física</li><li>Sueño reparador</li><li>Manejo del estrés</li><li>Vínculos sociales</li><li>Evitar sustancias tóxicas</li></ul>',
                'max_students' => 40,
                'teacher' => 'profesor@aamevi.ar',
                'inscripcion' => 'approved',
                'modules' => [
                    [
                        'title' => 'Los seis pilares',
                        'description' => '<p>Qué es la disciplina, de dónde viene y qué resultados muestra.</p>',
                        'exam_percentage' => 40,
                        'classes' => [
                            [
                                'title' => '¿Qué es la medicina del estilo de vida?',
                                'description' => '<p>Definición, alcance y diferencias con la medicina preventiva clásica.</p>',
                                'days' => -30,
                                'contents' => [
                                    ['type' => ClassContentType::Video, 'title' => 'Clase grabada: presentación', 'url' => self::VIDEO_A, 'description' => 'Recorrido general por los seis pilares.'],
                                    ['type' => ClassContentType::Text, 'title' => 'Apunte de lectura', 'description' => '<p>La medicina del estilo de vida usa <em>intervenciones sobre el comportamiento</em> como tratamiento de primera línea en enfermedades crónicas.</p>'],
                                ],
                                'quiz' => ['questions_per_student' => 2, 'passing_score' => 60],
                                'questions' => [
                                    [
                                        'text' => '<p>¿Cuántos pilares tiene la medicina del estilo de vida?</p>',
                                        'options' => [['Cuatro', false], ['Seis', true], ['Ocho', false], ['Diez', false]],
                                    ],
                                    [
                                        'text' => '<p>¿Cuál de estos <strong>no</strong> es uno de los pilares?</p>',
                                        'options' => [['Sueño reparador', false], ['Vínculos sociales', false], ['Suplementación vitamínica', true], ['Actividad física', false]],
                                    ],
                                    [
                                        'text' => '<p>En esta disciplina, la intervención sobre el comportamiento se considera…</p>',
                                        'options' => [['Un complemento opcional', false], ['Tratamiento de primera línea', true], ['Un recurso de última instancia', false]],
                                    ],
                                ],
                            ],
                            [
                                'title' => 'Evidencia y resultados clínicos',
                                'description' => '<p>Qué dicen los estudios sobre reversión de enfermedad cardiovascular y diabetes tipo 2.</p>',
                                'days' => -23,
                                'contents' => [
                                    ['type' => ClassContentType::Pdf, 'title' => 'Revisión bibliográfica (PDF)', 'url' => self::PDF, 'description' => 'Material de lectura obligatoria.'],
                                    ['type' => ClassContentType::Text, 'title' => 'Puntos clave', 'description' => '<p>Prestar atención a los <strong>tamaños de muestra</strong> y a la duración del seguimiento.</p>'],
                                ],
                                'quiz' => ['questions_per_student' => 2, 'passing_score' => 70],
                                'questions' => [
                                    [
                                        'text' => '<p>¿Qué variable conviene mirar primero al evaluar un estudio de intervención?</p>',
                                        'options' => [['El color de los gráficos', false], ['El tamaño de muestra y el seguimiento', true], ['La cantidad de autores', false]],
                                    ],
                                    [
                                        'text' => '<p>La diabetes tipo 2, con intervención intensiva sobre el estilo de vida, puede…</p>',
                                        'options' => [['Entrar en remisión en parte de los casos', true], ['Curarse siempre en un mes', false], ['No modificarse en absoluto', false]],
                                    ],
                                    [
                                        'text' => '<p>¿Qué es un estudio longitudinal?</p>',
                                        'options' => [['El que sigue a los participantes en el tiempo', true], ['El que mide una sola vez', false], ['El que no tiene grupo control', false]],
                                    ],
                                ],
                            ],
                            [
                                'title' => 'Entrevista motivacional en el consultorio',
                                'description' => '<p>Cómo acompañar un cambio de hábitos sin caer en la prescripción vacía.</p>',
                                'days' => -16,
                                'contents' => [
                                    ['type' => ClassContentType::Video, 'title' => 'Demostración de entrevista', 'url' => self::VIDEO_B],
                                    ['type' => ClassContentType::Task, 'title' => 'Trabajo práctico 1', 'description' => '<p>Registrá una entrevista simulada de 10 minutos y subí el archivo. Fecha de entrega: dos semanas.</p>'],
                                ],
                            ],
                        ],
                    ],
                    [
                        'title' => 'La nutrición como pilar',
                        'description' => '<p>Patrones alimentarios con respaldo y cómo llevarlos a una indicación concreta.</p>',
                        'exam_percentage' => 50,
                        'classes' => [
                            [
                                'title' => 'Patrones alimentarios basados en plantas',
                                'description' => '<p>Qué muestran las poblaciones con mayor longevidad.</p>',
                                'days' => -9,
                                'contents' => [
                                    ['type' => ClassContentType::Video, 'title' => 'Clase grabada', 'url' => self::VIDEO_A],
                                    ['type' => ClassContentType::Pdf, 'title' => 'Tabla de equivalencias (PDF)', 'url' => self::PDF],
                                ],
                                'quiz' => ['questions_per_student' => 2, 'passing_score' => 70],
                                'questions' => [
                                    [
                                        'text' => '<p>Un patrón alimentario basado en plantas <strong>no</strong> implica necesariamente…</p>',
                                        'options' => [['Ser vegetariano estricto', true], ['Aumentar el consumo de fibra', false], ['Priorizar alimentos mínimamente procesados', false]],
                                    ],
                                    [
                                        'text' => '<p>¿Qué nutriente requiere atención especial en dietas veganas?</p>',
                                        'options' => [['Vitamina B12', true], ['Vitamina C', false], ['Potasio', false]],
                                    ],
                                ],
                            ],
                            [
                                'title' => 'Prescripción nutricional en el consultorio',
                                'description' => '<p>De la recomendación general a la indicación escrita.</p>',
                                'days' => -2,
                                'contents' => [
                                    ['type' => ClassContentType::Text, 'title' => 'Modelo de indicación', 'description' => '<p>Una indicación útil es <strong>específica, medible y acordada</strong> con el paciente.</p>'],
                                    ['type' => ClassContentType::Task, 'title' => 'Trabajo práctico 2', 'description' => '<p>Redactá una indicación nutricional para un caso clínico a elección.</p>'],
                                ],
                            ],
                            [
                                // Futura y dentro del curso en el que el alumno está inscripto:
                                // es la que deja ver el candado por fecha de activación
                                'title' => 'Casos clínicos integradores',
                                'description' => '<p>Cierre del módulo con discusión de casos.</p>',
                                'days' => 7,
                                'contents' => [
                                    ['type' => ClassContentType::Pdf, 'title' => 'Casos para preparar (PDF)', 'url' => self::PDF],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Actividad física y prescripción del ejercicio',
                'description' => '<p>Cómo pasar de "haga ejercicio" a una <strong>prescripción con dosis</strong>: tipo, frecuencia, intensidad y progresión.</p>',
                'max_students' => 25,
                'teacher' => 'profesor@aamevi.ar',
                'inscripcion' => 'pending',
                'modules' => [
                    [
                        'title' => 'Fisiología del movimiento',
                        'description' => '<p>Qué pasa en el cuerpo, y cómo se traduce en una indicación.</p>',
                        'exam_percentage' => 60,
                        'classes' => [
                            [
                                'title' => 'Adaptaciones al entrenamiento',
                                'description' => '<p>Sistema cardiovascular, músculo y hueso.</p>',
                                'days' => -14,
                                'contents' => [
                                    ['type' => ClassContentType::Video, 'title' => 'Clase grabada', 'url' => self::VIDEO_B],
                                    ['type' => ClassContentType::Pdf, 'title' => 'Guía de dosificación (PDF)', 'url' => self::PDF],
                                ],
                                'quiz' => ['questions_per_student' => 2, 'passing_score' => 70],
                                'questions' => [
                                    [
                                        'text' => '<p>¿Cuál es la recomendación semanal habitual de actividad aeróbica moderada en adultos?</p>',
                                        'options' => [['150 minutos', true], ['30 minutos', false], ['600 minutos', false]],
                                    ],
                                    [
                                        'text' => '<p>El entrenamiento de fuerza en adultos mayores impacta sobre todo en…</p>',
                                        'options' => [['Masa muscular y densidad ósea', true], ['Agudeza visual', false], ['Capacidad pulmonar total', false]],
                                    ],
                                ],
                            ],
                            [
                                'title' => 'Taller en vivo: armado de un plan',
                                'description' => '<p>Encuentro sincrónico. Traer un caso propio.</p>',
                                'days' => -7,
                                'live' => ['meet' => 'https://meet.google.com/abc-defg-hij', 'recording' => true],
                                'contents' => [
                                    ['type' => ClassContentType::Text, 'title' => 'Cómo prepararse', 'description' => '<p>Revisar la guía de dosificación de la clase anterior antes del encuentro.</p>'],
                                    ['type' => ClassContentType::Video, 'title' => 'Grabación del taller', 'url' => self::VIDEO_A],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Sueño, estrés y vínculos',
                'description' => '<p>Los tres pilares que menos se indican y más pesan en el resultado.</p>',
                'max_students' => 30,
                'teacher' => 'profesora@aamevi.ar',
                'inscripcion' => null,
                'modules' => [
                    [
                        'title' => 'Higiene del sueño',
                        'description' => '<p>Evaluación y abordaje del sueño en la consulta.</p>',
                        'classes' => [
                            [
                                'title' => 'Arquitectura del sueño',
                                'description' => '<p>Fases, ciclos y qué mide cada instrumento.</p>',
                                'days' => -5,
                                'contents' => [
                                    ['type' => ClassContentType::Video, 'title' => 'Clase grabada', 'url' => self::VIDEO_B],
                                ],
                                'quiz' => ['questions_per_student' => 1, 'passing_score' => 70],
                                'questions' => [
                                    [
                                        'text' => '<p>¿Cuánto dura aproximadamente un ciclo de sueño completo?</p>',
                                        'options' => [['90 minutos', true], ['20 minutos', false], ['4 horas', false]],
                                    ],
                                ],
                            ],
                            [
                                // Deliberadamente futura: es la que muestra el candado por fecha
                                'title' => 'Insomnio: abordaje no farmacológico',
                                'description' => '<p>Terapia cognitivo-conductual para el insomnio.</p>',
                                'days' => 10,
                                'contents' => [
                                    ['type' => ClassContentType::Pdf, 'title' => 'Protocolo (PDF)', 'url' => self::PDF],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
