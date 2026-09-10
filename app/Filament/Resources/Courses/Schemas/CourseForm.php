<?php

namespace App\Filament\Resources\Courses\Schemas;

use App\Models\Teacher;
use Filament\Schemas\Schema;
use App\Enums\CourseModality;
use App\Filament\Forms\RichText;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;

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

                        DatePicker::make('start_date')
                            ->label('Fecha de inicio')
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        DatePicker::make('end_date')
                            ->label('Fecha de fin')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->afterOrEqual('start_date'),

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

                /*
                 * Lo que la ficha pública muestra además del temario. Aparte de
                 * la sección general porque no hace falta completarlo para que
                 * el curso funcione puertas adentro (inscripción, clases,
                 * evaluaciones) — es lo que ve alguien que todavía no se anotó.
                 */
                Section::make('Ficha pública')
                    ->description('Se muestra en /curso/{id}, la página que ve cualquiera antes de anotarse.')
                    ->columns(2)
                    ->components([
                        Select::make('modality')
                            ->label('Modalidad')
                            ->options(CourseModality::class)
                            ->required()
                            ->default(CourseModality::Online),

                        TextInput::make('location')
                            ->label('Lugar')
                            ->maxLength(255)
                            ->placeholder('Ej: Asociación Médica Argentina - Campus Virtual AMA'),

                        TextInput::make('specialties')
                            ->label('Especialidad/es')
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->placeholder('Ej: Medicina General, Medicina del Estilo de Vida'),

                        TextInput::make('schedule_days')
                            ->label('Días')
                            ->maxLength(255)
                            ->placeholder('Ej: Clases asincrónicas: disponible todos los días / Sincrónicas: jueves'),

                        TextInput::make('schedule_time')
                            ->label('Horario')
                            ->maxLength(255)
                            ->placeholder('Ej: Asincrónicas on-demand 24 hs / Sincrónicas: 19:30 hs'),

                        Textarea::make('investment_info')
                            ->label('Inversión')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Ej: 5 cuotas fijas mensuales sin interés de $190.400 c/u...'),

                        Textarea::make('certification_info')
                            ->label('Certificación')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('A quién certifica el curso y bajo qué validación.'),

                        Textarea::make('teaching_staff')
                            ->label('Cuerpo docente')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Un nombre por línea. El docente a cargo ya se muestra aparte, como Director del curso.')
                            ->placeholder("Dra. Ana Pérez — Nutrición\nDr. Juan Gómez — Actividad física"),

                        Textarea::make('objectives')
                            ->label('Objetivos del curso')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Un objetivo por línea.')
                            ->placeholder("Comprender los pilares de la medicina del estilo de vida\nAplicar estrategias de cambio de comportamiento"),

                        Textarea::make('enrollment_requirements')
                            ->label('Requisitos de inscripción')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Un requisito por línea. El contacto para informes ya sale del pie del sitio.')
                            ->placeholder("Título profesional\nFotocopia de DNI"),
                    ]),
            ]);
    }
}
