<?php

namespace App\Filament\Resources\Questions\Schemas;

use App\Models\CourseClass;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use App\Filament\Forms\QuestionOptions;
use Filament\Forms\Components\Textarea;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                /*
                 * Acá la clase se elige, a diferencia de la pantalla de la clase
                 * donde ya viene dada. La etiqueta incluye curso y módulo: con
                 * varios cursos, "Clase 1" solo no distingue nada.
                 */
                Select::make('class_id')
                    ->label('Clase')
                    ->options(fn (): array => CourseClass::with('module.course')
                        ->get()
                        ->sortBy(fn (CourseClass $c): string => $c->module->course->title.$c->module->order_number.$c->order_number)
                        ->mapWithKeys(fn (CourseClass $c): array => [
                            $c->id => "{$c->module->course->title} › {$c->module->title} › {$c->title}",
                        ])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),

                Textarea::make('text')
                    ->label('Pregunta')
                    ->rows(2)
                    ->required(),

                QuestionOptions::repeater(),

                Toggle::make('is_active')
                    ->label('Activa')
                    ->default(true)
                    ->helperText('Las inactivas no entran en el sorteo.'),
            ]);
    }
}
