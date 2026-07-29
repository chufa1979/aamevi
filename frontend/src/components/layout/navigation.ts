export interface NavItem {
  label: string;
  to: string;
  children?: NavItem[];
}

/**
 * Navegación principal de la plataforma. Replica la estructura del sitio madre
 * (www.aamevi.ar): ítems en mayúsculas, con submenú desplegable opcional.
 */
export const mainNav: NavItem[] = [
  { label: 'Inicio', to: '/' },
  {
    label: 'Cursos',
    to: '/cursos',
    children: [
      { label: 'Catálogo', to: '/cursos' },
      { label: 'Mis cursos', to: '/mis-cursos' },
    ],
  },
  { label: 'Mi progreso', to: '/progreso' },
  { label: 'Certificados', to: '/certificados' },
  { label: 'Ayuda', to: '/ayuda' },
];

/** Enlaces del pie, alineados con los del sitio institucional. */
export const footerNav: NavItem[] = [
  { label: 'Inicio', to: '/' },
  { label: 'Cursos', to: '/cursos' },
  { label: 'Mi progreso', to: '/progreso' },
  { label: 'Certificados', to: '/certificados' },
  { label: 'Ayuda', to: '/ayuda' },
];

/** Datos de contacto públicos de AAMEVi. */
export const contact = {
  whatsapp: 'https://wa.me/5491137742116',
  email: 'mailto:info@aamevi.ar',
  instagram: 'https://www.instagram.com/aa.mevi/',
  linkedin: 'https://www.linkedin.com/company/aamevi/',
  site: 'https://www.aamevi.ar/',
};
