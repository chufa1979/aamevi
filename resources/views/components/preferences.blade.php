{{--
    Controles de lectura: tamaño de letra y tema.

    Son botones y no un `select` porque el estado tiene que verse de un vistazo:
    `aria-pressed` lo anuncia a los lectores de pantalla y el fondo lo muestra a
    los demás. El JS que los cablea está en `resources/js/preferences.js`.
--}}
<div class="flex items-center gap-4 text-xs">
    <div class="flex items-center gap-1">
        <span id="etiqueta-tamano" class="sr-only">Tamaño de letra</span>

        <div class="flex items-center gap-0.5 rounded-button border border-line p-0.5"
             role="group"
             aria-labelledby="etiqueta-tamano">
            @foreach ([
                'normal' => ['A', 'Tamaño normal'],
                'grande' => ['A', 'Tamaño grande'],
                'mayor' => ['A', 'Tamaño muy grande'],
            ] as $valor => [$letra, $titulo])
                <button type="button"
                        data-tamano="{{ $valor }}"
                        aria-pressed="false"
                        title="{{ $titulo }}"
                        class="cursor-pointer rounded px-1.5 py-0.5 leading-none transition-colors
                               hover:bg-line aria-pressed:bg-primary aria-pressed:text-ink
                               {{ ['normal' => 'text-xs', 'grande' => 'text-sm', 'mayor' => 'text-base'][$valor] }}">
                    {{ $letra }}
                    <span class="sr-only">{{ $titulo }}</span>
                </button>
            @endforeach
        </div>
    </div>

    <div class="flex items-center gap-1">
        <span id="etiqueta-tema" class="sr-only">Tema</span>

        <div class="flex items-center gap-0.5 rounded-button border border-line p-0.5"
             role="group"
             aria-labelledby="etiqueta-tema">
            <button type="button"
                    data-tema="claro"
                    aria-pressed="false"
                    title="Tema claro"
                    class="cursor-pointer rounded px-1.5 py-1 leading-none transition-colors
                           hover:bg-line aria-pressed:bg-primary aria-pressed:text-ink">
                <x-ui.icon name="sol" class="h-4 w-4" />
                <span class="sr-only">Tema claro</span>
            </button>

            <button type="button"
                    data-tema="oscuro"
                    aria-pressed="false"
                    title="Tema oscuro"
                    class="cursor-pointer rounded px-1.5 py-1 leading-none transition-colors
                           hover:bg-line aria-pressed:bg-primary aria-pressed:text-ink">
                <x-ui.icon name="luna" class="h-4 w-4" />
                <span class="sr-only">Tema oscuro</span>
            </button>
        </div>
    </div>
</div>
