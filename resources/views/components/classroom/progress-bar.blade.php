@props([
    'value',
    'label' => 'Avance del curso',
    'detail' => null,
])

{{--
    Barra de avance.

    El número va **al lado** y no sólo en el ancho de la barra: el color y el
    largo no le dicen nada a quien no distingue bien los contrastes, y nada en
    absoluto a un lector de pantalla. `role="progressbar"` con sus valores es lo
    que hace que se anuncie como «60 por ciento».
--}}
<div {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
    <div class="h-2 grow overflow-hidden rounded-full bg-line"
         role="progressbar"
         aria-valuenow="{{ $value }}"
         aria-valuemin="0"
         aria-valuemax="100"
         aria-label="{{ $label }}">
        <div class="h-full rounded-full bg-primary transition-[width]"
             style="width: {{ $value }}%"></div>
    </div>

    <span class="shrink-0 text-sm font-medium tabular-nums">{{ $value }}%</span>
</div>

@if ($detail)
    <p class="mt-1 text-xs text-subtle">{{ $detail }}</p>
@endif
