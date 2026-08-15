<?php

namespace App\Filament\Resources\Courses\Schemas;

use App\Models\Teacher;
use Filament\Schemas\Schema;
use App\Filament\Forms\RichText;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            // La grilla del formulario es de dos columnas: sin esto la sección
            // ocupa una sola y queda a media pantalla, con el otro medio vacío.
            ->columns(1)
            ->components([
                Section::make()
                    ->columns(2)
                    ->components([
                        TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        RichText::make('description')
                            ->label('Descripción')
                            ->columnSpanFull(),

                        /*
                         * El generador dejaba `relationship('teacher', 'id')`, que
                         * listaba UUIDs. El nombre del docente vive en `users`, un
                         * salto más allá, así que se arma desde el registro.
                         */
                        Select::make('teacher_id')
                            ->label('Docente')
                            ->relationship(
                                name: 'teacher',
                                titleAttribute: 'id',
                                modifyQueryUsing: fn ($query) => $query->with('user'),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Teacher $record): string => $record->user->full_name)
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Solo aparecen usuarios con rol Profesor.'),

                        TextInput::make('max_students')
                            ->label('Cupo')
                            ->numeric()
                            ->minValue(1)
                            ->default(50)
                            ->required(),

                        Toggle::make('is_active')
                            ->label('Curso activo')
                            ->default(true)
                            ->helperText('Los cursos inactivos no se ofrecen en el catálogo.'),
                    ]),
            ]);
    }
}
