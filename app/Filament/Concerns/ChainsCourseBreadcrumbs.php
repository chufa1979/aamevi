<?php

namespace App\Filament\Concerns;

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\CourseModule;
use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\CourseClasses\CourseClassResource;
use App\Filament\Resources\CourseModules\CourseModuleResource;

/**
 * Arma a mano la cadena de breadcrumbs hacia el curso, para las páginas de
 * módulo y de clase.
 *
 * `CourseModuleResource` y `CourseClassResource` son recursos sueltos, sin
 * `$parentResource` — a propósito: declararlo anidaría sus rutas bajo el
 * curso (`/admin/courses/{course}/course-modules/{id}/...`) y este proyecto
 * las mantiene planas (`/admin/course-modules/{id}/...`), creadas siempre
 * desde el padre y ocultas de la navegación (ver CLAUDE.md). Pero eso mismo
 * es lo que hace que Filament no pueda reconstruir la cadena solo — sin
 * `$parentResource` no tiene de dónde sacar el curso, y por defecto el
 * breadcrumb caía en el listado plano del recurso («Módulos»), que no dice
 * de qué curso es ni lleva a ningún lado útil.
 */
trait ChainsCourseBreadcrumbs
{
    /** Curso → Contenidos, los dos como link. */
    protected function courseBreadcrumbs(Course $course): array
    {
        return [
            CourseResource::getUrl('edit', ['record' => $course]) => $course->title,
            CourseResource::getUrl('content', ['record' => $course]) => 'Contenidos',
        ];
    }

    /** Lo de arriba + el módulo, como link. */
    protected function moduleBreadcrumbs(CourseModule $module): array
    {
        return [
            ...$this->courseBreadcrumbs($module->course),
            CourseModuleResource::getUrl('edit', ['record' => $module]) => $module->title,
        ];
    }

    /** Lo de arriba + «Clases», como link. */
    protected function moduleClassesBreadcrumbs(CourseModule $module): array
    {
        return [
            ...$this->moduleBreadcrumbs($module),
            CourseModuleResource::getUrl('classes', ['record' => $module]) => 'Clases',
        ];
    }

    /** Lo de arriba + la clase, como link. */
    protected function classBreadcrumbs(CourseClass $class): array
    {
        return [
            ...$this->moduleClassesBreadcrumbs($class->module),
            CourseClassResource::getUrl('edit', ['record' => $class]) => $class->title,
        ];
    }
}
