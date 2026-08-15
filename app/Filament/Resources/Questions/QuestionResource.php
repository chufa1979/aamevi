<?php

namespace App\Filament\Resources\Questions;

use App\Models\Question;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use App\Filament\Concerns\ScopedToOwnCourses;
use App\Filament\Resources\Questions\Pages\EditQuestion;
use App\Filament\Resources\Questions\Pages\ListQuestions;
use App\Filament\Resources\Questions\Pages\CreateQuestion;
use App\Filament\Resources\Questions\Schemas\QuestionForm;
use App\Filament\Resources\Questions\Tables\QuestionsTable;

class QuestionResource extends Resource
{
    use ScopedToOwnCourses;

    protected static ?string $model = Question::class;

    // Sin icono propio: lo lleva el grupo, ver AdminPanelProvider
    protected static ?string $navigationLabel = 'Banco de preguntas';

    protected static ?string $modelLabel = 'pregunta';

    protected static ?string $pluralModelLabel = 'preguntas';

    protected static string|\UnitEnum|null $navigationGroup = 'Evaluación';

    protected static ?int $navigationSort = 1;

    /**
     * Atajo para cargar preguntas de corrido. Las mismas preguntas se pueden
     * administrar desde la pantalla de cada clase, pero llegar ahí son cuatro
     * niveles de navegación: para cargar el banco de un módulo entero, esta
     * pantalla evita bajar el árbol una vez por pregunta.
     */
    public static function getNavigationBadge(): ?string
    {
        // Por la consulta del recurso y no por el modelo: el docente cuenta las
        // preguntas de sus cursos, no las de la plataforma entera
        return (string) static::getEloquentQuery()->count();
    }

    public static function form(Schema $schema): Schema
    {
        return QuestionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuestionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuestions::route('/'),
            'create' => CreateQuestion::route('/create'),
            'edit' => EditQuestion::route('/{record}/edit'),
        ];
    }
}
