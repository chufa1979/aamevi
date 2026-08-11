<?php

namespace App\Filament\Resources\CourseClasses\RelationManagers;

use App\Models\Question;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = 'Preguntas';

    protected static ?string $modelLabel = 'pregunta';

    protected static ?string $pluralModelLabel = 'preguntas';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->components([
                Textarea::make('text')
                    ->label('Pregunta')
                    ->rows(2)
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
            ->emptyStateDescription('Las preguntas alimentan tanto el quiz de la clase como el examen del módulo.');
    }
}
