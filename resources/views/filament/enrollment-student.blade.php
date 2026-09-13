{{--
    Ficha del alumno de una solicitud, de sólo lectura.

    Existe para no tener que salir de Solicitudes a buscar al alumno en
    StudentResource: acá está lo que hace falta para procesar el pedido —DNI,
    contacto, egreso— sin dejar la pantalla.

    Las clases son `.aamevi-*` de `resources/css/filament/admin.css`: adentro
    de Filament no llegan las utilidades de Tailwind del sitio.
--}}
<div class="aamevi-modal">
    <dl class="aamevi-modal-meta">
        <div>
            <dt>Alumno</dt>
            <dd>{{ $student->user?->full_name ?? '—' }}</dd>
        </div>
        <div>
            <dt>Email</dt>
            <dd>{{ $student->user?->email ?? '—' }}</dd>
        </div>
        <div>
            <dt>DNI</dt>
            <dd>{{ $student->dni ?? '—' }}</dd>
        </div>
        <div>
            <dt>Teléfono</dt>
            <dd>{{ $student->phone ?? '—' }}</dd>
        </div>
        <div>
            <dt>País</dt>
            <dd>{{ $student->country ?? '—' }}</dd>
        </div>
        <div>
            <dt>Ciudad</dt>
            <dd>{{ $student->city ?? '—' }}</dd>
        </div>
        <div>
            <dt>Fecha de nacimiento</dt>
            <dd>{{ $student->date_of_birth?->format('d/m/Y') ?? '—' }}</dd>
        </div>
        <div>
            <dt>Fecha de egreso</dt>
            <dd>{{ $student->graduation_date?->format('d/m/Y') ?? '—' }}</dd>
        </div>
        <div>
            <dt>Universidad</dt>
            <dd>{{ $student->university ?? '—' }}</dd>
        </div>
        <div>
            <dt>Profesión</dt>
            <dd>{{ $student->profession ?? '—' }}</dd>
        </div>
        <div>
            <dt>N° de socio</dt>
            <dd>{{ $student->membership_number ?? '—' }}</dd>
        </div>
        <div>
            <dt>Cuenta</dt>
            <dd>{{ $student->user?->is_active ? 'Activa' : 'Desactivada' }}</dd>
        </div>
    </dl>
</div>
