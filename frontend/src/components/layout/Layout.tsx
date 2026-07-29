import { Outlet } from 'react-router-dom';
import { TopBar } from './TopBar';
import { Header } from './Header';
import { Footer } from './Footer';

/**
 * Estructura general de página del sitio madre: `.wrapper` en columna con el
 * `<main>` creciendo para empujar el pie al fondo de la ventana.
 */
export function Layout() {
  return (
    <div className="flex min-h-screen flex-col">
      <TopBar />
      <Header />
      <main className="grow">
        <Outlet />
      </main>
      <Footer />
    </div>
  );
}
