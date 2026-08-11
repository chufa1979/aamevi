<?php

namespace App\Filament\Forms;

use App\Models\Quiz;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

/**
 * Configuración del examen que cierra un módulo.
 *
 * Se declara al dar de alta el módulo: el examen no tiene banco propio, toma un
 * porcentaje del banco combinado de todas sus clases. Con 5 clases de 5
 * preguntas y un 40%, el alumno responde 10 de las 25.
 *
 * Vive en una clase aparte porque el mismo bloque se usa en el formulario del
 * módulo dentro del curso y en la pantalla propia del módulo.
 */
class ModuleExam
{
    public static function section(): Section
    {
        return Section::make('Examen del módulo')
            ->description('Se rinde al terminar el módulo y mezcla preguntas de todas sus clases.')
            ->relationship('quiz')
            ->columns(3)
            ->components([
                TextInput::make('questions_percentage')
                    ->label('% de preguntas')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(100)
                    ->default(40)
                    ->required()
                    ->suffix('%')
                    // Dentro de una sección con relationship(), $record es el
                    // registro relacionado —el Quiz—, no el módulo
                    ->helperText(fn (?Quiz $record): string => self::ayuda($record)),

                TextInput::make('passing_score')
                    ->label('Nota mínima')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(100)
                    ->default(70)
                    ->required()
                    ->suffix('%'),

                TextInput::make('max_attempts')
                    ->label('Intentos')
                    ->numeric()
                    ->minValue(1)
                    ->default(3)
                    ->required(),
            ]);
    }

    /** Traduce el porcentaje a preguntas concretas, con el banco que ya existe. */
    private static function ayuda(?Quiz $quiz): string
    {
        if (! $quiz?->exists) {
            return 'Sobre el total de preguntas cargadas en las clases del módulo.';
        }

        $total = $quiz->poolSize();

        if ($total === 0) {
            return 'Todavía no hay preguntas cargadas en las clases de este módulo.';
        }

        return "Hay {$total} pregunta(s) en las clases de este módulo; se sortearán las que resulten del porcentaje.";
    }
}
