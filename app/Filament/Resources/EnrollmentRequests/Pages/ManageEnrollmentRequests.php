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
use Filament\Forms\Components\Select;
use App\Exceptions\EnrollmentException;
use Filament\Tables\Columns\TextColumn;
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
            ->modifyQueryUsing(fn ($query) => $query->with(['student.user', 'course', 'certificate']))
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
                self::resolver('approve', 'Aprobar', 'Inscripción aprobada', 'heroicon-o-check-circle', 'success'),
                self::resolver('reject', 'Rechazar', 'Inscripción rechazada', 'heroicon-o-x-circle', 'danger'),
            ])
            ->emptyStateHeading('No hay solicitudes de inscripción');
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
