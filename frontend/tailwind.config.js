/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      // Paleta tomada de www.aamevi.ar (css/global.css + images/aamevi.svg)
      colors: {
        // --green: #00b8b3 — color institucional: borde del header, botones
        primary: {
          50: '#e6f8f7',
          100: '#c0efee',
          200: '#8ae2e0',
          300: '#4dd4d1',
          400: '#1ac5c1',
          500: '#00b8b3',
          600: '#009c98',
          700: '#007c79',
          800: '#005f5c',
          900: '#004442',
          DEFAULT: '#00b8b3',
        },
        // --orange: #f46707 — hover de navegación, CTA
        accent: {
          50: '#fff3e9',
          100: '#ffe0c7',
          200: '#fdc294',
          300: '#fba25f',
          400: '#f88736',
          500: '#f46707',
          600: '#d45606',
          700: '#ab4405',
          800: '#833403',
          900: '#5c2402',
          DEFAULT: '#f46707',
        },
        // Fondo translúcido del submenú desplegable del sitio madre
        'accent-overlay': 'rgba(232, 118, 40, 0.75)',
        ink: '#333333', // texto principal y fondo del footer
        surface: '#ececec', // fondo de página
        panel: '#d3d3d2', // fondo de formularios destacados
        muted: '#c1c1c1', // pasos inactivos, bordes suaves
        danger: '#eb3f3f',
        error: '#cc0000',
        // Colores del isotipo: los 6 pilares de la medicina del estilo de vida.
        // Sirven para diferenciar cursos, módulos o categorías.
        pillar: {
          blue: '#0071b6',
          teal: '#00b8b3',
          green: '#01875f',
          red: '#d04742',
          orange: '#f46707',
          yellow: '#edbc42',
        },
      },
      fontFamily: {
        sans: ['Montserrat', 'system-ui', 'sans-serif'],
      },
      maxWidth: {
        site: '1315px', // .container del sitio madre
        'site-sm': '1070px', // .container.small
      },
      borderRadius: {
        button: '0.5rem',
      },
      fontSize: {
        // Título de sección del sitio madre: 4em mobile / 5.5em desktop (raíz 10px)
        'title-sm': ['2.5rem', { lineHeight: '1.1' }],
        title: ['3.4375rem', { lineHeight: '1.1' }],
      },
    },
  },
  plugins: [],
}
