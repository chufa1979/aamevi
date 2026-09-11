<?php

namespace App\Filament\Widgets;

use App\Models\Quiz;
use App\Models\Course;
use App\Enums\EmailStatus;
use App\Enums\TicketStatus;
use App\Models\Certificate;
use App\Models\QueuedEmail;
use App\Models\QuizAttempt;
use App\Models\SupportTicket;
use App\Enums\EnrollmentStatus;
use App\Models\CourseEnrollment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Filament\Resources\EnrollmentRequests\EnrollmentRequestResource;

/**
 * Lo que le urge mirar al administrador al entrar: qué está esperando por él y
 * qué se rompió. No repite lo que cada recurso ya muestra en su propio badge de
 * navegación, lo junta en un solo lugar antes de decidir a dónde ir.
 */
class AdminStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Inscripciones pendientes', (string) CourseEnrollment::where('status', EnrollmentStatus::Pending)->count())
                ->description('Esperando aprobación')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning')
                ->url(EnrollmentRequestResource::getUrl()),

            Stat::make('Consultas sin responder', (string) SupportTicket::where('status', TicketStatus::Open)->count())
                ->description('En todos los cursos')
                ->descriptionIcon('heroicon-o-chat-bubble-left-right')
                ->color('warning'),

            Stat::make('Alumnos trabados', (string) QuizAttempt::ultimosPorEvaluacion(Quiz::query()->select('id'))->trabados()->count())
                ->description('Sin intentos y sin aprobar')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger'),

            Stat::make('Avisos que fallaron', (string) QueuedEmail::where('status', EmailStatus::Failed)->count())
                ->description('En la cola de email')
                ->descriptionIcon('heroicon-o-envelope')
                ->color(QueuedEmail::where('status', EmailStatus::Failed)->exists() ? 'danger' : 'success'),

            Stat::make('Certificados este mes', (string) Certificate::whereMonth('issued_at', now()->month)
                ->whereYear('issued_at', now()->year)
                ->count())
                ->description('Cursos completados')
                ->descriptionIcon('heroicon-o-academic-cap')
                ->color('success'),

            Stat::make('Cursos activos', (string) Course::where('is_active', true)->count())
                ->description('Publicados hoy')
                ->descriptionIcon('heroicon-o-book-open')
                ->color('primary'),
        ];
    }
}
