<?php

namespace App\Filament\Resources\Students;

use App\Models\User;
use App\Enums\UserRole;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Concerns\ListAndCreateNavigation;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Students\Pages\EditStudent;
use App\Filament\Resources\Students\Pages\ListStudents;
use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Filament\Resources\Students\Tables\StudentsTable;

/**
 * Los alumnos, con su ficha y sus inscripciones.
 *
 * Trabaja sobre `User` y no sobre `Student` porque la ficha comparte la clave
 * con el usuario: un alumno sin usuario no puede entrar a la plataforma, así
 * que darlo de alta es siempre crear los dos. `UserForm` ya resuelve ese par,
 * y acá se reutiliza con el rol fijo.
 *
 * `UserResource`, en Sistema, sigue existiendo para dar de alta cualquier rol.
 */
class StudentResource extends Resource
{
    use ListAndCreateNavigation;

    protected static ?string $model = User::class;

    protected static ?string $slug = 'alumnos';

    protected static ?string $navigationLabel = 'Alumnos';

    protected static ?string $modelLabel = 'alumno';

    protected static ?string $pluralModelLabel = 'alumnos';

    protected static string|\UnitEnum|null $navigationGroup = 'Alumnos';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'first_name';

    public static function getRecordTitle(?Model $record): ?string
    {
        return $record?->full_name;
    }

    /** @return Builder<User> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', UserRole::Student);
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema, UserRole::Student);
    }

    public static function table(Table $table): Table
    {
        return StudentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStudents::route('/'),
            'create' => CreateStudent::route('/create'),
            'edit' => EditStudent::route('/{record}/edit'),
        ];
    }
}
