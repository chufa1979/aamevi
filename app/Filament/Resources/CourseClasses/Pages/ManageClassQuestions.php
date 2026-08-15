<?php

namespace App\Filament\Resources\CourseClasses\Pages;

use App\Models\Question;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use App\Filament\Forms\RichText;
use Filament\Actions\EditAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Toggle;
use App\Filament\Forms\QuestionOptions;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRelatedRecords;
use App\Filament\Resources\CourseClasses\CourseClassResource;

/** Banco de preguntas de la clase. Alimenta su autoevaluación y el examen del módulo. */
class ManageClassQuestions extends ManageRelatedRecords
{
    protected static string $resource = CourseClassResource::class;

    protected static string $relationship = 'questions';

    protected static ?string $navigationLabel = 'Banco de preguntas';

    protected static ?string $title = 'Banco de preguntas de la clase';

    protected static ?string $breadcrumb = 'Preguntas';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->components([
                RichText::make('text')
                    ->label('Pregunta')
                    ->required()
                    ->columnSpan(3),

                TextInput::make('order_number')
                    ->label('Orden')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->default(fn (): int => $this->getOwnerRecord()->questions()->max('order_number') + 1),

                QuestionOptions::repeater(),

                Toggle::make('is_active')
                    ->label('Activa')
                    ->default(true)
                    ->helperText('Las inactivas no entran en el sorteo.')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('text')
            // Los títulos de los modales salen de acá: esta clase de página no
            // lee las propiedades estáticas $modelLabel del recurso.
            ->modelLabel('pregunta')
            ->pluralModelLabel('preguntas')
            ->defaultSort('order_number')
            ->columns([
                TextColumn::make('order_number')
                    ->label('#')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('text')
                    ->label('Pregunta')
                    ->searchable()
                    ->wrap()
                    // En el listado interesa el texto, no el marcado: sin esto
                    // la celda mostraría las etiquetas del editor
                    ->formatStateUsing(fn (?string $state): string => strip_tags((string) $state))
                    ->limit(120),

                TextColumn::make('options_count')
                    ->label('Opciones')
                    ->counts('options')
                    ->alignCenter(),

                TextColumn::make('correcta')
                    ->label('Respuesta correcta')
                    ->state(fn (Question $record): string => $record->correctOption()?->option_text ?? '— sin definir —')
                    ->wrap()
                    ->limit(60),

                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()->label('Agregar pregunta')->modalWidth('3xl'),
            ])
            ->recordActions([
                EditAction::make()->modalWidth('3xl'),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Esta clase todavía no tiene preguntas')
            ->emptyStateDescription('Las preguntas alimentan tanto la autoevaluación de la clase como el examen del módulo.');
    }
}
