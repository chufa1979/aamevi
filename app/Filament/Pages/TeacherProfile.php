<?php

namespace App\Filament\Pages;

use Illuminate\Support\Arr;
use Filament\Schemas\Schema;
use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;

/**
 * "Mi perfil" del docente: sus propios datos, sin pasar por el
 * administrador. `Filament\Auth\Pages\EditProfile` trae nombre, email y
 * contraseña de fábrica, pero solo sabe de columnas de `User` — hay que
 * enseñarle a leer y guardar `specialization`, `bio` y `signature_path`,
 * que viven en `teachers`.
 *
 * `getNameFormComponent()` de la base pide un campo `name`, que este
 * modelo no tiene (usa `first_name`/`last_name`, ver `UserForm`): por eso
 * `form()` no la llama y arma esos dos campos directo.
 */
class TeacherProfile extends EditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('first_name')
                ->label('Nombre')
                ->required()
                ->maxLength(100)
                ->autofocus(),

            TextInput::make('last_name')
                ->label('Apellido')
                ->required()
                ->maxLength(100),

            $this->getEmailFormComponent(),

            TextInput::make('specialization')
                ->label('Especialización')
                ->maxLength(255),

            Textarea::make('bio')
                ->label('Biografía')
                ->rows(4),

            // Mismo disco y directorio que UserForm — el admin sigue
            // pudiendo cargarla también, desde Sistema › Usuarios.
            FileUpload::make('signature_path')
                ->label('Firma escaneada')
                ->image()
                ->disk('public')
                ->directory('teacher-signatures')
                ->downloadable()
                ->openable()
                ->columnSpanFull()
                ->helperText('Se usa en el certificado que descarga el alumno.'),

            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
            $this->getCurrentPasswordFormComponent(),
        ]);
    }

    /**
     * La base llena el form con `$user->attributesToArray()`, plano: sin
     * esto, specialization/bio/signature_path llegan siempre vacíos
     * aunque el docente ya los haya cargado.
     */
    protected function fillForm(): void
    {
        $user = $this->getUser();
        $data = $user->attributesToArray();

        $data = [
            ...$data,
            ...($user->teacher?->only(['specialization', 'bio', 'signature_path']) ?? []),
        ];

        $data = $this->mutateFormDataBeforeFill($data);

        $this->form->fill($data);
    }

    /**
     * La base hace `$record->update($data)` sobre `User` a secas: un
     * `specialization` ahí es una clave que `fill()` ignora en silencio
     * porque no es columna de `users`. Se separa y se guarda en `teachers`
     * antes de dejar que la base actualice el resto.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $teacherData = Arr::only($data, ['specialization', 'bio', 'signature_path']);
        $data = Arr::except($data, ['specialization', 'bio', 'signature_path']);

        $record->teacher()->update($teacherData);

        return parent::handleRecordUpdate($record, $data);
    }
}
