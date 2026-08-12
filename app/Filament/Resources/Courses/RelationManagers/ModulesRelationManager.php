<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use Filament\Tables\Table;
use App\Models\CourseModule;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use App\Filament\Forms\RichText;
use Filament\Actions\EditAction;
use App\Filament\Forms\ModuleExam;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use App\Filament\Resources\CourseModules\CourseModuleResource;

class ModulesRelationManager extends RelationManager
{
    protected static string $relationship = 'modules';

    protected static ?string $title = 'Módulos';

    protected static ?string $modelLabel = 'módulo';

    protected static ?string $pluralModelLabel = 'módulos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label('Título')
                ->required()
                ->maxLength(255),

            TextInput::make('order_number')
                ->label('Orden')
                ->numeric()
                ->minValue(1)
                ->required()
                // Sugiere la posición siguiente; el unique (course_id,
                // order_number) rechaza los repetidos.
                ->default(fn (): int => $this->getOwnerRecord()->modules()->max('order_number') + 1)
                ->helperText('Define la secuencia dentro del curso.'),

            RichText::make('description')
                ->label('Descripción')
                ->columnSpanFull(),

            ModuleExam::section()->columnSpanFull(),
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
                    ->label('Módulo')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('classes_count')
                    ->label('Clases')
                    ->counts('classes')
                    ->alignCenter(),
            ])
            ->headerActions([
                CreateAction::make()->label('Agregar módulo'),
            ])
            ->recordActions([
                // Las clases se administran en la pantalla del módulo: un
                // relation manager no puede anidar otro.
                Action::make('classes')
                    ->label('Clases')
                    ->icon('heroicon-o-academic-cap')
                    ->url(fn (CourseModule $record): string => CourseModuleResource::getUrl('edit', ['record' => $record])),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Este curso todavía no tiene módulos');
    }
}
