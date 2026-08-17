<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Student;
use App\Models\CourseClass;
use App\Enums\EnrollmentStatus;
use Illuminate\Support\Collection;

/**
 * El buscador del aula.
 *
 * Busca sólo lo que ese alumno puede abrir: los cursos que están en su catálogo
 * o que ya cursa, y las clases de los cursos en los que está inscripto. Buscar
 * en todo y después esconder los resultados ajenos filtraría igual: la lista de
 * títulos que existen ya es información.
 *
 * Las clases de un curso ajeno no aparecen aunque coincidan. Las de un curso
 * propio sí, incluso las que todavía no se habilitaron: figuran en el temario
 * desde el primer día, así que no revelan nada — y el resultado dice en qué
 * estado están en lugar de mandarlo a un 403.
 */
class SearchService
{
    /** Menos de esto devuelve media plataforma y no ayuda a nadie. */
    public const MINIMO = 2;

    public function __construct(private readonly ProgressService $progreso) {}

    /**
     * @return array{cursos: Collection<int, Course>, clases: Collection<int, CourseClass>}
     */
    public function forStudent(Student $student, string $termino): array
    {
        $termino = trim($termino);

        if (mb_strlen($termino) < self::MINIMO) {
            return ['cursos' => collect(), 'clases' => collect()];
        }

        return [
            'cursos' => $this->cursos($student, $termino),
            'clases' => $this->clases($student, $termino),
        ];
    }

    /**
     * Los cursos que le son visibles: los que ya cursa y los que podría pedir.
     *
     * @return Collection<int, Course>
     */
    private function cursos(Student $student, string $termino): Collection
    {
        return Course::query()
            ->where(function ($q) use ($student): void {
                $q->whereHas('enrollments', fn ($e) => $e
                    ->where('student_id', $student->getKey())
                    ->whereIn('status', EnrollmentStatus::ocupantes()))
                    ->orWhere(fn ($abiertos) => $abiertos->availableFor($student));
            })
            ->where(fn ($q) => $this->coincide($q, $termino, ['title', 'description']))
            ->with('teacher.user')
            ->orderBy('title')
            ->limit(20)
            ->get();
    }

    /**
     * Las clases de los cursos que cursa.
     *
     * @return Collection<int, CourseClass>
     */
    private function clases(Student $student, string $termino): Collection
    {
        return CourseClass::query()
            ->whereHas('module.course.enrollments', fn ($e) => $e
                ->where('student_id', $student->getKey())
                ->whereIn('status', EnrollmentStatus::ocupantes()))
            ->where(fn ($q) => $this->coincide($q, $termino, ['title', 'description']))
            ->with('module.course')
            ->orderBy('order_number')
            ->limit(30)
            ->get();
    }

    /**
     * Coincidencia por partes: «medicina estilo» encuentra «Medicina del estilo
     * de vida», que con el término entero como una sola cadena no aparecería.
     *
     * @param  array<int, string>  $columnas
     */
    private function coincide($query, string $termino, array $columnas): void
    {
        foreach (preg_split('/\s+/', $termino) ?: [] as $palabra) {
            $query->where(function ($q) use ($palabra, $columnas): void {
                foreach ($columnas as $columna) {
                    $q->orWhere($columna, 'like', '%'.$palabra.'%');
                }
            });
        }
    }

    /**
     * En qué estado está la clase para este alumno, para no ofrecerle un enlace
     * que le va a rebotar.
     */
    public function estadoDe(Student $student, CourseClass $class): ?string
    {
        return $this->progreso->lockReason($student, $class);
    }
}
