<?php

namespace App\Filament\Widgets;

use Filament\Tables\Table;
use App\Enums\TicketStatus;
use Filament\Actions\Action;
use App\Models\SupportTicket;
use Filament\Widgets\TableWidget;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Courses\Pages\CourseTickets;

/**
 * Las consultas que esperan respuesta, de todos los cursos.
 *
 * Cada curso ya avisa las suyas en su solapa Consultas; acá está el cruce, que
 * es lo que le urge ver al administrador sin entrar curso por curso.
 */
class PendingSupportTickets extends TableWidget
{
    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => SupportTicket::query()
                ->where('status', TicketStatus::Open)
                ->with(['course', 'student.user']))
            ->heading('Consultas sin responder')
            ->defaultSort('created_at')
            ->paginated([5])
            ->columns([
                TextColumn::make('student.user.full_name')
                    ->label('Alumno'),

                TextColumn::make('course.title')
                    ->label('Curso'),

                TextColumn::make('subject')
                    ->label('Asunto')
                    ->limit(50),

                TextColumn::make('created_at')
                    ->label('Desde')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('ver')
                    ->label('Ver y responder')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (SupportTicket $record): ?string => $record->course === null
                        ? null
                        : CourseTickets::getUrl(['record' => $record->course]))
                    ->visible(fn (SupportTicket $record): bool => $record->course !== null),
            ])
            ->emptyStateHeading('No hay consultas esperando respuesta')
            ->emptyStateDescription('Cuando un alumno escriba y nadie le haya contestado todavía, va a aparecer acá.');
    }
}
