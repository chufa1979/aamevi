import { Link } from 'react-router-dom';

/**
 * Barra superior de cortesía, equivalente al `#top` del sitio madre:
 * texto pequeño alineado a la derecha con el acceso a la cuenta.
 */
export function TopBar() {
  return (
    <div className="container-site flex w-full items-center justify-end pt-5">
      <p className="text-xs">
        ¿Ya tenés cuenta?{' '}
        <Link to="/login" className="ml-1 underline-offset-2 hover:underline">
          Iniciar sesión
        </Link>
      </p>
    </div>
  );
}
