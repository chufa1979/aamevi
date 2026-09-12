{{--
    La bitácora de un alumno: interna, el alumno nunca la ve.

    Existe para pagos confirmados por fuera de la plataforma —transferencia,
    Mercado Pago— o cualquier gestión que se resuelva por teléfono o
    WhatsApp y que hasta ahora no quedaba escrita en ningún lado.

    Las clases son `.aamevi-*` de `resources/css/filament/admin.css`: adentro
    de Filament no llegan las utilidades de Tailwind del sitio.
--}}
<div class="aamevi-modal">
    @if ($notas->isEmpty())
        <p class="aamevi-thread-meta">Todavía no hay nada anotado.</p>
    @else
        <ol class="aamevi-answers">
            @foreach ($notas as $nota)
                <li class="aamevi-answer" data-mio="1">
                    <p class="aamevi-thread-meta">
                        {{ $nota->author?->full_name ?? 'Cuenta eliminada' }} ·
                        {{ $nota->created_at->format('d/m/Y H:i') }}
                    </p>

                    <p class="aamevi-thread-body">{{ $nota->body }}</p>
                </li>
            @endforeach
        </ol>
    @endif
</div>
