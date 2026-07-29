import { Link } from 'react-router-dom';
import { PageHero } from '@/components/ui/PageHero';
import { Section } from '@/components/ui/Section';
import { ButtonLink } from '@/components/ui/Button';

const accesos = [
  {
    to: '/cursos',
    title: 'Cursos',
    text: 'Recorré el catálogo, inscribite y seguí las clases módulo a módulo.',
    color: 'bg-pillar-blue',
  },
  {
    to: '/progreso',
    title: 'Mi progreso',
    text: 'Mirá qué clases completaste, tus evaluaciones y las tareas pendientes.',
    color: 'bg-pillar-teal',
  },
  {
    to: '/certificados',
    title: 'Certificados',
    text: 'Descargá el certificado de cada curso que hayas finalizado.',
    color: 'bg-pillar-green',
  },
];

export function Home() {
  return (
    <>
      <PageHero title="Educación" size="full" />

      <Section
        title="Plataforma de formación AAMEVi"
        subtitle="Formación en medicina del estilo de vida para profesionales de la salud."
        narrow
      >
        <div className="grid gap-6 md:grid-cols-3">
          {accesos.map(({ to, title, text, color }) => (
            <Link
              key={to}
              to={to}
              className="group block bg-white no-underline transition-shadow hover:shadow-lg"
            >
              <div className={`h-2 ${color}`} />
              <div className="p-6">
                <h3 className="mb-2 text-xl font-bold group-hover:text-accent">{title}</h3>
                <p className="text-sm leading-relaxed">{text}</p>
              </div>
            </Link>
          ))}
        </div>
      </Section>

      {/* Banda de llamada a la acción, equivalente al bloque `.membresia` del sitio madre */}
      <section>
        <h2 className="bg-ink py-2 text-center text-sm uppercase text-white">Sumate a AAMEVi</h2>
        <div className="container-site-sm py-10 text-center md:py-16">
          <p className="mx-auto max-w-xl text-xl font-light">
            Creá tu cuenta para inscribirte a los cursos y llevar el seguimiento de tu formación.
          </p>
          <div className="mt-6 flex flex-wrap justify-center gap-6">
            <ButtonLink to="/registro" cta>
              Quiero unirme
            </ButtonLink>
            <ButtonLink to="/login" cta variant="ghost">
              Ya tengo cuenta
            </ButtonLink>
          </div>
        </div>
      </section>
    </>
  );
}
