<?php

namespace App\Filament\Resources\CourseModules\RelationManagers;

use Filament\Tables\Table;
use App\Models\CourseClass;
use App\Models\ClassContent;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use App\Enums\ClassContentType;
use App\Filament\Forms\RichText;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Illuminate\Contracts\View\View;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Illuminate\Database\Eloquent\Collection;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Resources\RelationManagers\RelationManager;
use App\Filament\Resources\CourseClasses\CourseClassResource;

class ClassesRelationManager extends RelationManager
{
    protected static string $relationship = 'classes';

    protected static ?string $title = 'Clases';

    protected static ?string $modelLabel = 'clase';

    protected static ?string $pluralModelLabel = 'clases';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('title')
                    ->label('Título')
                    ->required()
                    ->maxLength(255),

                TextInput::make('order_number')
                    ->label('Orden')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->default(fn (): int => $this->getOwnerRecord()->classes()->max('order_number') + 1),

                DateTimePicker::make('activation_date')
                    ->label('Se habilita el')
                    ->seconds(false)
                    ->displayFormat('d/m/Y H:i')
                    ->required()
                    ->default(now())
                    ->helperText('Antes de esta fecha, la clase no es visible para los alumnos.'),

                Toggle::make('is_live_session')
                    ->label('Clase en vivo')
                    ->live()
                    ->helperText('Se dicta por Google Meet en la fecha indicada.'),

                TextInput::make('meet_link')
                    ->label('Enlace de Meet')
                    ->url()
                    ->maxLength(500)
                    ->visible(fn (Get $get): bool => (bool) $get('is_live_session'))
                    ->required(fn (Get $get): bool => (bool) $get('is_live_session')),

                Toggle::make('is_live_recording_available')
                    ->label('Grabación disponible')
                    ->visible(fn (Get $get): bool => (bool) $get('is_live_session')),

                RichText::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),

                /*
                 * El contenido se edita acá adentro y no en otra pantalla: sería
                 * un tercer nivel de navegación (curso → módulo → clase →
                 * contenido) para editar cuatro campos.
                 */
                Repeater::make('contents')
                    ->label('Contenido de la clase')
                    ->relationship()
                    ->orderColumn('order_number')
                    ->reorderable()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                    ->addActionLabel('Agregar contenido')
                    ->defaultItems(0)
                    ->columnSpanFull()
                    ->columns(2)
                    /*
                     * Los campos de abajo se muestran según el tipo, pero se
                     * deshidratan siempre: un componente oculto no llega al
                     * modelo y la columna terminaba en null. Acá se decide qué
                     * campo corresponde a cada tipo y se limpian los demás.
                     */
                    ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => self::normalizarContenido($data))
                    ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => self::normalizarContenido($data))
                    ->schema([
                        Select::make('type')
                            ->label('Tipo')
                            ->options(ClassContentType::class)
                            ->default(ClassContentType::Text)
                            ->required()
                            ->live()
                            ->native(false),

                        TextInput::make('title')
                            ->label('Título')
                            ->maxLength(255),

                        TextInput::make('content_url')
                            ->label('Enlace del video')
                            ->url()
                            ->maxLength(500)
                            ->columnSpanFull()
                            ->dehydrated()
                            // Al salir del campo se actualiza la previsualización
                            ->live(onBlur: true)
                            ->visible(fn (Get $get): bool => self::tipoEs($get, ClassContentType::Video))
                            ->required(fn (Get $get): bool => self::tipoEs($get, ClassContentType::Video))
                            ->helperText('YouTube, Vimeo o el enlace del archivo en Google Cloud Storage.'),

                        Placeholder::make('video_preview')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => self::tipoEs($get, ClassContentType::Video)
                                && filled($get('content_url')))
                            ->content(fn (Get $get): View => view('filament.video-preview', [
                                'url' => $get('content_url'),
                                'embed' => ClassContent::embedUrlFor($get('content_url')),
                            ])),

                        FileUpload::make('content_file')
                            ->label('Archivo PDF')
                            ->disk('public')
                            ->directory('class-content')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(20480)
                            // Con ambos, Filament muestra el archivo guardado con
                            // botones para abrirlo en una pestaña y descargarlo
                            ->downloadable()
                            ->openable()
                            ->columnSpanFull()
                            ->dehydrated()
                            ->visible(fn (Get $get): bool => self::tipoEs($get, ClassContentType::Pdf))
                            ->required(fn (Get $get): bool => self::tipoEs($get, ClassContentType::Pdf))
                            // Mientras no esté Google Cloud Storage, los archivos
                            // van al disco público local.
                            ->helperText('Hasta 20 MB.'),

                        RichText::make('description')
                            ->label(fn (Get $get): string => self::tipoEs($get, ClassContentType::Task)
                                ? 'Consigna'
                                : 'Texto')
                            ->columnSpanFull()
                            ->dehydrated()
                            ->visible(fn (Get $get): bool => self::tipoEs($get, ClassContentType::Text)
                                || self::tipoEs($get, ClassContentType::Task)),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('order_number')
            ->columns([
                TextColumn::make('order_number')
                    ->label('#')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Clase')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('activation_date')
                    ->label('Se habilita')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record): string => $record->isAvailable() ? 'success' : 'warning'),

                TextColumn::make('contents_count')
                    ->label('Contenido')
                    ->counts('contents')
                    ->alignCenter(),

                IconColumn::make('is_live_session')
                    ->label('En vivo')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()->label('Agregar clase')->modalWidth('4xl'),
            ])
            ->recordActions([
                // El banco de preguntas se administra en la pantalla de la
                // clase: un relation manager no puede anidar otro.
                Action::make('questions')
                    ->label('Preguntas')
                    ->icon('heroicon-o-question-mark-circle')
                    ->url(fn (CourseClass $record): string => CourseClassResource::getUrl('edit', ['record' => $record])),

                EditAction::make()->modalWidth('4xl'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::correrFechas(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Este módulo todavía no tiene clases');
    }

    /** El estado del select puede llegar como enum o como su valor. */
    private static function tipoEs(Get $get, ClassContentType $type): bool
    {
        $state = $get('type');

        return $state === $type || $state === $type->value;
    }

    /**
     * Cada tipo de contenido usa un solo campo. Los demás se limpian para que
     * cambiar el tipo no deje colgados los datos del anterior: un contenido que
     * pasó de video a texto no debe conservar el enlace de YouTube.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function normalizarContenido(array $data): array
    {
        $type = $data['type'] instanceof ClassContentType
            ? $data['type']
            : ClassContentType::from($data['type']);

        $data['content_url'] = match ($type) {
            ClassContentType::Video => $data['content_url'] ?? null,
            ClassContentType::Pdf => $data['content_file'] ?? null,
            default => null,
        };

        $data['description'] = in_array($type, [ClassContentType::Text, ClassContentType::Task], true)
            ? ($data['description'] ?? null)
            : null;

        unset($data['content_file']);

        return $data;
    }

    /**
     * Correr el cronograma en bloque. Reprogramar un módulo entero clase por
     * clase es la tarea que más rápido se vuelve insoportable para un docente.
     */
    private static function correrFechas(): BulkAction
    {
        return BulkAction::make('shiftDates')
            ->label('Correr fechas')
            ->icon('heroicon-o-calendar-days')
            ->schema([
                TextInput::make('days')
                    ->label('Días')
                    ->numeric()
                    ->required()
                    ->default(7)
                    ->helperText('Podés usar un número negativo para adelantarlas.'),
            ])
            ->action(function (Collection $records, array $data): void {
                $days = (int) $data['days'];

                foreach ($records as $record) {
                    $record->update([
                        'activation_date' => $record->activation_date->addDays($days),
                    ]);
                }

                Notification::make()
                    ->title(count($records).' clase(s) reprogramada(s)')
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
