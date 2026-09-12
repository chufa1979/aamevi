<?php

namespace App\Filament\Resources\Students\Tables;

use App\Models\User;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Illuminate\Contracts\View\View;
use App\Services\NotificationService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\TernaryFilter;

class StudentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Sin el withCount, la columna de inscripciones haría una consulta
            // por fila. `enrollments.course` alimenta la descripción de esa
            // misma columna: qué cursos, no sólo cuántos.
            ->modifyQueryUsing(fn ($query) => $query->with(['student', 'enrollments.course'])->withCount('enrollments'))
            ->columns([
                TextColumn::make('full_name')
                    ->label('Nombre')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['last_name']),

                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('student.dni')
                    ->label('DNI')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('student.city')
                    ->label('Ciudad')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('enrollments_count')
                    ->label('Cursos')
                    ->alignCenter()
                    ->sortable()
                    ->description(fn (User $record): ?string => $record->enrollments->isEmpty()
                        ? null
                        : $record->enrollments->pluck('course.title')->filter()->implode(', ')),

                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),

                TextColumn::make('student.phone')
                    ->label('Teléfono')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Alta')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('last_name')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos'),

                TernaryFilter::make('sin_curso')
                    ->label('Inscripciones')
                    ->placeholder('Todos')
                    ->trueLabel('Sin ningún curso')
                    ->falseLabel('Con al menos uno')
                    ->queries(
                        true: fn ($query) => $query->doesntHave('enrollments'),
                        false: fn ($query) => $query->has('enrollments'),
                    ),
            ])
            ->recordActions([
                self::bitacora(),
                self::avisar(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Todavía no hay alumnos')
            ->emptyStateDescription('Se dan de alta desde «Crear nuevo», que crea la cuenta y la ficha juntas.');
    }

    /**
     * La bitácora administrativa del alumno.
     *
     * Interna: no pasa por `email_queue`, el alumno nunca la ve. Existe para
     * pagos confirmados por fuera de la plataforma —transferencia, Mercado
     * Pago— o cualquier gestión resuelta por teléfono que hasta ahora no
     * quedaba escrita en ningún lado. Cuelga del alumno y no de una
     * inscripción puntual: un pago confirmado no es cosa de un curso.
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
            ->modalContent(fn (User $record): View => view('filament.student-notes', [
                'notas' => $record->notes,
            ]))
            ->schema([
                Textarea::make('nota')
                    ->label('Nueva anotación')
                    ->rows(3)
                    ->required(),
            ])
            ->action(function (User $record, array $data): void {
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
            ->action(function (User $record, array $data): void {
                app(NotificationService::class)->administrativeNotice($record, $data['asunto'], $data['mensaje']);

                Notification::make()
                    ->title('Aviso enviado')
                    ->body('Se le encoló el aviso por email.')
                    ->success()
                    ->send();
            });
    }
}
