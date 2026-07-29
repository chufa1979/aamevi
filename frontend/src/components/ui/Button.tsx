import type { ButtonHTMLAttributes, ReactNode } from 'react';
import { Link } from 'react-router-dom';
import clsx from 'clsx';

type Variant = 'primary' | 'accent' | 'danger' | 'ghost';

const variants: Record<Variant, string> = {
  primary: 'btn',
  accent: 'btn-accent',
  danger: 'btn-danger',
  ghost: 'btn-ghost',
};

interface BaseProps {
  variant?: Variant;
  /** Versión en cursiva usada en las llamadas a la acción del sitio institucional. */
  cta?: boolean;
  className?: string;
  children: ReactNode;
}

export function Button({
  variant = 'primary',
  cta = false,
  className,
  children,
  ...props
}: BaseProps & ButtonHTMLAttributes<HTMLButtonElement>) {
  return (
    <button className={clsx(variants[variant], cta && 'text-lg italic', className)} {...props}>
      {children}
    </button>
  );
}

export function ButtonLink({
  to,
  variant = 'primary',
  cta = false,
  className,
  children,
}: BaseProps & { to: string }) {
  return (
    <Link to={to} className={clsx(variants[variant], cta && 'text-lg italic', className)}>
      {children}
    </Link>
  );
}
