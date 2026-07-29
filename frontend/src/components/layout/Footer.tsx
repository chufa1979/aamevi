import { NavLink } from 'react-router-dom';
import { FaWhatsapp, FaEnvelope, FaInstagram, FaLinkedinIn } from 'react-icons/fa';
import clsx from 'clsx';
import { footerNav, contact } from './navigation';

const social = [
  { href: contact.whatsapp, label: 'WhatsApp', Icon: FaWhatsapp },
  { href: contact.email, label: 'E-Mail', Icon: FaEnvelope },
];

const networks = [
  { href: contact.instagram, label: 'Instagram', Icon: FaInstagram },
  { href: contact.linkedin, label: 'LinkedIn', Icon: FaLinkedinIn },
];

function IconList({ items }: { items: typeof social }) {
  return (
    <ul className="flex justify-center gap-2 py-5 md:py-0">
      {items.map(({ href, label, Icon }) => (
        <li key={label}>
          <a
            href={href}
            target={href.startsWith('mailto:') ? undefined : '_blank'}
            rel="noreferrer"
            aria-label={label}
            className="block p-1 text-2xl transition-colors hover:text-primary"
          >
            <Icon />
          </a>
        </li>
      ))}
    </ul>
  );
}

export function Footer() {
  return (
    <footer className="bg-ink text-center text-white">
      <div className="container-site flex flex-col py-5 md:flex-row md:items-end md:justify-between md:py-8">
        <IconList items={social} />

        <ul className="md:flex">
          {footerNav.map((item) => (
            <li key={item.to} className="my-2 md:mx-6 md:my-0">
              <NavLink
                to={item.to}
                end={item.to === '/'}
                className={({ isActive }) =>
                  clsx('text-sm uppercase no-underline', isActive && 'font-semibold')
                }
              >
                {item.label}
              </NavLink>
            </li>
          ))}
        </ul>

        <IconList items={networks} />
      </div>

      <p className="container-site pb-6 text-[11px] text-white/60">
        AAMEVi — Asociación Argentina de Medicina del Estilo de Vida ·{' '}
        <a href={contact.site} target="_blank" rel="noreferrer" className="underline">
          www.aamevi.ar
        </a>
      </p>
    </footer>
  );
}
