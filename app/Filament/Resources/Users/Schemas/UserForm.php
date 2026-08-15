<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Utilities\Get;

class UserForm
{
    /**
     * @param  UserRole|null  $fixedRole  fija el rol y esconde el selector, para
     *                                    las pantallas que dan de alta un rol
     *                                    concreto —Alumnos, por ejemplo— en vez
     *                                    de un usuario cualquiera
     */
    public static function configure(Schema $schema, ?UserRole $fixedRole = null): Schema
    {
        return $schema
            ->components([
                Section::make('Datos personales')
                    ->columns(2)
                    ->components([
                        TextInput::make('first_name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(100),

                        TextInput::make('last_name')
                            ->label('Apellido')
                            ->required()
                            ->maxLength(100),

                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        $fixedRole === null
                            ? Select::make('role')
                                ->label('Rol')
                                ->options(UserRole::class)
                                ->required()
                                ->native(false)
                                // live() para que las fichas de abajo aparezcan al
                                // elegir el rol, sin recargar
                                ->live()
                            : Hidden::make('role')->default($fixedRole),
                    ]),

                Section::make('Acceso')
                    ->columns(2)
                    ->components([
                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            // Obligatoria al crear; al editar, en blanco significa
                            // "no cambiarla". Sin el dehydrated, cada edición la
                            // sobreescribiría con null y dejaría al usuario afuera.
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText('Al editar, dejala vacía para conservar la actual.'),

                        Toggle::make('is_active')
                            ->label('Cuenta activa')
                            ->default(true)
                            ->helperText('Una cuenta inactiva no puede iniciar sesión.'),

                        DateTimePicker::make('email_verified_at')
                            ->label('Correo verificado el')
                            ->helperText('Vacío significa que todavía no verificó su correo.'),
                    ]),

                /*
                 * Fichas de extensión. `students` y `teachers` comparten la clave
                 * con `users`, así que Filament las crea sola al guardar la
                 * relación. Sin esto, un usuario con rol Profesor no tendría fila
                 * en `teachers` y no podría figurar como docente de un curso.
                 */
                Section::make('Ficha de alumno')
                    ->relationship('student')
                    ->visible(fn (Get $get): bool => self::roleIs($get, UserRole::Student, $fixedRole))
                    ->columns(2)
                    ->components([
                        TextInput::make('dni')
                            ->label('DNI')
                            ->maxLength(20)
                            ->unique(ignoreRecord: true),

                        DatePicker::make('date_of_birth')
                            ->label('Fecha de nacimiento')
                            ->displayFormat('d/m/Y')
                            ->maxDate(now()),

                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(20),

                        TextInput::make('cell_phone')
                            ->label('Celular')
                            ->tel()
                            ->maxLength(20),

                        TextInput::make('delegation')
                            ->label('Delegación')
                            ->maxLength(100),

                        TextInput::make('sub_delegation')
                            ->label('Subdelegación')
                            ->maxLength(100),
                    ]),

                Section::make('Ficha de profesor')
                    ->relationship('teacher')
                    ->visible(fn (Get $get): bool => self::roleIs($get, UserRole::Teacher, $fixedRole))
                    ->components([
                        TextInput::make('specialization')
                            ->label('Especialización')
                            ->maxLength(255),

                        Textarea::make('bio')
                            ->label('Biografía')
                            ->rows(4),
                    ]),
            ]);
    }

    /** El estado del select puede llegar como enum o como su valor. */
    private static function roleIs(Get $get, UserRole $role, ?UserRole $fixedRole = null): bool
    {
        if ($fixedRole !== null) {
            return $fixedRole === $role;
        }

        $state = $get('role');

        return $state === $role || $state === $role->value;
    }
}
