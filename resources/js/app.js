/*
 * Menú hamburguesa del header. Es el único estado de interfaz que necesita JS:
 * el submenú desplegable se resuelve con `group-hover` en CSS y el buscador es
 * un form GET normal.
 */
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('[data-menu-toggle]');
    const menu = document.querySelector('[data-menu]');

    if (!toggle || !menu) {
        return;
    }

    toggle.addEventListener('click', () => {
        const open = toggle.getAttribute('aria-expanded') === 'true';

        toggle.setAttribute('aria-expanded', String(!open));
        toggle.setAttribute('aria-label', open ? 'Abrir menú' : 'Cerrar menú');
        menu.classList.toggle('max-h-0', open);
        menu.classList.toggle('max-h-[27rem]', !open);

        toggle.querySelector('[data-icon-open]')?.classList.toggle('hidden', !open);
        toggle.querySelector('[data-icon-close]')?.classList.toggle('hidden', open);
    });
});
