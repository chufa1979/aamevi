import type { ReactNode } from 'react';
import clsx from 'clsx';

interface PageHeroProps {
  title: string;
  /** Imagen de cabecera. Sin ella se usa un degradado con los colores del isotipo. */
  image?: string;
  /** `small` recorta la cabecera a 200px, como `.header.small` del sitio madre. */
  size?: 'small' | 'full';
  children?: ReactNode;
}

/**
 * Cabecera de sección: imagen a sangre, título blanco de peso liviano alineado
 * abajo a la izquierda y borde inferior verde de 6px (patrón `.header` de
 * www.aamevi.ar).
 */
export function PageHero({ title, image, size = 'small', children }: PageHeroProps) {
  return (
    <section className="relative border-b-[6px] border-primary">
      {image ? (
        <img
          src={image}
          alt=""
          className={clsx('block w-full object-cover', size === 'small' ? 'h-[200px]' : 'h-auto')}
        />
      ) : (
        <div
          className={clsx(
            'w-full bg-gradient-to-r from-pillar-blue via-primary to-pillar-green',
            size === 'small' ? 'h-[200px]' : 'h-[320px]'
          )}
        />
      )}

      <div className="container-site-sm absolute bottom-4 left-1/2 w-full -translate-x-1/2">
        <h1 className="font-light text-white [font-size:clamp(2.5rem,10vw,7.5rem)]">{title}</h1>
        {children}
      </div>
    </section>
  );
}
