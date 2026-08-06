# Sistema de Diseño — Frontend AAMEVi

Este documento describe el sistema visual del frontend de la plataforma, derivado del
sitio institucional **https://www.aamevi.ar**.

**Fuentes de verdad** (extraídas del sitio madre):

- `https://www.aamevi.ar/css/global.css` — variables, tipografía, layout y componentes
- `https://www.aamevi.ar/images/aamevi.svg` — colores del isotipo

> Si el sitio madre cambia su identidad, estos dos archivos son el punto de partida para
> actualizar los tokens.

---

## 1. Paleta

Definida en `frontend/tailwind.config.js` bajo `theme.extend.colors`.

| Token Tailwind | Valor | Origen en el sitio madre | Uso |
|---|---|---|---|
| `primary` (500 / DEFAULT) | `#00b8b3` | `--green` | Color institucional: borde del header, botones, foco |
| `accent` (500 / DEFAULT) | `#f46707` | `--orange` | Hover de navegación, llamadas a la acción |
| `accent-overlay` | `rgba(232,118,40,0.75)` | fondo del submenú desplegable | Panel del submenú en desktop |
| `ink` | `#333333` | `body { color }` y `#footer` | Texto principal y fondo del pie |
| `surface` | `#ececec` | `body { background-color }` | Fondo de página |
| `panel` | `#d3d3d2` | `.donaciones .form` | Fondo de formularios destacados |
| `muted` | `#c1c1c1` | pasos inactivos | Bordes suaves, estados inactivos |
| `danger` | `#eb3f3f` | `.button.danger` | Acciones destructivas |
| `error` | `#cc0000` | `#alert.error` | Mensajes de error |

`primary` y `accent` se generaron como rampas completas (50–900) a partir del color
institucional, para tener estados de hover y fondos suaves sin salirse de la marca.

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

**Montserrat**, importada desde Google Fonts en `frontend/src/index.css`.

Pesos disponibles: `300, 400, 500, 600, 700` + itálicas `400, 500, 700`.
El sitio madre solo declara `100, 400, 700`, pero su CSS usa además `300`, `500` y `600`
(que el navegador sintetiza). Acá se importan explícitamente para evitar esa síntesis.

Está configurada como `fontFamily.sans`, por lo que es la fuente por defecto de todo el
documento; no hace falta aplicar `font-sans` manualmente.

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

## 5. Clases utilitarias

Declaradas en `@layer components` de `frontend/src/index.css`. Usarlas en lugar de repetir
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

**Accesibilidad:** el sitio madre anula los `outline` de foco. Acá se restauraron con un
anillo en color institucional (`:focus-visible` en `@layer base`).

---

## 6. Componentes

```
frontend/src/components/
├── layout/
│   ├── navigation.ts   # Ítems de menú y datos de contacto (fuente única)
│   ├── TopBar.tsx      # Barra superior de cortesía (acceso a cuenta)
│   ├── Header.tsx      # Logo, navegación, submenú, buscador
│   ├── Footer.tsx      # Pie oscuro con menú, contacto y redes
│   └── Layout.tsx      # Estructura general; usa <Outlet /> de React Router
└── ui/
    ├── Button.tsx      # Button y ButtonLink, con variantes
    ├── PageHero.tsx    # Cabecera de página (imagen + título + borde verde)
    └── Section.tsx     # Bloque de contenido con título y contenedor
```

Las rutas se declaran en `frontend/src/App.tsx`, anidadas dentro de `<Layout />`.
`pages/Placeholder.tsx` cubre las secciones aún no implementadas.

### Navegación

`components/layout/navigation.ts` es la **fuente única** del menú. Se armó con las secciones
de la plataforma (Cursos, Mi progreso, Certificados, Ayuda) y **no** con las del sitio
público (Certificación, Membresía, Donaciones), porque esto es el campus, no la web
institucional. También contiene los datos de contacto públicos (WhatsApp, email, Instagram,
LinkedIn).

---

## 7. Recursos de marca

| Archivo | Origen |
|---|---|
| `frontend/public/images/aamevi.svg` | `https://www.aamevi.ar/images/aamevi.svg` |
| `frontend/public/favicon.png` | `https://www.aamevi.ar/images/favicon.png` |

`index.html` y `public/manifest.json` usan `#00b8b3` como `theme_color` y `#ececec` como
`background_color`.

Los íconos de interfaz y redes se toman de **react-icons** (`react-icons/fa`), ya presente
en las dependencias, en lugar de descargar los SVG sueltos del sitio madre.

---

## 8. Estado y pendientes

**Verificado:** `npm run build` (tsc + vite) y `npm run lint` pasan sin errores.
**No verificado:** revisión visual en navegador — ejecutar `npm run dev` y contrastar contra
el sitio madre.

Pendiente:

- Imágenes de cabecera propias por sección. Hoy `PageHero` cae a un degradado con los
  colores del isotipo cuando no recibe `image`.
- Íconos PWA (`icon-192.png`, `icon-512.png`) referenciados en `manifest.json` pero
  inexistentes.
- Componentes de dominio pendientes de las fases siguientes: tarjeta de curso, acordeón de
  módulos/clases, quiz, barra de progreso, tabla de calificaciones.
- El sitio madre incluye un cursor personalizado y un carrusel (slick) en la home. Se
  omitieron a propósito: aportan poco en una plataforma de estudio.
