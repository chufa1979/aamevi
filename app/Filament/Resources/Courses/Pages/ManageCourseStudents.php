<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Models\Student;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use App\Enums\EnrollmentStatus;
use App\Models\CourseEnrollment;
use Filament\Actions\CreateAction;
use App\Services\CertificateService;
use Filament\Forms\Components\Select;
use App\Exceptions\EnrollmentException;
use Filament\Tables\Columns\TextColumn;
use App\Exceptions\CertificateException;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\Courses\CourseResource;
use Filament\Resources\Pages\ManageRelatedRecords;

/** Los alumnos inscriptos al curso, con la aprobación de las solicitudes. */
class ManageCourseStudents extends ManageRelatedRecords
{
    protected static string $resource = CourseResource::class;

    protected static string $relationship = 'enrollments';

    protected static ?string $navigationLabel = 'Alumnos del curso';

    protected static ?string $title = 'Alumnos del curso';

    protected static ?string $breadcrumb = 'Alumnos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('student_id')
                ->label('Alumno')
                ->options(fn (): array => Student::with('user')
                    ->get()
                    ->mapWithKeys(fn (Student $s): array => [$s->id => $s->user->full_name])
                    ->all())
                ->searchable()
                ->required()
                // El unique de la tabla ya lo impide, pero un error de validación
                // es mejor que un choque contra la base
                ->unique(
                    table: 'course_enrollments',
                    column: 'student_id',
                    modifyRuleUsing: fn ($rule) => $rule->where('course_id', $this->getOwnerRecord()->getKey()),
                )
                ->validationMessages(['unique' => 'Ese alumno ya está inscripto en este curso.']),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('certificate'))
            ->recordTitleAttribute('id')
            // Los títulos de los modales salen de acá: esta clase de página no
            // lee las propiedades estáticas $modelLabel del recurso.
            ->modelLabel('inscripción')
            ->pluralModelLabel('inscripciones')
            ->defaultSort('enrollment_date', 'desc')
            ->columns([
                TextColumn::make('student.user.full_name')
                    ->label('Alumno')
                    ->searchable(['users.first_name', 'users.last_name'])
                    ->sortable(),

                TextColumn::make('student.dni')
                    ->label('DNI')
                    ->placeholder('—'),

                TextColumn::make('enrollment_date')
                    ->label('Solicitó')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),

                TextColumn::make('certificate.certificate_number')
                    ->label('Certificado')
                    ->placeholder('—')
                    ->description(fn (CourseEnrollment $record): ?string => $record->certificate
                        ? 'Emitido el '.$record->certificate->issued_at->format('d/m/Y')
                        : null),

                TextColumn::make('approvedBy.user.full_name')
                    ->label('Resuelta por')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(EnrollmentStatus::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Inscribir alumno')
                    // Alta desde administración: entra directamente aprobada,
                    // porque el acto de crearla acá ya es la aprobación
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
                self::emitirCertificado(),
            ])
            ->emptyStateHeading('Todavía no hay inscripciones');
    }

    /**
     * Emisión manual del certificado.
     *
     * El sistema lo emite solo cuando el alumno completa el curso; esto es para
     * lo que la regla no contempla —una entrega fuera de término que se decidió
     * aceptar, alguien que rindió aparte—. Por eso avisa cuando todavía no
     * corresponde en vez de impedirlo: la decisión es del docente, pero que la
     * tome sabiendo qué le falta.
     */
    private static function emitirCertificado(): Action
    {
        return Action::make('certificado')
            ->label('Emitir certificado')
            ->icon('heroicon-o-academic-cap')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Emitir el certificado')
            ->modalDescription(function (CourseEnrollment $record): string {
                $falta = app(CertificateService::class)->blocker($record->student, $record->course);

                return $falta === null
                    ? 'El alumno completó el curso. Se le emite el certificado y la inscripción queda finalizada.'
                    : "El alumno todavía no terminó: {$falta} Emitirlo igual lo da por aprobado.";
            })
            ->visible(fn (CourseEnrollment $record): bool => $record->status->ocupaCupo()
                && $record->certificate === null)
            ->action(function (CourseEnrollment $record): void {
                try {
                    $certificado = app(CertificateService::class)->issue($record);
                } catch (CertificateException|EnrollmentException $e) {
                    Notification::make()
                        ->title('No se pudo emitir')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Certificado emitido')
                    ->body($certificado->certificate_number)
                    ->success()
                    ->send();
            });
    }

    /**
     * Aprobar y rechazar comparten todo salvo el método del modelo. Las reglas
     * —solo desde pendiente, y no exceder el cupo— viven en CourseEnrollment;
     * acá solo se traduce la excepción a una notificación.
     */
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
                    // Un administrador no tiene ficha de profesor: queda en null
                    $record->{$metodo}(auth()->user()->teacher);
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
