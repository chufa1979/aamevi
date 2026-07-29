import type { ReactNode } from 'react';
import clsx from 'clsx';

interface SectionProps {
  title?: string;
  subtitle?: string;
  /** Usa el contenedor angosto (1070px) en lugar del ancho (1315px). */
  narrow?: boolean;
  className?: string;
  children?: ReactNode;
}

/** Bloque de contenido con el ritmo vertical del sitio institucional. */
export function Section({ title, subtitle, narrow, className, children }: SectionProps) {
  return (
    <section className={clsx('py-8 md:py-16', className)}>
      <div className={narrow ? 'container-site-sm' : 'container-site'}>
        {title && <h2 className="section-title mb-4">{title}</h2>}
        {subtitle && <p className="section-subtitle mb-6 font-light">{subtitle}</p>}
        {children}
      </div>
    </section>
  );
}
