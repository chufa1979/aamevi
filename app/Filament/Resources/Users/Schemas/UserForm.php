<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DateTimePicker;

class UserForm
{
    public static function configure(Schema $schema): Schema
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

                        Select::make('role')
                            ->label('Rol')
                            ->options(UserRole::class)
                            ->required()
                            ->native(false),
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
            ]);
    }
}
