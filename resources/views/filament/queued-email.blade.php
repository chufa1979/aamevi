{{--
    Un aviso de la cola, tal como salió.

    El cuerpo se guarda ya renderizado, así que lo que se ve acá es literalmente
    lo que recibió el destinatario. Va dentro de un `iframe` con `srcdoc` y no
    incrustado en la página: el correo trae su propio `<html>` con estilos
    pensados para clientes de mail, y soltarlos sueltos dentro del panel
    desarmaría el panel.

    Las clases son `.aamevi-*` de `resources/css/filament/admin.css` y no
    utilidades de Tailwind: el panel sirve su propio CSS y no carga el build del
    sitio, así que acá `grid` o `h-96` no significan nada.
--}}
<div class="aamevi-modal">
    <dl class="aamevi-modal-meta">
        <div>
            <dt>Destinatario</dt>
            <dd>{{ $email->recipient?->email ?? '—' }}</dd>
        </div>
        <div>
            <dt>Tipo</dt>
            <dd>{{ $email->email_type->getLabel() }}</dd>
        </div>
        <div>
            <dt>Estado</dt>
            <dd>{{ $email->status->getLabel() }}</dd>
        </div>
        <div>
            <dt>Enviado</dt>
            <dd>{{ $email->sent_at?->format('d/m/Y H:i') ?? 'Todavía no' }}</dd>
        </div>
    </dl>

    @if (filled($email->last_error))
        <div class="aamevi-modal-error">
            <p><strong>Último error</strong></p>
            <p>{{ $email->last_error }}</p>
        </div>
    @endif

    <iframe
        title="Vista previa del correo"
        class="aamevi-email-preview"
        srcdoc="{{ $email->body }}"
        sandbox
    ></iframe>
</div>
