<?php

namespace App\Filament\Resources\EnrollmentRequests;

use App\Enums\EnrollmentStatus;
use App\Models\CourseEnrollment;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\EnrollmentRequests\Pages\ManageEnrollmentRequests;

/**
 * Todas las solicitudes de inscripción, de todos los cursos, en una sola
 * pantalla — filtrable por curso y por estado.
 *
 * Es la pantalla de trabajo del perfil administrativo (`UserRole::Registrar`):
 * revisar el pago por otro medio y aceptar, o rechazar, sin tener que entrar
 * curso por curso a buscar qué está pendiente. La solapa "Alumnos" dentro de
 * cada curso (`ManageCourseStudents`) sigue existiendo para el administrador
 * y sigue siendo visible —de sólo lectura— para el docente, pero dejó de ser
 * donde se resuelve nada.
 */
class EnrollmentRequestResource extends Resource
{
    protected static ?string $model = CourseEnrollment::class;

    protected static ?string $slug = 'solicitudes';

    // Sin icono propio: lo lleva el grupo, ver AdminPanelProvider / RegistrarPanelProvider
    protected static ?string $navigationLabel = 'Solicitudes';

    protected static ?string $modelLabel = 'solicitud';

    protected static ?string $pluralModelLabel = 'solicitudes';

    protected static string|\UnitEnum|null $navigationGroup = 'Alumnos';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getRecordTitle(?Model $record): ?string
    {
        return $record instanceof CourseEnrollment
            ? "{$record->student?->user?->full_name} — {$record->course?->title}"
            : null;
    }

    /** Lo que espera resolución: mismo criterio que Consultas y Avisos por email. */
    public static function getNavigationBadge(): ?string
    {
        $pendientes = CourseEnrollment::where('status', EnrollmentStatus::Pending)->count();

        return $pendientes > 0 ? (string) $pendientes : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEnrollmentRequests::route('/'),
        ];
    }
}
