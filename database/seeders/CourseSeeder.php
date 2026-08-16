<?php

namespace Database\Seeders;

use Carbon\Carbon;
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
use App\Services\QuizService;
use App\Models\QuestionOption;
use App\Models\TaskSubmission;
use App\Enums\ClassContentType;
use App\Enums\EnrollmentStatus;
use App\Enums\SubmissionStatus;
use Illuminate\Database\Seeder;
use App\Models\CourseEnrollment;
use App\Services\ProgressService;
use Illuminate\Support\Collection;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Storage;
use Database\Seeders\Data\CourseCatalog;

/**
 * Cinco cursos completos, con alumnos cursando y avance simulado.
 *
 * El objetivo no es tener «algo» cargado sino que el panel se vea como se va a
 * ver en uso: 28 módulos, 140 clases, 700 preguntas y veinte alumnos repartidos
 * con distinto grado de avance. Recién con ese volumen se nota si una pantalla
 * pagina bien, si la grilla de seguimiento es legible o si una consulta escala.
 *
 * **El cronograma va de julio a diciembre de 2026 a propósito.** Con la fecha
 * de hoy en el medio, cada curso queda partido en clases ya dictadas y clases
 * por venir, que es lo que hace visible la progresión: unas aparecen aprobadas
 * o en curso, y las siguientes bloqueadas o todavía no habilitadas.
 *
 * El avance se simula con `QuizService` y `ProgressService`, no escribiendo las
 * tablas a mano: así los intentos quedan con sus preguntas sorteadas y sus
 * respuestas, y el avance respeta el mismo gateo que en producción. Un alumno
 * no queda «aprobado» en una clase sin haber rendido.
 *
 * Es idempotente: cada registro se busca por su clave natural y el avance se
 * saltea si ya está. Las fechas se recalculan en cada corrida.
 *
 * **El texto de las preguntas es de relleno.** Se arma combinando el título de
 * la clase con cinco plantillas: alcanza para ver el sistema funcionando, pero
 * no es material didáctico y hay que reemplazarlo.
 */
class CourseSeeder extends Seeder
{
    /** El cronograma completo: todos los cursos empiezan y terminan acá dentro. */
    private const INICIO = '2026-07-01';

    private const FIN = '2026-12-20';

    /** Marcadores de posición que cargan de verdad, para ver la previsualización. */
    private const VIDEO_A = 'https://www.youtube.com/watch?v=aqz-KE-bpKQ';

    private const VIDEO_B = 'https://youtu.be/YE7VzlLtp-4';

    private const PDF = 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf';

    /** PDF válido de una página en blanco, para que las entregas se puedan abrir. */
    private const PDF_MINIMO = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
        ."2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
        ."3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]>>endobj\n"
        ."trailer<</Root 1 0 R>>\n%%EOF\n";

    /**
     * Plantillas de pregunta. Cada clase genera una de cada, sobre su propio
     * título, así que no se repite ninguna en todo el sistema.
     *
     * @var array<int, array{texto: string, opciones: array<int, array{0: string, 1: bool}>}>
     */
    private const FORMAS = [
        [
            'texto' => 'En relación con «%s», ¿cuál de estas afirmaciones es correcta?',
            'opciones' => [
                ['Es un factor modificable mediante intervenciones sobre el estilo de vida.', true],
                ['Depende exclusivamente de la carga genética del paciente.', false],
                ['Sólo se aborda con tratamiento farmacológico.', false],
                ['No cuenta con evidencia clínica que lo respalde.', false],
            ],
        ],
        [
            'texto' => '¿Qué conviene evaluar antes de intervenir sobre «%s»?',
            'opciones' => [
                ['El estado basal del paciente y los objetivos que se acuerden con él.', true],
                ['Únicamente la edad.', false],
                ['Nada en particular: la indicación es la misma para todos.', false],
                ['El resultado de un solo estudio de laboratorio.', false],
            ],
        ],
        [
            'texto' => 'Al trabajar «%s» en el consultorio, ¿cuál es el primer paso?',
            'opciones' => [
                ['Acordar con el paciente una meta concreta y alcanzable.', true],
                ['Entregar un folleto genérico y citarlo en seis meses.', false],
                ['Derivar sin evaluar.', false],
                ['Indicar suplementos de entrada.', false],
            ],
        ],
        [
            'texto' => 'Sobre «%s», la evidencia disponible muestra que…',
            'opciones' => [
                ['Los cambios sostenidos producen mejoras clínicas medibles.', true],
                ['No hay diferencias respecto de no intervenir.', false],
                ['Los efectos recién aparecen después de diez años.', false],
                ['Sólo se aplica a pacientes jóvenes y sanos.', false],
            ],
        ],
        [
            'texto' => '¿Qué error es más frecuente al abordar «%s»?',
            'opciones' => [
                ['Indicar un cambio general sin adaptarlo a la vida del paciente.', true],
                ['Registrar la línea de base antes de empezar.', false],
                ['Acordar objetivos por escrito.', false],
                ['Citar al paciente para seguimiento.', false],
            ],
        ],
    ];

    public function __construct(
        private readonly QuizService $quizzes,
        private readonly ProgressService $progreso,
        private readonly NotificationService $avisos,
    ) {}

    public function run(): void
    {
        $cursos = collect(CourseCatalog::all())
            ->map(fn (array $datos, int $i): Course => $this->armarCurso($datos, $i));

        $this->inscribir($cursos);
        $this->simularAvance($cursos);

        $this->command?->info(sprintf(
            'Contenido de ejemplo: %d cursos, %d módulos, %d clases, %d preguntas, %d inscripciones.',
            Course::count(),
            CourseModule::count(),
            CourseClass::count(),
            Question::count(),
            CourseEnrollment::count(),
        ));
    }

    // ── Contenido ───────────────────────────────────────────────────────────

    /** @param array<string, mixed> $datos */
    private function armarCurso(array $datos, int $indice): Course
    {
        $course = Course::updateOrCreate(
            ['title' => $datos['title']],
            [
                'description' => $datos['description'],
                'teacher_id' => $this->docente($datos['teacher'])->getKey(),
                'max_students' => $datos['max_students'],
                'is_active' => true,
            ],
        );

        $fechas = $this->cronograma($datos, $indice);
        $clase = 0;

        foreach ($datos['modules'] as $orden => [$titulo, $titulosDeClase]) {
            $module = CourseModule::updateOrCreate(
                ['course_id' => $course->getKey(), 'order_number' => $orden + 1],
                ['title' => $titulo, 'description' => '<p>Módulo '.($orden + 1).' de «'.$datos['title'].'».</p>'],
            );

            foreach ($titulosDeClase as $posicion => $tituloClase) {
                $this->armarClase($module, $tituloClase, $posicion + 1, $fechas[$clase], $clase);
                $clase++;
            }

            $this->examenDeModulo($module, $orden);
        }

        return $course->fresh();
    }

    /**
     * Reparte las clases del curso entre INICIO y FIN.
     *
     * El espaciado sale de la cantidad de clases y no al revés: un curso de
     * cuarenta clases y otro de veinte tienen que caer los dos dentro de la
     * misma ventana, o uno terminaría el año siguiente.
     *
     * @param  array<string, mixed>  $datos
     * @return array<int, Carbon>
     */
    private function cronograma(array $datos, int $indice): array
    {
        $total = collect($datos['modules'])->sum(fn (array $m): int => count($m[1]));

        // Un curso puede traer su propia ventana: es lo que permite tener una
        // edición ya terminada entre las que se están dictando
        [$desde, $hasta] = $datos['schedule'] ?? [self::INICIO, self::FIN];

        // Cada curso arranca unos días después del anterior, para que no queden
        // todos dictando el mismo día
        $inicio = Carbon::parse($desde)->addDays(isset($datos['schedule']) ? 0 : $indice * 3)->setTime(19, 0);
        $fin = Carbon::parse($hasta)->setTime(19, 0);

        $paso = $total > 1 ? $inicio->diffInDays($fin) / ($total - 1) : 0;

        return collect(range(0, $total - 1))
            ->map(fn (int $i): Carbon => $inicio->copy()->addDays((int) round($i * $paso)))
            ->all();
    }

    private function armarClase(CourseModule $module, string $titulo, int $orden, Carbon $fecha, int $global): void
    {
        // Una clase en vivo cada seis, con su enlace de Meet
        $enVivo = $global % 6 === 5;

        $class = CourseClass::updateOrCreate(
            ['module_id' => $module->getKey(), 'order_number' => $orden],
            [
                'title' => $titulo,
                'description' => '<p>'.$titulo.'.</p>',
                'activation_date' => $fecha,
                'is_live_session' => $enVivo,
                'meet_link' => $enVivo ? 'https://meet.google.com/abc-defg-hij' : null,
                'is_live_recording_available' => $enVivo && $fecha->isPast(),
            ],
        );

        $this->materiales($class, $titulo, $global);
        $this->preguntas($class, $titulo);

        Quiz::updateOrCreate(
            ['class_id' => $class->getKey()],
            [
                'title' => 'Autoevaluación: '.$titulo,
                'questions_per_student' => 3,
                'passing_score' => 60,
                'max_attempts' => 3,
            ],
        );
    }

    /** Cuatro combinaciones de material, rotando, para que no sean todas iguales. */
    private function materiales(CourseClass $class, string $titulo, int $global): void
    {
        $combinaciones = [
            [
                [ClassContentType::Video, 'Clase grabada', self::VIDEO_A, null],
                [ClassContentType::Text, 'Apunte de lectura', null, '<p>Puntos clave de <strong>'.$titulo.'</strong>.</p>'],
            ],
            [
                [ClassContentType::Pdf, 'Material de lectura', self::PDF, null],
                [ClassContentType::Text, 'Resumen', null, '<p>Síntesis de <em>'.$titulo.'</em>.</p>'],
            ],
            [
                [ClassContentType::Video, 'Clase grabada', self::VIDEO_B, null],
                [ClassContentType::Task, 'Trabajo práctico', null, '<p>Aplicá lo visto en «'.$titulo.'» a un caso propio y subí el archivo.</p>'],
            ],
            [
                [ClassContentType::Video, 'Clase grabada', self::VIDEO_A, null],
                [ClassContentType::Pdf, 'Guía de trabajo', self::PDF, null],
                [ClassContentType::Text, 'Para seguir leyendo', null, '<p>Bibliografía ampliatoria.</p>'],
            ],
        ];

        foreach ($combinaciones[$global % 4] as $i => [$tipo, $tituloMaterial, $url, $descripcion]) {
            ClassContent::updateOrCreate(
                ['class_id' => $class->getKey(), 'order_number' => $i + 1],
                [
                    'type' => $tipo,
                    'title' => $tituloMaterial,
                    'content_url' => $url,
                    'description' => $descripcion,
                ],
            );
        }
    }

    /** Cinco preguntas por clase, una por plantilla, sobre el título de la clase. */
    private function preguntas(CourseClass $class, string $titulo): void
    {
        foreach (self::FORMAS as $i => $forma) {
            $question = Question::updateOrCreate(
                ['class_id' => $class->getKey(), 'order_number' => $i + 1],
                ['text' => '<p>'.sprintf($forma['texto'], $titulo).'</p>', 'is_active' => true],
            );

            foreach ($forma['opciones'] as $j => [$texto, $correcta]) {
                QuestionOption::updateOrCreate(
                    ['question_id' => $question->getKey(), 'order_number' => $j + 1],
                    ['option_text' => $texto, 'is_correct' => $correcta],
                );
            }
        }
    }

    /**
     * Examen del módulo, con porcentaje variable.
     *
     * El cuarto de cada cinco queda sin examen: el examen es opcional, y si
     * todos lo tuvieran no se vería la diferencia en la solapa Exámenes.
     */
    private function examenDeModulo(CourseModule $module, int $orden): void
    {
        if ($orden % 5 === 3) {
            return;
        }

        Quiz::updateOrCreate(
            ['module_id' => $module->getKey()],
            [
                'title' => 'Examen del módulo: '.$module->title,
                'questions_percentage' => [30, 40, 50][$orden % 3],
                'passing_score' => 70,
                'max_attempts' => 2,
            ],
        );
    }

    // ── Personas ────────────────────────────────────────────────────────────

    /** El docente con ese email, creándolo si hace falta. */
    private function docente(string $email): Teacher
    {
        $esInvitada = $email === 'profesora@aamevi.ar';

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'password' => 'password',
                'first_name' => $esInvitada ? 'Docente' : 'Profesor',
                'last_name' => $esInvitada ? 'Invitada' : 'De Prueba',
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

    /**
     * Reparte los alumnos entre los cursos.
     *
     * Cada alumno entra en dos o tres cursos y las inscripciones quedan en
     * estados distintos: la mayoría aprobadas, algunas pendientes de resolver y
     * una rechazada, para que la solapa Alumnos del curso tenga qué mostrar.
     *
     * @param  Collection<int, Course>  $cursos
     */
    private function inscribir($cursos): void
    {
        $alumnos = Student::with('user')
            ->get()
            ->sortBy(fn (Student $s): string => $s->user?->email ?? '')
            ->values();

        foreach ($alumnos as $i => $student) {
            // Dos cursos para todos, un tercero para uno de cada tres
            $cantidad = $i % 3 === 0 ? 3 : 2;

            foreach (range(0, $cantidad - 1) as $n) {
                $course = $cursos[($i + $n * 2) % $cursos->count()];

                $enrollment = CourseEnrollment::firstOrCreate(
                    ['course_id' => $course->getKey(), 'student_id' => $student->getKey()],
                    [
                        'enrollment_date' => Carbon::parse(self::INICIO)->subDays(30 - $i),
                        // El default de la columna no llega al modelo recién
                        // creado, y sin esto isPending() daría falso
                        'status' => EnrollmentStatus::Pending,
                    ],
                );

                if (! $enrollment->isPending()) {
                    continue;
                }

                match (true) {
                    // Una de cada once queda pendiente y una rechazada
                    $i % 11 === 7 => null,
                    $i % 11 === 9 => $enrollment->reject($course->teacher),
                    default => $enrollment->approve($course->teacher),
                };
            }
        }
    }

    // ── Avance ──────────────────────────────────────────────────────────────

    /**
     * Simula lo que hicieron los alumnos hasta hoy.
     *
     * Cada alumno tiene un ritmo distinto —al día, atrasado, recién empezando o
     * sin entrar nunca—, porque una grilla donde todos van igual no muestra
     * nada. Sólo se tocan las clases cuya fecha ya pasó: las futuras tienen que
     * quedar sin avance para que se vean como «no habilitada».
     *
     * @param  Collection<int, Course>  $cursos
     */
    private function simularAvance($cursos): void
    {
        foreach ($cursos as $course) {
            /*
             * Las finalizadas también entran, y el orden es explícito. Si no,
             * el seeder deja de ser idempotente: al volver a correr, los que ya
             * se recibieron quedarían fuera de la lista, los índices se
             * correrían y les tocaría otro ritmo a otros alumnos, que se
             * pondrían a rendir evaluaciones nuevas. Reprocesarlos no cuesta
             * nada porque `aprobar()` saltea las clases ya completadas.
             */
            $inscripciones = $course->enrollments()
                ->whereIn('status', EnrollmentStatus::ocupantes())
                ->with('student')
                ->orderBy('student_id')
                ->get()
                ->values();

            /*
             * En un curso que ya terminó no tiene sentido el reparto de ritmos:
             * nadie se quedó «al día» en una cursada que cerró hace meses. Unos
             * lo terminaron y otros lo abandonaron a mitad de camino, que es
             * como se ve una edición cerrada de verdad.
             */
            $cerrado = $this->yaTermino($course);
            $ritmos = $cerrado ? [1.0, 1.0, 0.6, 1.0, 0.3] : [1.0, 0.7, 0.4, 0.2, 0.0];

            foreach ($inscripciones as $i => $enrollment) {
                $student = $enrollment->student;

                if ($student === null) {
                    continue;
                }

                $ritmo = $ritmos[$i % 5];

                // Quien terminó una cursada cerrada tiene todo corregido: si le
                // quedara una tarea sin aprobar no habría certificado, y es
                // justamente lo que este curso viene a mostrar
                $this->avanzar($course, $student, $ritmo, $i, graduado: $cerrado && $ritmo === 1.0);
            }
        }
    }

    private function avanzar(Course $course, Student $student, float $ritmo, int $indice, bool $graduado = false): void
    {
        $dictadas = $course->modules()
            ->with(['classes' => fn ($q) => $q->where('activation_date', '<=', now()), 'classes.contents', 'classes.module.course'])
            ->get()
            ->flatMap->classes;

        if ($dictadas->isEmpty() || $ritmo === 0.0) {
            return;
        }

        $hasta = (int) floor($dictadas->count() * $ritmo);

        foreach ($dictadas->values() as $posicion => $class) {
            if ($posicion >= $hasta) {
                // La siguiente queda abierta pero sin terminar: es el estado
                // «en curso» de la grilla de seguimiento
                if ($posicion === $hasta) {
                    $this->progreso->start($student, $class);
                }

                return;
            }

            $this->aprobar($student, $class, $indice + $posicion, $graduado);
        }
    }

    /**
     * Rinde la autoevaluación de la clase, entrega sus tareas, y la da por vista.
     *
     * Las tareas van sí o sí: desde que la entrega entró en el gateo, una clase
     * con trabajo práctico sin entregar no se completa, y el alumno quedaría con
     * huecos en el medio de su avance.
     */
    private function aprobar(Student $student, CourseClass $class, int $indice, bool $graduado = false): void
    {
        if ($this->progreso->hasCompleted($student, $class)) {
            return;
        }

        $quiz = $class->quiz;

        if ($quiz !== null && ! $this->quizzes->hasPassed($quiz, $student)) {
            $attempt = $this->quizzes->start($quiz, $student);

            $this->quizzes->submit($attempt, $attempt->questions->mapWithKeys(
                fn (Question $q): array => [$q->getKey() => $q->options->firstWhere('is_correct', true)?->getKey()],
            )->all());
        }

        $this->entregarTareas($student, $class, $indice, $graduado);

        $this->progreso->complete($student, $class);
    }

    /**
     * Entrega las tareas de la clase, con distinta suerte.
     *
     * Una de cada tres queda sin corregir, y de las corregidas una parte sin
     * publicar: son los tres estados que tiene que poder mostrar la solapa
     * Calificaciones, y si todas terminaran iguales no se vería la diferencia.
     */
    private function entregarTareas(Student $student, CourseClass $class, int $indice, bool $graduado = false): void
    {
        $tareas = $class->contents->where('type', ClassContentType::Task);

        foreach ($tareas as $i => $tarea) {
            if (TaskSubmission::where('content_id', $tarea->getKey())->where('student_id', $student->getKey())->exists()) {
                continue;
            }

            // Al que se recibe se le aprueba y publica todo: el certificado
            // exige que no quede ninguna tarea sin aprobar
            $suerte = $graduado ? 4 : ($indice + $i) % 6;

            $submission = TaskSubmission::create([
                'content_id' => $tarea->getKey(),
                'student_id' => $student->getKey(),
                'attempt_number' => 1,
                'file_path' => $this->archivoDeEjemplo(),
                'file_name' => 'trabajo-practico.pdf',
                'submitted_at' => $class->activation_date->copy()->addDays(3),
                'status' => SubmissionStatus::Pending,
            ]);

            // 0 y 1: sin corregir · 2 y 3: corregida sin publicar · 4 y 5: publicada
            if ($suerte < 2) {
                continue;
            }

            $desaprobada = $suerte === 3;

            /*
             * Se escribe la corrección a mano en lugar de pasar por
             * `SubmissionService`: el servicio fecha todo con `now()`, y acá las
             * correcciones tienen que quedar en los días de su clase para que el
             * historial sea creíble.
             */
            $submission->update([
                // La nota tiene que acompañar al resultado: una desaprobada con
                // un nueve deja el ejemplo sin sentido
                'grade' => $desaprobada ? [3, 4, 5][$i % 3] : [6, 7, 8, 9][$suerte % 4],
                'status' => $desaprobada ? SubmissionStatus::Rejected : SubmissionStatus::Approved,
                'feedback' => $desaprobada
                    ? 'Faltó desarrollar la indicación por escrito. Volvé a entregarlo.'
                    : 'Buen desarrollo del caso.',
                'graded_by' => $class->module->course->teacher_id,
                'graded_at' => $class->activation_date->copy()->addDays(6),
                'published_at' => $suerte >= 4 ? $class->activation_date->copy()->addDays(7) : null,
            ]);

            // El aviso sí se encola, para que la cola de ejemplo tenga los tres
            // tipos y no sólo las inscripciones
            if ($suerte >= 4) {
                $this->avisos->taskGraded($submission->fresh());
            }
        }
    }

    /** ¿El curso ya se dictó entero? */
    private function yaTermino(Course $course): bool
    {
        $ultima = $course->classes()->max('activation_date');

        return $ultima !== null && Carbon::parse($ultima)->isPast();
    }

    /**
     * Un PDF mínimo, real, compartido por todas las entregas de ejemplo.
     *
     * Sin esto el enlace de descarga del panel daría 404 y parecería un error
     * del sistema en lugar de datos de prueba. Se escribe una sola vez.
     */
    private function archivoDeEjemplo(): string
    {
        $ruta = 'submissions/ejemplo.pdf';

        if (! Storage::disk('public')->exists($ruta)) {
            Storage::disk('public')->put($ruta, self::PDF_MINIMO);
        }

        return $ruta;
    }
}
