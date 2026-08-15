{{--
    Aplica el tema y el tamaño de letra guardados ANTES del primer pintado.

    Va en línea en el `<head>` y no en el bundle de Vite a propósito: cualquier
    script diferido corre después de que el navegador ya dibujó, y el alumno que
    eligió modo oscuro vería un fogonazo blanco en cada carga.

    Es la única excepción a «todo el JS en resources/js». Son diez líneas y no
    puede depender de la red.
--}}
<script>
    (function () {
        try {
            var raiz = document.documentElement;
            var tema = localStorage.getItem('aamevi:tema');
            var tamano = localStorage.getItem('aamevi:tamano');

            // Sin elección explícita se respeta la del sistema operativo
            if (!tema) {
                tema = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'oscuro' : 'claro';
            }

            raiz.dataset.theme = tema === 'oscuro' ? 'dark' : 'light';

            if (tamano && tamano !== 'normal') {
                raiz.dataset.fontSize = tamano;
            }
        } catch (e) {
            // Sin localStorage queda el tema claro, que es el valor por defecto
        }
    })();
</script>
