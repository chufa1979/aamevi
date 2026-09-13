<?php

/*
 * Videos instructivos de la sección Ayuda (`/ayuda`), un array por rol
 * (`App\Enums\UserRole`). Es la fuente única de qué video le corresponde a
 * cada perfil, igual que `config/navigation.php` lo es para el menú — cambia
 * poco y no tiene sentido una tabla para esto.
 *
 * `file` es el nombre del mp4 en `public/videos-ayuda/`. Los videos se sirven
 * como contenido estático versionado, no como upload de usuario: por eso van
 * en `public/`, no en `storage/app/` (gitignorado). El directorio no se llama
 * `public/ayuda/` a propósito: colisiona con la ruta `/ayuda` — el servidor
 * embebido de PHP (y cualquier `try_files` de Nginx) sirve directorios
 * existentes antes de pasarle la request al router de Laravel, así que esa
 * ruta daba 404 en vez de llegar al controlador.
 */

return [
    'admin' => [
        [
            'slug' => 'admin-01-crear-curso',
            'title' => 'Crear un curso desde cero',
            'description' => 'Título, docente, cupo y ficha pública: lo que solo puede hacer el administrador.',
            'file' => 'admin-01-crear-curso.mp4',
        ],
        [
            'slug' => 'admin-02-alta-profesor',
            'title' => 'Dar de alta un profesor',
            'description' => 'Crear una cuenta con rol Profesor desde Sistema › Usuarios.',
            'file' => 'admin-02-alta-profesor.mp4',
        ],
        [
            'slug' => 'admin-03-reasignar-docente',
            'title' => 'Reasignar el docente de un curso',
            'description' => 'Cambiar quién dicta un curso ya existente.',
            'file' => 'admin-03-reasignar-docente.mp4',
        ],
    ],

    'registrar' => [
        [
            'slug' => 'administrativo-01-solicitudes',
            'title' => 'Revisar y resolver solicitudes de inscripción',
            'description' => 'Ver la ficha del alumno, aprobar o rechazar una solicitud.',
            'file' => 'administrativo-01-solicitudes.mp4',
        ],
        [
            'slug' => 'administrativo-02-inscribir-directo',
            'title' => 'Inscribir un alumno directamente',
            'description' => 'Dar de alta una inscripción ya aprobada, sin pasar por una solicitud.',
            'file' => 'administrativo-02-inscribir-directo.mp4',
        ],
        [
            'slug' => 'administrativo-03-bitacora-aviso',
            'title' => 'Alumnos: bitácora y aviso administrativo',
            'description' => 'Dejar una anotación interna o mandarle un aviso por email a un alumno.',
            'file' => 'administrativo-03-bitacora-aviso.mp4',
        ],
        [
            'slug' => 'administrativo-04-dar-de-alta-alumno',
            'title' => 'Dar de alta un alumno nuevo',
            'description' => 'Crear la cuenta y la ficha de un alumno desde el panel.',
            'file' => 'administrativo-04-dar-de-alta-alumno.mp4',
        ],
    ],

    'teacher' => [
        [
            'slug' => 'profesor-01-recorrido-curso',
            'title' => 'Tu curso asignado: recorrido y edición de la ficha',
            'description' => 'Cómo entrar al curso propio y qué se puede editar de su ficha.',
            'file' => 'profesor-01-recorrido-curso.mp4',
        ],
        [
            'slug' => 'profesor-02-contenidos-modulo-clase',
            'title' => 'Contenidos: crear un módulo, una clase y su material',
            'description' => 'Armar el temario del curso paso a paso.',
            'file' => 'profesor-02-contenidos-modulo-clase.mp4',
        ],
        [
            'slug' => 'profesor-03-evaluacion-autoevaluacion',
            'title' => 'Evaluación: configurar la autoevaluación y cargar el banco de preguntas',
            'description' => 'Reglas del quiz de una clase y cómo cargar sus preguntas.',
            'file' => 'profesor-03-evaluacion-autoevaluacion.mp4',
        ],
        [
            'slug' => 'profesor-04-planificacion-cronograma',
            'title' => 'Planificación: ver el cronograma y correr fechas en lote',
            'description' => 'El calendario de clases del curso y cómo correrlo de una vez.',
            'file' => 'profesor-04-planificacion-cronograma.mp4',
        ],
        [
            'slug' => 'profesor-05-calificaciones-intentos',
            'title' => 'Calificaciones: corregir y publicar una entrega, y ver quién quedó trabado',
            'description' => 'Corregir tareas, publicar notas y encontrar a quién se le acabaron los intentos.',
            'file' => 'profesor-05-calificaciones-intentos.mp4',
        ],
        [
            'slug' => 'profesor-06-seguimiento-comunicacion',
            'title' => 'Seguimiento: cómo va cada alumno, y publicar una comunicación',
            'description' => 'El estado de cada alumno en el curso y cómo avisarles algo a todos.',
            'file' => 'profesor-06-seguimiento-comunicacion.mp4',
        ],
        [
            'slug' => 'profesor-07-consultas',
            'title' => 'Consultas: responder una pregunta de mesa de ayuda',
            'description' => 'Responder una consulta de un alumno sobre el curso.',
            'file' => 'profesor-07-consultas.mp4',
        ],
    ],

    'student' => [
        [
            'slug' => 'alumno-01-registro-verificacion',
            'title' => 'Crear una cuenta y verificar el correo',
            'description' => 'Registrarse en AAMEVi y confirmar la cuenta desde el correo.',
            'file' => 'alumno-01-registro-verificacion.mp4',
        ],
        [
            'slug' => 'alumno-02-inscripcion-catalogo',
            'title' => 'Solicitar inscripción a un curso desde el catálogo',
            'description' => 'Elegir un curso abierto y pedir la inscripción.',
            'file' => 'alumno-02-inscripcion-catalogo.mp4',
        ],
        [
            'slug' => 'alumno-03-clase-autoevaluacion',
            'title' => 'Cursar una clase y rendir la autoevaluación',
            'description' => 'Ver el material de una clase y aprobar su autoevaluación.',
            'file' => 'alumno-03-clase-autoevaluacion.mp4',
        ],
        [
            'slug' => 'alumno-04-entregar-tarea',
            'title' => 'Entregar un trabajo práctico',
            'description' => 'Subir el archivo de una tarea desde la clase.',
            'file' => 'alumno-04-entregar-tarea.mp4',
        ],
        [
            'slug' => 'alumno-05-progreso-certificado',
            'title' => 'Ver el progreso y descargar el certificado',
            'description' => 'El avance en cada curso y cómo bajar el certificado de uno terminado.',
            'file' => 'alumno-05-progreso-certificado.mp4',
        ],
        [
            'slug' => 'alumno-06-consultas',
            'title' => 'Hacer una consulta a mesa de ayuda',
            'description' => 'Escribirle al docente del curso una duda puntual.',
            'file' => 'alumno-06-consultas.mp4',
        ],
    ],
];
