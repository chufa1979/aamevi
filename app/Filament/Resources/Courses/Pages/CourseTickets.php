<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Models\Course;
use Filament\Tables\Table;
use App\Enums\TicketStatus;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use App\Models\SupportTicket;
use App\Services\SupportService;
use Illuminate\Contracts\View\View;
use App\Exceptions\SupportException;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\Courses\CourseResource;
use Filament\Resources\Pages\ManageRelatedRecords;

/**
 * Las consultas de los alumnos del curso.
 *
 * Cuelgan del curso, a diferencia de FID, donde el listado vivía dentro del menú
 * del curso pero mostraba las de todos. Las atiende quien lo dicta: una duda
 * sobre una clase no tiene por qué pasar por gente que no la dictó.
 *
 * De sólo lectura salvo por responder y cerrar: el texto lo escribió el alumno.
 */
class CourseTickets extends ManageRelatedRecords
{
    protected static string $resource = CourseResource::class;

    protected static string $relationship = 'tickets';

    protected static ?string $navigationLabel = 'Consultas';

    protected static ?string $title = 'Consultas a mesa de ayuda';

    protected static ?string $breadcrumb = 'Consultas';

    /**
     * Cuántas esperan respuesta en **este** curso.
     *
     * El curso sale de la ruta y no del registro de la página: Filament pide el
     * badge por estático, sin instancia. La solapa sólo se dibuja dentro de un
     * curso, así que ahí el parámetro siempre está.
     */
    public static function getNavigationBadge(): ?string
    {
        $course = Course::find(request()->route('record'));

        if ($course === null) {
            return null;
        }

        $esperando = app(SupportService::class)->pendingInCourse($course);

        return $esperando > 0 ? (string) $esperando : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel('consulta')
            ->pluralModelLabel('consultas')
            // Las que esperan respuesta primero: es la única razón para entrar acá
            ->defaultSort('updated_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['student.user', 'messages.author']))
            ->columns([
                TextColumn::make('student.user.full_name')
                    ->label('Alumno')
                    ->searchable(['users.first_name', 'users.last_name'])
                    ->sortable(),

                TextColumn::make('subject')
                    ->label('Asunto')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),

                TextColumn::make('messages_count')
                    ->label('Mensajes')
                    ->counts('messages')
                    ->alignCenter(),

                TextColumn::make('updated_at')
                    ->label('Última actividad')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(TicketStatus::class),
            ])
            ->recordActions([
                Action::make('hilo')
                    ->label('Ver y responder')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->modalHeading(fn (SupportTicket $record): string => $record->subject)
                    ->modalWidth('3xl')
                    ->modalSubmitActionLabel('Responder')
                    ->modalContent(fn (SupportTicket $record): View => view('filament.support-thread', [
                        'ticket' => $record,
                    ]))
                    ->schema(fn (SupportTicket $record): array => $record->isClosed() ? [] : [
                        Textarea::make('mensaje')
                            ->label('Respuesta')
                            ->rows(4)
                            ->required(),
                    ])
                    /*
                     * Cerrada no se contesta: el botón de enviar sobra. `false`
                     * lo saca y `null` deja el que Filament arma solo — un
                     * `true` acá revienta, porque el getter espera una acción.
                     */
                    ->modalSubmitAction(fn (SupportTicket $record) => $record->isClosed() ? false : null)
                    ->action(function (SupportTicket $record, array $data): void {
                        try {
                            app(SupportService::class)->reply(
                                $record,
                                auth()->user(),
                                $data['mensaje'] ?? '',
                            );
                        } catch (SupportException $e) {
                            Notification::make()->title('No se pudo responder')->body($e->getMessage())->danger()->send();

                            return;
                        }

                        Notification::make()
                            ->title('Respuesta enviada')
                            ->body('Se le encoló el aviso por email.')
                            ->success()
                            ->send();
                    }),

                Action::make('cerrar')
                    ->label('Cerrar')
                    ->icon('heroicon-o-check-circle')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('Una consulta cerrada no recibe más mensajes. El alumno puede abrir otra.')
                    ->visible(fn (SupportTicket $record): bool => ! $record->isClosed())
                    ->action(function (SupportTicket $record): void {
                        app(SupportService::class)->close($record);

                        Notification::make()->title('Consulta cerrada')->success()->send();
                    }),
            ])
            ->emptyStateHeading('No hay consultas')
            ->emptyStateDescription('Acá aparecen las preguntas que hagan los alumnos de este curso.');
    }
}
