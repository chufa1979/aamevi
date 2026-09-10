/*
 * Panel de login del header. Igual patrón que el menú mobile: data-attributes,
 * sin dependencias nuevas. Se cierra con click afuera o con Escape porque es
 * un formulario con contraseña — dejarlo abierto sobre la página es peor que
 * en un menú de navegación común.
 */
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('[data-login-toggle]');
    const panel = document.querySelector('[data-login-panel]');

    if (!toggle || !panel) {
        return;
    }

    const abrir = (abierto) => {
        toggle.setAttribute('aria-expanded', String(abierto));
        panel.classList.toggle('invisible', !abierto);
        panel.classList.toggle('opacity-0', !abierto);
        panel.classList.toggle('scale-95', !abierto);
    };

    toggle.addEventListener('click', (evento) => {
        evento.stopPropagation();
        abrir(toggle.getAttribute('aria-expanded') !== 'true');
    });

    panel.addEventListener('click', (evento) => evento.stopPropagation());

    document.addEventListener('click', () => abrir(false));

    document.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape') {
            abrir(false);
        }
    });
});
