<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Models\Student;
use Filament\Tables\Table;
use App\Models\Announcement;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use App\Filament\Forms\RichText;
use Filament\Actions\EditAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use App\Services\AnnouncementService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Filament\Resources\Courses\CourseResource;
use Filament\Resources\Pages\ManageRelatedRecords;

/**
 * El tablón de comunicaciones del curso.
 *
 * Publicar y avisar son dos cosas. La comunicación queda en el tablón siempre;
 * el correo sale sólo si se marca la casilla al crearla. Un aviso de que se
 * corrió una clase merece correo, una nota de color no — y si todo saliera por
 * mail, el docente terminaría no publicando lo menor.
 *
 * Por eso el aviso además se puede mandar después: se escribe la comunicación,
 * se relee, y recién ahí se decide.
 */
class CourseAnnouncements extends ManageRelatedRecords
{
    protected static string $resource = CourseResource::class;

    protected static string $relationship = 'announcements';

    protected static ?string $navigationLabel = 'Comunicación';

    protected static ?string $title = 'Comunicaciones del curso';

    protected static ?string $breadcrumb = 'Comunicación';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('title')
                ->label('Título')
                ->required()
                ->maxLength(255),

            /*
             * Vacío significa todo el curso. Es el caso frecuente, así que va sin
             * marcar por defecto: obligar a elegir destinatario cada vez sería
             * fricción por el caso raro.
             */
            Select::make('student_id')
                ->label('Destinatario')
                ->placeholder('Todo el curso')
                ->options(fn (): array => Student::query()
                    ->whereIn('id', $this->getOwnerRecord()->enrollments()->select('student_id'))
                    ->with('user')
                    ->get()
                    ->mapWithKeys(fn (Student $s): array => [$s->getKey() => $s->user?->full_name])
                    ->all())
                ->searchable(),

            RichText::make('body')
                ->label('Texto')
                ->required(),

            Toggle::make('avisar')
                ->label('Avisar por email')
                ->helperText('Se le manda a quien corresponda. Si no, queda sólo en el tablón del aula.')
                ->default(false)
                // No es una columna: es lo que se decide al publicar
                ->dehydrated(false)
                ->visibleOn('create'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel('comunicación')
            ->pluralModelLabel('comunicaciones')
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('student.user.full_name')
                    ->label('Destinatario')
                    ->placeholder('Todo el curso'),

                TextColumn::make('published_at')
                    ->label('Publicada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                IconColumn::make('notified_at')
                    ->label('Avisada')
                    ->boolean()
                    ->getStateUsing(fn (Announcement $record): bool => $record->wasNotified()),

                TextColumn::make('author.full_name')
                    ->label('Publicó')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nueva comunicación')
                    ->mutateDataUsing(fn (array $data): array => [
                        ...$data,
                        'published_at' => now(),
                        'author_id' => auth()->id(),
                    ])
                    ->after(function (Announcement $record, array $data): void {
                        if (! ($data['avisar'] ?? false)) {
                            return;
                        }

                        $cuantos = app(AnnouncementService::class)->notify($record);

                        Notification::make()
                            ->title('Comunicación publicada')
                            ->body("Se encolaron {$cuantos} avisos por email.")
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                self::avisar(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Todavía no hay comunicaciones')
            ->emptyStateDescription('El tablón es lo que el alumno ve al entrar al curso.');
    }

    /** Mandar el aviso después, si al publicar se decidió que no. */
    private static function avisar(): Action
    {
        return Action::make('avisar')
            ->label('Avisar por email')
            ->icon('heroicon-o-paper-airplane')
            ->requiresConfirmation()
            ->modalDescription('Se le encola un correo a cada destinatario. Sólo se puede una vez.')
            ->visible(fn (Announcement $record): bool => ! $record->wasNotified())
            ->action(function (Announcement $record): void {
                $cuantos = app(AnnouncementService::class)->notify($record);

                Notification::make()
                    ->title("Se encolaron {$cuantos} avisos")
                    ->success()
                    ->send();
            });
    }
}
