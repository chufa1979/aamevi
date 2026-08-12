<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\RichEditor;

/**
 * Editor de texto enriquecido del panel.
 *
 * La barra de herramientas se define en un solo lugar: se usa en la descripción
 * del curso, del módulo, de la clase y en el contenido de clase, y si cada
 * formulario la declarara por su cuenta terminarían divergiendo.
 *
 * Es deliberadamente corta. Estos campos son texto descriptivo, no maquetado:
 * sin tablas, sin imágenes, sin colores.
 */
class RichText
{
    /** @var array<int, string> */
    private const TOOLBAR = [
        'bold', 'italic', 'underline', 'strike',
        'h2', 'h3',
        'bulletList', 'orderedList',
        'link', 'blockquote',
        'undo', 'redo',
    ];

    public static function make(string $name = 'description'): RichEditor
    {
        return RichEditor::make($name)->toolbarButtons(self::TOOLBAR);
    }
}
