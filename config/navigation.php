<?php

/*
 * Fuente única de la navegación de la plataforma. Replica la estructura del
 * sitio madre (www.aamevi.ar) pero con las secciones del campus (Cursos, Mi
 * progreso, Certificados, Ayuda), no con las de la web institucional
 * (Certificación, Membresía, Donaciones).
 *
 * `match` es el patrón que se pasa a Request::is() para marcar el ítem activo.
 */

return [
    'main' => [
        ['label' => 'Inicio', 'href' => '/', 'match' => '/'],
        [
            'label' => 'Cursos',
            'href' => '/cursos',
            'match' => 'cursos*',
            'children' => [
                ['label' => 'Catálogo', 'href' => '/cursos', 'match' => 'cursos'],
                ['label' => 'Mis cursos', 'href' => '/mis-cursos', 'match' => 'mis-cursos*'],
            ],
        ],
        ['label' => 'Mi progreso', 'href' => '/progreso', 'match' => 'progreso*'],
        ['label' => 'Certificados', 'href' => '/certificados', 'match' => 'certificados*'],
        ['label' => 'Ayuda', 'href' => '/ayuda', 'match' => 'ayuda*'],
    ],

    'footer' => [
        ['label' => 'Inicio', 'href' => '/', 'match' => '/'],
        ['label' => 'Cursos', 'href' => '/cursos', 'match' => 'cursos*'],
        ['label' => 'Mi progreso', 'href' => '/progreso', 'match' => 'progreso*'],
        ['label' => 'Certificados', 'href' => '/certificados', 'match' => 'certificados*'],
        ['label' => 'Ayuda', 'href' => '/ayuda', 'match' => 'ayuda*'],
    ],

    /* Datos de contacto públicos de AAMEVi. */
    'contact' => [
        'whatsapp' => 'https://wa.me/5491137742116',
        'email' => 'mailto:info@aamevi.ar',
        'instagram' => 'https://www.instagram.com/aa.mevi/',
        'linkedin' => 'https://www.linkedin.com/company/aamevi/',
        'site' => 'https://www.aamevi.ar/',
    ],
];
