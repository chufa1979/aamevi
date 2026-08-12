<?php

namespace App\Filament\Forms;

use Closure;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;

/**
 * Opciones de respuesta de una pregunta.
 *
 * Se usa desde la pantalla de la clase y desde el listado general de preguntas;
 * definida una sola vez para que la regla de "exactamente una correcta" no
 * dependa de por dónde se entró a cargar.
 */
class QuestionOptions
{
    public static function repeater(): Repeater
    {
        return Repeater::make('options')
            ->label('Opciones de respuesta')
            ->relationship()
            ->orderColumn('order_number')
            ->reorderable()
            ->addActionLabel('Agregar opción')
            ->minItems(2)
            ->defaultItems(4)
            ->columnSpanFull()
            ->columns(4)
            /*
             * Una pregunta sin correcta no se puede aprobar nunca, y con dos
             * correctas la calificación es ambigua. El esquema no lo puede
             * expresar sin un trigger, así que se valida acá.
             */
            ->rule(static function (): Closure {
                return static function (string $attribute, mixed $value, Closure $fail): void {
                    $correctas = collect($value)->where('is_correct', true)->count();

                    if ($correctas !== 1) {
                        $fail('Marcá exactamente una opción como correcta.');
                    }
                };
            })
            ->schema([
                TextInput::make('option_text')
                    ->label('Opción')
                    ->required()
                    ->columnSpan(3),

                Toggle::make('is_correct')
                    ->label('Correcta')
                    ->inline(false),
            ]);
    }
}
