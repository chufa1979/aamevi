/*
 * Preferencias de lectura: tema y tamaño de letra.
 *
 * Este archivo se carga con `@vite`, o sea después del primer pintado. Lo que
 * evita el parpadeo es el script en línea del `<head>` (ver
 * `resources/views/partials/preferences-head.blade.php`), que aplica los
 * atributos antes de que el navegador dibuje nada. Acá sólo viven los
 * controles.
 */

const TEMAS = ['claro', 'oscuro'];
const TAMANOS = ['normal', 'grande', 'mayor'];

const guardar = (clave, valor) => {
    try {
        localStorage.setItem(clave, valor);
    } catch {
        // Modo privado o almacenamiento lleno: la preferencia vale para esta
        // pantalla y se pierde al recargar. Preferible a romper la página.
    }
};

const aplicarTema = (tema) => {
    document.documentElement.dataset.theme = tema === 'oscuro' ? 'dark' : 'light';
    guardar('aamevi:tema', tema);
    sincronizar('[data-tema]', 'tema', tema);
};

const aplicarTamano = (tamano) => {
    if (tamano === 'normal') {
        delete document.documentElement.dataset.fontSize;
    } else {
        document.documentElement.dataset.fontSize = tamano;
    }

    guardar('aamevi:tamano', tamano);
    sincronizar('[data-tamano]', 'tamano', tamano);
};

/** Deja `aria-pressed` en el botón elegido y lo saca de los demás. */
const sincronizar = (selector, atributo, elegido) => {
    document.querySelectorAll(selector).forEach((boton) => {
        boton.setAttribute('aria-pressed', String(boton.dataset[atributo] === elegido));
    });
};

const temaActual = () =>
    document.documentElement.dataset.theme === 'dark' ? 'oscuro' : 'claro';

const tamanoActual = () => document.documentElement.dataset.fontSize ?? 'normal';

document.addEventListener('DOMContentLoaded', () => {
    // Refleja en los controles lo que el script del <head> ya aplicó
    sincronizar('[data-tema]', 'tema', temaActual());
    sincronizar('[data-tamano]', 'tamano', tamanoActual());

    document.querySelectorAll('[data-tema]').forEach((boton) => {
        boton.addEventListener('click', () => aplicarTema(boton.dataset.tema));
    });

    document.querySelectorAll('[data-tamano]').forEach((boton) => {
        boton.addEventListener('click', () => aplicarTamano(boton.dataset.tamano));
    });

    // Atajo: alternar tema sin buscar el botón
    document.querySelectorAll('[data-tema-alternar]').forEach((boton) => {
        boton.addEventListener('click', () => {
            aplicarTema(temaActual() === 'oscuro' ? 'claro' : 'oscuro');
        });
    });

    // Ciclar el tamaño: normal → grande → mayor → normal
    document.querySelectorAll('[data-tamano-ciclar]').forEach((boton) => {
        boton.addEventListener('click', () => {
            const siguiente = TAMANOS[(TAMANOS.indexOf(tamanoActual()) + 1) % TAMANOS.length];
            aplicarTamano(siguiente);
        });
    });
});

export { TEMAS, TAMANOS };
