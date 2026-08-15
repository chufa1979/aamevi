<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Limpia el HTML que viene del editor antes de mostrarlo.
 *
 * Cinco columnas guardan marcado cargado desde el panel —descripción de curso,
 * de módulo, de clase, de contenido, y el enunciado de cada pregunta—. Blade
 * escapa por defecto, así que mientras no se rendericen no hay riesgo; en el
 * momento en que el aula las muestre con formato, un `{!! !!}` crudo sería un
 * vector de XSS.
 *
 * Se sanitiza al **mostrar** y no al guardar: así quedan cubiertas también las
 * filas que ya están en la base, y endurecer la política más adelante no obliga
 * a reprocesar nada.
 *
 * La lista blanca es la misma que produce la barra del editor
 * (`App\Filament\Forms\RichText`): si alguna vez se le agrega un botón, hay que
 * agregar la etiqueta acá o no se va a ver.
 */
class Html
{
    private static ?HtmlSanitizer $sanitizer = null;

    public static function sanitize(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        return self::sanitizer()->sanitize($html);
    }

    private static function sanitizer(): HtmlSanitizer
    {
        return self::$sanitizer ??= new HtmlSanitizer(
            (new HtmlSanitizerConfig)
                ->allowElement('p')
                ->allowElement('br')
                ->allowElement('strong')
                ->allowElement('b')
                ->allowElement('em')
                ->allowElement('i')
                ->allowElement('u')
                ->allowElement('s')
                ->allowElement('h2')
                ->allowElement('h3')
                ->allowElement('ul')
                ->allowElement('ol')
                ->allowElement('li')
                ->allowElement('blockquote')
                ->allowElement('a', ['href', 'title'])
                // Sólo enlaces navegables: sin esto pasarían `javascript:` y
                // `data:`, que son ejecutables
                ->allowLinkSchemes(['http', 'https', 'mailto'])
                ->forceAttribute('a', 'rel', 'noopener noreferrer')
                ->forceAttribute('a', 'target', '_blank')
                ->dropElement('script')
                ->dropElement('style')
                ->dropElement('iframe')
        );
    }
}
