@props(['width' => null])

{{--
    El isotipo, en la variante que corresponda al tema.

    El SVG institucional lleva el texto en `#333333`, ilegible sobre fondo
    oscuro; `aamevi-dark.svg` es el mismo con el texto en blanco y los seis
    colores intactos. Se usa el mismo par que el panel de administración.

    Van los dos en el marcado y se muestra uno con CSS —en vez de cambiar el
    `src` con JavaScript— para que el logo correcto esté desde el primer pintado,
    igual que el resto del tema.
--}}
<span {{ $attributes->merge(['class' => 'block']) }}>
    <img src="/images/aamevi.svg"
         alt="AAMEVi"
         @class(['max-w-full', 'hidden-en-oscuro'])
         @if ($width) style="width: {{ $width }}" @endif>

    <img src="/images/aamevi-dark.svg"
         alt="AAMEVi"
         @class(['max-w-full', 'solo-en-oscuro'])
         aria-hidden="true"
         @if ($width) style="width: {{ $width }}" @endif>
</span>
