# Sistema de Diseño — AAMEVi

Describe el sistema visual de la plataforma, derivado del sitio institucional
**https://www.aamevi.ar**.

**Fuentes de verdad** (extraídas del sitio madre):

- `https://www.aamevi.ar/css/global.css` — variables, tipografía, layout y componentes
- `https://www.aamevi.ar/images/aamevi.svg` — colores del isotipo

> Si el sitio madre cambia su identidad, estos dos archivos son el punto de partida para
> actualizar los tokens.

Todo lo que sigue vive en **`resources/css/app.css`**. Tailwind 4 se configura en CSS,
no en un `tailwind.config.js`: **agregar un token de diseño es agregar una variable en el
bloque `@theme`**.

---

## 1. Paleta

### Colores de marca — no cambian nunca

| Token | Valor | Origen en el sitio madre | Uso |
|---|---|---|---|
| `primary` (50–900 / DEFAULT) | `#00b8b3` | `--green` | Color institucional: borde del header, botones, foco |
| `accent` (50–900 / DEFAULT) | `#f46707` | `--orange` | Hover de navegación, llamadas a la acción |
| `accent-overlay` | `rgba(232,118,40,0.75)` | fondo del submenú desplegable | Panel del submenú en desktop |
| `ink` | `#333333` | `body { color }` y `#footer` | Texto sobre fondos claros, fondo del pie |
| `surface` | `#ececec` | `body { background-color }` | Fondo de página del sitio madre |
| `panel` | `#d3d3d2` | `.donaciones .form` | Fondo de formularios destacados |
| `muted` | `#c1c1c1` | pasos inactivos | Bordes suaves, estados inactivos |
| `danger` | `#eb3f3f` | `.button.danger` | Acciones destructivas |
| `error` | `#cc0000` | `#alert.error` | Mensajes de error |

`primary` y `accent` se generaron como rampas completas (50–900) a partir del color
institucional, para tener estados de hover y fondos suaves sin salirse de la marca.

### Tokens semánticos — son los que se usan en las pantallas

Los de arriba son la identidad; estos dicen **qué papel cumple** un color. Son los que se
dan vuelta en modo oscuro: una vista escrita con `bg-canvas` en vez de `bg-surface`
funciona en los dos temas sin escribir una sola variante.

| Token | Claro | Oscuro | Para qué |
|---|---|---|---|
| `canvas` / `canvas-fg` | `#ececec` / `#333333` | `#14181c` / `#e8eaed` | Fondo de página y su texto |
| `card` / `card-fg` | `#ffffff` / `#333333` | `#1e2429` / `#e8eaed` | Tarjetas, paneles, formularios |
| `line` | `#d3d3d2` | `#333b42` | Bordes y separadores |
| `subtle` | `#5b5b5b` | `#a8b0b8` | Texto secundario, ayudas |
| `brand-text` | `#007c79` | `#4dd4d1` | El teal **como texto** |
| `accent-text` | `#ab4405` | `#fba25f` | El naranja **como texto** |

**Por qué existen `brand-text` y `accent-text`:** el teal y el naranja plenos dan 2,47:1 y
2,62:1 sobre el fondo de página. Como texto no pasan ni de cerca. Estas variantes dan
5,05:1 y 4,99:1. El color pleno sigue siendo el de marca en lo decorativo —bordes, fondos,
el borde de 6px del header—, donde el contraste no se le exige.

### Colores del isotipo — `pillar.*`

El logo usa seis colores que representan los pilares de la medicina del estilo de vida.
Están disponibles para diferenciar cursos, módulos o categorías:

| Token | Valor |
|---|---|
| `pillar-blue` | `#0071b6` |
| `pillar-teal` | `#00b8b3` |
| `pillar-green` | `#01875f` |
| `pillar-red` | `#d04742` |
| `pillar-orange` | `#f46707` |
| `pillar-yellow` | `#edbc42` |

---

## 2. Tipografía

**Montserrat**, importada desde Google Fonts al principio de `resources/css/app.css`.

Pesos disponibles: `300, 400, 500, 600, 700` + itálicas `400, 500, 700`.
El sitio madre solo declara `100, 400, 700`, pero su CSS usa además `300`, `500` y `600`
(que el navegador sintetiza). Acá se importan explícitamente para evitar esa síntesis.

Está declarada como `--font-sans`, así que es la fuente por defecto de todo el documento;
no hace falta aplicar `font-sans` a mano.

### ⚠️ Sobre el sistema de tamaños

El sitio madre usa `body { font-size: 62.5% }` y dimensiona **todo en `em`** (`1.6em` = 16px).
**Este proyecto NO replica esa cascada**: mantiene la base de 16px de Tailwind y traduce los
tamaños a su escala. La cascada en `em` rompe las utilidades de Tailwind.

**No portar valores `em` de `global.css` de forma literal.** Equivalencias frecuentes:

| Sitio madre | Equivalente acá |
|---|---|
| `1.2em` | `text-xs` (12px) |
| `1.4em` | `text-sm` (14px) |
| `1.6em` | `text-base` (16px) |
| `2em` | `text-xl` (20px) |
| `4em` / `5.5em` (títulos) | `text-title-sm` / `text-title` |

---

## 3. Layout

| Elemento | Valor | Origen |
|---|---|---|
| `.container-site` | máx. 1315px, centrado, `px-5` | `.container` |
| `.container-site-sm` | máx. 1070px | `.container.small` |
| Ritmo vertical de sección | `py-8` / `md:py-16` | `.section` |
| Estructura de página | columna flex, `main` con `grow` | `.wrapper` |

Breakpoint de navegación: **`lg` (1024px)**. Por debajo, el menú colapsa detrás del botón
hamburguesa con transición de `max-height`, igual que el original.

---

## 4. Elementos característicos

Son los rasgos que hacen que la plataforma se lea como parte del sitio institucional.
**Conservarlos al agregar pantallas nuevas:**

1. **Borde inferior verde de 6px** en el header y en las cabeceras de página (`border-b-[6px] border-primary`).
2. **Navegación en mayúsculas** con hover naranja.
3. **Submenú desplegable** en panel naranja translúcido, texto blanco en itálica y negrita, con separadores blancos.
4. **Buscador asimétrico**: solo bordes inferior y derecho en verde, radio `0 0 15px 0` (`rounded-br-[15px]`).
5. **Cabecera de página**: imagen a sangre con título blanco de peso liviano (`font-light`) alineado abajo, escalado con `clamp()`.
6. **Pie oscuro** `#333333` con menú en mayúsculas e íconos de contacto y redes.
7. **Fotos de personas circulares** (`rounded-full`), como en las grillas de médicos y staff.
8. **Botones** con fondo verde, texto oscuro heredado y radio `0.5rem`; la variante de CTA va en itálica.

---

## 5. Accesibilidad

No es un agregado al final: cambió decisiones de color y de marcado.

**Contraste medido, no estimado.** Los colores de los botones salieron de calcular la
relación, no de mirarlos. El teal pleno con texto oscuro da 5,12:1 y pasa AA; el hover
**aclara** en lugar de oscurecer, porque bajar a `primary-600` con texto oscuro caía a
3,74:1. El naranja y el rojo plenos con texto blanco no llegan (3,09:1 y 3,95:1), así que
los botones usan el escalón 700: 5,90:1 y 6,47:1.

**Preferencias de lectura** (`<x-preferences>` en la barra superior):

| Preferencia | Cómo funciona |
|---|---|
| Tema claro / oscuro | `data-theme` en `<html>`; sin elección explícita manda `prefers-color-scheme` |
| Tamaño de letra: normal / grande / mayor | `data-font-size` mueve el `font-size` de la raíz a 112,5% o 125% |

Todo el sitio está en `rem`, así que mover la raíz escala tipografía, espaciados y
componentes **juntos**, sin excepciones ni ajustes por pantalla.

El parpadeo se evita con un script en línea en el `<head>`
(`resources/views/partials/preferences-head.blade.php`) que aplica los atributos **antes
del primer pintado**. `resources/js/preferences.js` se carga con `@vite`, o sea después, y
sólo maneja los controles. Las preferencias se guardan en `localStorage`; si está
bloqueado, valen para esa pantalla y se pierden al recargar — preferible a romper la página.

**Lo demás:** `:focus-visible` restaurado con anillo institucional (el sitio madre lo
anula), enlace `.skip-link` para saltar la navegación, `prefers-reduced-motion` respetado,
y `.solo-en-oscuro` / `.hidden-en-oscuro` para las piezas que existen en dos versiones
—como el isotipo— resueltas en CSS y no en JavaScript, para que la correcta esté desde el
primer pintado.

---

## 6. Clases utilitarias

Declaradas en `@layer components` de `resources/css/app.css`. Usarlas en lugar de repetir
la composición de utilidades:

| Clase | Para qué |
|---|---|
| `.container-site` / `.container-site-sm` | Contenedores ancho / angosto |
| `.btn` | Botón institucional (verde) |
| `.btn-accent` / `.btn-danger` / `.btn-ghost` | Variantes |
| `.btn-cta` | Variante en itálica para llamadas a la acción |
| `.field` / `.field-label` | Campos de formulario |
| `.section-title` / `.section-subtitle` | Títulos de sección |
| `.nav-link` | Enlace de navegación (mayúsculas + hover naranja) |
| `.card` | Tarjeta del aula: fondo, borde y texto por tokens semánticos |
| `.prose-aamevi` | Texto enriquecido que viene del editor del panel |
| `.skip-link` | Salto a contenido, visible sólo con foco |

`.prose-aamevi` existe porque Tailwind deja en cero los márgenes de párrafos y listas: sin
él, el texto cargado desde el panel se ve como un bloque corrido.

---

## 7. Componentes

```
resources/views/components/
├── brand-logo.blade.php     # Isotipo, con su variante para modo oscuro
├── top-bar.blade.php        # Barra de cortesía: preferencias, quién sos, salir
├── header.blade.php         # Logo, navegación, submenú, buscador
├── footer.blade.php         # Pie oscuro con menú, contacto y redes
├── footer-icons.blade.php
├── page-hero.blade.php      # Cabecera de página (imagen + título + borde verde)
├── section.blade.php        # Bloque de contenido con título y contenedor
├── button.blade.php         # Botón y enlace-botón, con variantes
├── preferences.blade.php    # Tema y tamaño de letra
├── rich-text.blade.php      # Único lugar del proyecto con `{!! !!}`
├── ui/icon.blade.php        # Íconos SVG en línea
└── classroom/               # El aula
    ├── nav.blade.php
    ├── progress-bar.blade.php
    ├── state-badge.blade.php   # Aprobada, en curso, bloqueada, no habilitada
    ├── content-block.blade.php # Video, PDF, texto o consigna
    └── task-panel.blade.php    # Entrega, estado, nota y devolución
```

Los layouts están en `resources/views/layouts/`.

**`<x-rich-text>` es el único componente que imprime HTML sin escapar**, y lo sanea con
`App\Support\Html::sanitize()` antes de hacerlo. Si aparece un `{!! !!}` suelto en una
vista, es un bug.

**El ícono se llama `<x-ui.icon>` y no `<x-icon>`**: `blade-icons`, que viene con Filament,
registra un componente global con ese nombre que le gana al del proyecto.

### Navegación

`config/navigation.php` es la **fuente única** del menú. Se armó con las secciones de la
plataforma (Cursos, Mi progreso, Certificados, Ayuda) y **no** con las del sitio público
(Certificación, Membresía, Donaciones), porque esto es el campus, no la web institucional.
También contiene los datos de contacto públicos (WhatsApp, email, Instagram, LinkedIn).

---

## 8. El panel de administración es otra cosa

Filament trae su propio sistema visual y **no carga el bundle de Vite del sitio**. De ahí
dos consecuencias que ya costaron una pantalla rota:

- Una vista Blade dentro del panel **no puede usar las utilidades de Tailwind del sitio**.
  Los estilos propios del panel van en `resources/css/filament/admin.css`.
- Los assets de Filament se sirven desde `public/css|js|fonts/filament`, fuera del build.
  No están versionados: los republica `php artisan filament:assets`, que ya está en
  `deploy.sh`.

Del sistema de diseño, el panel toma el color institucional como `primary` y el isotipo
—con su variante clara para modo oscuro, porque el original lleva el texto en `#333333` y
sobre fondo oscuro no se lee.

---

## 9. Recursos de marca

| Archivo | Origen |
|---|---|
| `public/images/aamevi.svg` | `https://www.aamevi.ar/images/aamevi.svg` |
| `public/images/aamevi-dark.svg` | Variante propia: texto en blanco, los seis colores intactos |
| `public/favicon.png` | `https://www.aamevi.ar/images/favicon.png` |

---

## 10. Pendientes

- Imágenes de cabecera propias por sección. Hoy `<x-page-hero>` cae a un degradado con los
  colores del isotipo cuando no recibe imagen.
- Pantallas de certificados: es la última sección del aula que sigue sirviendo el marcador.
- El sitio madre incluye un cursor personalizado y un carrusel (slick) en la home. Se
  omitieron a propósito: aportan poco en una plataforma de estudio.
