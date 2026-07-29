import { PageHero } from '@/components/ui/PageHero';
import { Section } from '@/components/ui/Section';

/**
 * Página temporal mientras se construyen los módulos de negocio (ver
 * docs/PLAN_ARQUITECTONICO.md). Sirve para verificar el shell y los tokens.
 */
export function Placeholder({ title }: { title: string }) {
  return (
    <>
      <PageHero title={title} />
      <Section narrow>
        <p className="text-base">Esta sección todavía no está implementada.</p>
      </Section>
    </>
  );
}
