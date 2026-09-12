<?php

namespace App\Filament\Resources\EnrollmentRequests\Pages;

use App\Models\Course;
use App\Models\Student;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use App\Enums\EnrollmentStatus;
use App\Models\CourseEnrollment;
use Filament\Actions\CreateAction;
use Illuminate\Contracts\View\View;
use App\Services\NotificationService;
use Filament\Forms\Components\Select;
use App\Exceptions\EnrollmentException;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Resources\EnrollmentRequests\EnrollmentRequestResource;

/**
 * La tabla en sí. Aprobar, rechazar y el alta directa repiten casi textual lo
 * que ya hace `ManageCourseStudents` para un curso a la vez — acá es la misma
 * mecánica sin el curso como registro dueño, así que el formulario de alta
 * pide también el curso, y la tabla suma esa columna y ese filtro.
 */
class ManageEnrollmentRequests extends ManageRecords
{
    protected static string $resource = EnrollmentRequestResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('course_id')
                ->label('Curso')
                ->options(fn (): array => Course::query()
                    ->where('is_active', true)
                    ->orderBy('title')
                    ->pluck('title', 'id')
                    ->all())
                ->searchable()
                ->required()
                ->live(),

            Select::make('student_id')
                ->label('Alumno')
                ->options(fn (): array => Student::with('user')
                    ->get()
                    ->mapWithKeys(fn (Student $s): array => [$s->id => $s->user->full_name])
                    ->all())
                ->searchable()
                ->required()
                ->unique(
                    table: 'course_enrollments',
                    column: 'student_id',
                    modifyRuleUsing: fn ($rule, $get) => $rule->where('course_id', $get('course_id')),
                )
                ->validationMessages(['unique' => 'Ese alumno ya está inscripto en ese curso.']),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['student.user', 'course', 'certificate', 'notes.author']))
            ->recordTitleAttribute('id')
            ->modelLabel('solicitud')
            ->pluralModelLabel('solicitudes')
            ->defaultSort('enrollment_date', 'desc')
            ->columns([
                TextColumn::make('student.user.full_name')
                    ->label('Alumno')
                    ->searchable(['users.first_name', 'users.last_name'])
                    ->sortable(),

                TextColumn::make('course.title')
                    ->label('Curso')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('enrollment_date')
                    ->label('Solicitó')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),

                TextColumn::make('approvedBy.full_name')
                    ->label('Resuelta por')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('course_id')
                    ->label('Curso')
                    ->options(fn (): array => Course::query()->orderBy('title')->pluck('title', 'id')->all())
                    ->searchable(),

                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(EnrollmentStatus::class)
                    // Lo que hay para hacer es lo pendiente; sin este default
                    // se abre mostrando también lo ya resuelto de meses atrás.
                    ->default(EnrollmentStatus::Pending->value),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Inscribir alumno')
                    ->mutateDataUsing(fn (array $data): array => [
                        ...$data,
                        'status' => EnrollmentStatus::Approved,
                        'enrollment_date' => now(),
                        'approval_date' => now(),
                    ]),
            ])
            ->recordActions([
                self::verAlumno(),
                self::bitacora(),
                self::avisar(),
                self::resolver('approve', 'Aprobar', 'Inscripción aprobada', 'heroicon-o-check-circle', 'success'),
                self::resolver('reject', 'Rechazar', 'Inscripción rechazada', 'heroicon-o-x-circle', 'danger'),
            ])
            ->emptyStateHeading('No hay solicitudes de inscripción');
    }

    /** Ficha del alumno, de sólo lectura: no hace falta salir a `StudentResource` a buscarlo. */
    private static function verAlumno(): Action
    {
        return Action::make('ver_alumno')
            ->label('Ver alumno')
            ->icon('heroicon-o-user')
            ->color('gray')
            ->modalHeading(fn (CourseEnrollment $record): string => $record->student?->user?->full_name ?? 'Alumno')
            ->modalWidth('2xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalContent(fn (CourseEnrollment $record): View => view('filament.enrollment-student', [
                'student' => $record->student,
            ]));
    }

    /**
     * La bitácora administrativa de la solicitud.
     *
     * Interna: no pasa por `email_queue`, el alumno nunca la ve. Existe para
     * pagos confirmados por fuera de la plataforma —transferencia, Mercado
     * Pago— o cualquier gestión resuelta por teléfono que hasta ahora no
     * quedaba escrita en ningún lado.
     */
    private static function bitacora(): Action
    {
        return Action::make('bitacora')
            ->label('Bitácora')
            ->icon('heroicon-o-clipboard-document-list')
            ->color('gray')
            ->modalHeading('Bitácora')
            ->modalWidth('2xl')
            ->modalSubmitActionLabel('Agregar')
            ->modalContent(fn (CourseEnrollment $record): View => view('filament.enrollment-notes', [
                'notas' => $record->notes,
            ]))
            ->schema([
                Textarea::make('nota')
                    ->label('Nueva anotación')
                    ->rows(3)
                    ->required(),
            ])
            ->action(function (CourseEnrollment $record, array $data): void {
                $record->notes()->create([
                    'author_id' => auth()->id(),
                    'body' => $data['nota'],
                    'created_at' => now(),
                ]);

                Notification::make()->title('Anotación agregada')->success()->send();
            });
    }

    /**
     * Un aviso administrativo, por fuera de cualquier curso o consulta.
     *
     * Sale por el mismo circuito que el resto de los avisos de la
     * plataforma —`email_queue`, drenada por `emails:enviar`—, no en el
     * momento.
     */
    private static function avisar(): Action
    {
        return Action::make('avisar')
            ->label('Enviar aviso')
            ->icon('heroicon-o-envelope')
            ->color('gray')
            ->modalHeading('Enviar aviso administrativo')
            ->modalSubmitActionLabel('Enviar')
            ->schema([
                TextInput::make('asunto')
                    ->label('Asunto')
                    ->required(),

                Textarea::make('mensaje')
                    ->label('Mensaje')
                    ->rows(5)
                    ->required(),
            ])
            ->action(function (CourseEnrollment $record, array $data): void {
                $destinatario = $record->student?->user;

                if ($destinatario === null) {
                    Notification::make()->title('No se pudo enviar')->body('El alumno ya no existe.')->danger()->send();

                    return;
                }

                app(NotificationService::class)->administrativeNotice($destinatario, $data['asunto'], $data['mensaje']);

                Notification::make()
                    ->title('Aviso enviado')
                    ->body('Se le encoló el aviso por email.')
                    ->success()
                    ->send();
            });
    }

    /** Mismo mecanismo que `ManageCourseStudents::resolver()`: ver ese comentario. */
    private static function resolver(string $metodo, string $label, string $exito, string $icono, string $color): Action
    {
        return Action::make($metodo)
            ->label($label)
            ->icon($icono)
            ->color($color)
            ->requiresConfirmation()
            ->visible(fn (CourseEnrollment $record): bool => $record->isPending())
            ->action(function (CourseEnrollment $record) use ($metodo, $exito): void {
                try {
                    $record->{$metodo}(auth()->user());
                } catch (EnrollmentException $e) {
                    Notification::make()
                        ->title('No se pudo completar')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()->title($exito)->success()->send();
            });
    }
}
