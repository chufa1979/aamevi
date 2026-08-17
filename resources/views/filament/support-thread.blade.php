{{--
    El hilo de una consulta, dentro del modal del panel.

    Las clases son `.aamevi-*` de `resources/css/filament/admin.css`: adentro de
    Filament no llegan las utilidades de Tailwind del sitio.
--}}
<div class="aamevi-modal">
    <div class="aamevi-attempt-meta">
        <span>{{ $ticket->student?->user?->full_name }}</span>
        <span>{{ $ticket->course?->title }}</span>
        <span>{{ $ticket->status->getLabel() }}</span>
    </div>

    <ol class="aamevi-answers">
        @foreach ($ticket->messages as $mensaje)
            <li class="aamevi-answer" data-mio="{{ $mensaje->isFromStudent() ? '0' : '1' }}">
                <p class="aamevi-thread-meta">
                    {{ $mensaje->author?->full_name ?? 'Cuenta eliminada' }} ·
                    {{ $mensaje->created_at->format('d/m/Y H:i') }}
                </p>

                <p class="aamevi-thread-body">{{ $mensaje->body }}</p>
            </li>
        @endforeach
    </ol>
</div>
