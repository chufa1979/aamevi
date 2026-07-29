import { useState } from 'react';
import { NavLink, Link, useNavigate } from 'react-router-dom';
import { FaBars, FaTimes, FaSearch } from 'react-icons/fa';
import clsx from 'clsx';
import { mainNav, type NavItem } from './navigation';

function NavEntry({ item, onNavigate }: { item: NavItem; onNavigate: () => void }) {
  const hasChildren = Boolean(item.children?.length);

  return (
    <li className="group relative lg:mx-6">
      <NavLink
        to={item.to}
        end={item.to === '/'}
        onClick={onNavigate}
        className={({ isActive }) =>
          clsx('nav-link block py-1 text-[15px] lg:text-[13px]', isActive && 'text-accent')
        }
      >
        {item.label}
      </NavLink>

      {hasChildren && (
        <ul
          className={clsx(
            // Desktop: panel naranja translúcido que aparece al pasar el mouse,
            // igual que el submenú de www.aamevi.ar
            'lg:pointer-events-none lg:absolute lg:left-0 lg:top-full lg:z-10 lg:min-w-[9rem]',
            'lg:bg-accent-overlay lg:px-4 lg:pb-4 lg:pt-2 lg:opacity-0',
            'lg:transition-opacity lg:duration-500 lg:group-hover:pointer-events-auto lg:group-hover:opacity-100'
          )}
        >
          {item.children!.map((child) => (
            <li key={child.to} className="lg:border-b-2 lg:border-white">
              <NavLink
                to={child.to}
                onClick={onNavigate}
                className={({ isActive }) =>
                  clsx(
                    'nav-link block py-1 pl-4 text-sm lg:pl-0 lg:text-left lg:text-[11px]',
                    'lg:font-bold lg:italic lg:normal-case lg:text-white lg:hover:text-white',
                    isActive && 'text-accent lg:text-white'
                  )
                }
              >
                {child.label}
              </NavLink>
            </li>
          ))}
        </ul>
      )}
    </li>
  );
}

export function Header() {
  const [menuOpen, setMenuOpen] = useState(false);
  const [query, setQuery] = useState('');
  const navigate = useNavigate();

  const closeMenu = () => setMenuOpen(false);

  const handleSearch = (event: React.FormEvent) => {
    event.preventDefault();
    const term = query.trim();
    if (!term) return;
    navigate(`/buscar?q=${encodeURIComponent(term)}`);
    closeMenu();
  };

  return (
    // El borde inferior verde de 6px es la firma visual del sitio institucional
    <header className="border-b-[6px] border-primary bg-surface pb-5 pt-5 lg:pt-0">
      <div className="container-site flex flex-wrap items-center justify-between lg:items-end">
        <Link to="/" className="w-[200px] xl:w-[250px]" aria-label="AAMEVi — Inicio">
          <img src="/images/aamevi.svg" alt="AAMEVi" className="max-w-full" />
        </Link>

        <button
          type="button"
          onClick={() => setMenuOpen((open) => !open)}
          className="p-2 text-2xl text-ink lg:hidden"
          aria-expanded={menuOpen}
          aria-controls="main-menu"
          aria-label={menuOpen ? 'Cerrar menú' : 'Abrir menú'}
        >
          {menuOpen ? <FaTimes /> : <FaBars />}
        </button>

        <nav
          id="main-menu"
          className={clsx(
            'w-full overflow-hidden transition-[max-height] duration-500 lg:w-auto lg:overflow-visible',
            menuOpen ? 'max-h-[27rem]' : 'max-h-0 lg:max-h-none'
          )}
        >
          <ul className="mt-5 text-center lg:mt-0 lg:flex lg:justify-between">
            {mainNav.map((item) => (
              <NavEntry key={item.to} item={item} onNavigate={closeMenu} />
            ))}
          </ul>
        </nav>

        <form
          onSubmit={handleSearch}
          role="search"
          className="mt-5 flex w-full items-center justify-center lg:mt-0 lg:w-auto"
        >
          <button type="submit" className="px-2 text-ink" aria-label="Buscar">
            <FaSearch />
          </button>
          <input
            type="text"
            name="q"
            value={query}
            onChange={(event) => setQuery(event.target.value)}
            aria-label="Buscar en la plataforma"
            // Radio asimétrico y bordes parciales: detalle propio del sitio madre
            className="w-[110px] rounded-none rounded-br-[15px] border-0 border-b-[1.5px] border-r-[1.5px]
                       border-primary bg-transparent pb-1 text-xs focus:outline-none"
          />
        </form>
      </div>
    </header>
  );
}
