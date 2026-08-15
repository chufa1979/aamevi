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
    /**
     * Tres campos son de administración y no de dictado: quién dicta, cuánta
     * gente entra y si el curso se ofrece. El docente los ve —le sirven— pero no
     * los toca: si pudiera cambiar el docente asignado, se sacaría el curso de
     * encima con un clic y nadie quedaría a cargo.
     *
     * Deshabilitados y no ocultos, porque Filament no manda al servidor el valor
     * de un campo deshabilitado: no alcanza con esconderlo en la pantalla.
     */
    private static function esAdmin(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

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
                            ->disabled(fn (): bool => ! self::esAdmin())
                            ->helperText('Solo aparecen usuarios con rol Profesor.'),

                        TextInput::make('max_students')
                            ->label('Cupo')
                            ->numeric()
                            ->minValue(1)
                            ->default(50)
                            ->disabled(fn (): bool => ! self::esAdmin())
                            ->required(),

                        Toggle::make('is_active')
                            ->label('Curso activo')
                            ->default(true)
                            ->disabled(fn (): bool => ! self::esAdmin())
                            ->helperText('Los cursos inactivos no se ofrecen en el catálogo.'),
                    ]),
            ]);
    }
}
