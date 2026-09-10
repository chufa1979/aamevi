<?php

namespace Database\Seeders\Data;

/**
 * Contenido de los cursos de ejemplo.
 *
 * Está separado del seeder porque es sólo datos: cinco cursos de medicina del
 * estilo de vida, con sus módulos y el título de cada clase. El seeder se
 * ocupa de la mecánica —fechas, evaluaciones, inscripciones, avance— y no de
 * qué dice cada fila.
 *
 * El título de la clase es además el tema de sus preguntas: `CourseSeeder`
 * arma cinco por clase a partir de él. Por eso los títulos son sustantivos y
 * no verbos: se leen bien dentro de la pregunta.
 */
class CourseCatalog
{
    /** @return array<int, array<string, mixed>> */
    public static function all(): array
    {
        return [
            [
                'title' => 'Fundamentos de la Medicina del Estilo de Vida',
                'description' => '<p>Introducción a los <strong>seis pilares</strong> de la medicina del estilo de vida y a la evidencia que los respalda. Es el curso de entrada: los demás lo dan por visto.</p>',
                'max_students' => 60,
                'teacher' => 'profesor@aamevi.ar',
                'location' => 'Campus Virtual AAMEVi',
                'specialties' => 'Medicina General, Medicina del Estilo de Vida',
                'schedule_days' => 'Clases asincrónicas: disponible todos los días / Clases sincrónicas: jueves',
                'schedule_time' => 'Asincrónicas on-demand las 24 hs / Sincrónicas: 19:00 hs.',
                'investment_info' => "5 cuotas fijas mensuales sin interés de \$190.400 c/u.\nAbonando en un pago obtené un 20% de descuento.",
                'certification_info' => 'Certifica AAMEVi. Diploma digital al aprobar todas las evaluaciones del curso.',
                'teaching_staff' => "Lic. Carla Fernández — Nutrición\nDr. Martín López — Medicina del deporte\nLic. Valeria Campos — Psicología de la salud",
                'objectives' => "Comprender los seis pilares de la medicina del estilo de vida\nAplicar la entrevista motivacional para el cambio de hábitos\nDiseñar un plan de prescripción integral",
                'enrollment_requirements' => "Título profesional en ciencias de la salud\nFotocopia de DNI",
                'modules' => [
                    ['Qué es la medicina del estilo de vida', [
                        'Origen y definición de la disciplina',
                        'Los seis pilares y su interacción',
                        'Diferencias con la medicina preventiva clásica',
                        'El rol del profesional de la salud',
                        'Marco ético y alcance de la intervención',
                    ]],
                    ['La evidencia detrás de los pilares', [
                        'Lectura crítica de estudios de intervención',
                        'Cohortes longitudinales y zonas azules',
                        'Reversión de la enfermedad cardiovascular',
                        'Remisión de la diabetes tipo 2',
                        'Límites y sesgos de la evidencia disponible',
                    ]],
                    ['Evaluación inicial del paciente', [
                        'La historia clínica del estilo de vida',
                        'Cuestionarios y escalas validadas',
                        'Biomarcadores de riesgo cardiometabólico',
                        'Composición corporal y medidas antropométricas',
                        'Registro y seguimiento de la línea de base',
                    ]],
                    ['La entrevista motivacional', [
                        'Etapas del cambio de comportamiento',
                        'Ambivalencia y resistencia al cambio',
                        'Preguntas abiertas y escucha reflexiva',
                        'Acuerdo de metas y planes de acción',
                        'Manejo de las recaídas',
                    ]],
                    ['Prescripción del estilo de vida', [
                        'De la recomendación general a la indicación escrita',
                        'Dosis, frecuencia y progresión',
                        'Adherencia y seguimiento',
                        'Trabajo interdisciplinario',
                        'Armado de un plan integral',
                    ]],
                ],
            ],

            [
                'title' => 'Nutrición basada en plantas y prescripción alimentaria',
                'description' => '<p>De los patrones alimentarios con respaldo a la <strong>indicación escrita</strong> en el consultorio. Es el curso más largo del programa: ocho módulos.</p>',
                'max_students' => 45,
                'teacher' => 'profesora@aamevi.ar',
                'location' => 'Campus Virtual AAMEVi',
                'specialties' => 'Nutrición, Medicina del Estilo de Vida',
                'schedule_days' => 'Clases asincrónicas: disponible todos los días / Clases sincrónicas: martes',
                'schedule_time' => 'Asincrónicas on-demand las 24 hs / Sincrónicas: 19:00 hs.',
                'investment_info' => "8 cuotas fijas mensuales sin interés de \$150.000 c/u.\nAbonando en un pago obtené un 20% de descuento.",
                'certification_info' => 'Certifica AAMEVi. Diploma digital al aprobar todas las evaluaciones del curso.',
                'teaching_staff' => "Lic. Carla Fernández — Nutrición clínica\nDr. Pablo Suárez — Gastroenterología",
                'objectives' => "Prescribir planes alimentarios basados en plantas con respaldo de evidencia\nEvaluar el impacto metabólico de distintos patrones dietarios\nAsesorar a pacientes en una transición alimentaria segura",
                'enrollment_requirements' => "Título profesional en ciencias de la salud\nFotocopia de DNI",
                'modules' => [
                    ['Patrones alimentarios con respaldo', [
                        'La dieta mediterránea',
                        'Los patrones basados en plantas',
                        'La dieta DASH y la hipertensión',
                        'La alimentación en poblaciones longevas',
                        'Comparación de patrones y resultados clínicos',
                    ]],
                    ['Macronutrientes en contexto', [
                        'La calidad de los hidratos de carbono',
                        'Las grasas: tipos y fuentes',
                        'Las proteínas de origen vegetal',
                        'La fibra y la salud intestinal',
                        'Densidad energética y saciedad',
                    ]],
                    ['Micronutrientes y suplementación', [
                        'La vitamina B12 en dietas vegetarianas',
                        'El hierro y su biodisponibilidad',
                        'El calcio y la salud ósea',
                        'La vitamina D y la exposición solar',
                        'Cuándo suplementar y cuándo no',
                    ]],
                    ['Alimentos ultraprocesados', [
                        'La clasificación NOVA',
                        'Ultraprocesados y riesgo cardiometabólico',
                        'Lectura de etiquetas y rotulado frontal',
                        'Estrategias de sustitución',
                        'El entorno alimentario',
                    ]],
                    ['Microbiota intestinal', [
                        'Composición y funciones de la microbiota',
                        'Fibra fermentable y ácidos grasos de cadena corta',
                        'Probióticos y prebióticos',
                        'El eje intestino-cerebro',
                        'Intervenciones dietarias sobre la microbiota',
                    ]],
                    ['Nutrición en situaciones clínicas', [
                        'La diabetes tipo 2',
                        'Las dislipidemias',
                        'La hipertensión arterial',
                        'La enfermedad renal crónica',
                        'La obesidad y el manejo del peso',
                    ]],
                    ['Planificación de menús', [
                        'La estructura de un plan semanal',
                        'Compras, presupuesto y estacionalidad',
                        'Preparación y conservación de alimentos',
                        'La adaptación cultural del plan',
                        'Comer fuera de casa',
                    ]],
                    ['Prescripción nutricional en consultorio', [
                        'La anamnesis alimentaria',
                        'El registro de ingesta y sus limitaciones',
                        'La redacción de la indicación',
                        'Educación alimentaria en la consulta',
                        'Seguimiento y ajuste del plan',
                    ]],
                ],
            ],

            [
                'title' => 'Actividad física y prescripción del ejercicio',
                'description' => '<p>Cómo pasar de «haga ejercicio» a una <strong>prescripción con dosis</strong>: tipo, frecuencia, intensidad y progresión.</p>',
                'max_students' => 40,
                'teacher' => 'profesor@aamevi.ar',
                'location' => 'Campus Virtual AAMEVi',
                'specialties' => 'Medicina del Deporte, Medicina del Estilo de Vida',
                'schedule_days' => 'Clases asincrónicas: disponible todos los días / Clases sincrónicas: miércoles',
                'schedule_time' => 'Asincrónicas on-demand las 24 hs / Sincrónicas: 20:00 hs.',
                'investment_info' => "6 cuotas fijas mensuales sin interés de \$150.000 c/u.\nAbonando en un pago obtené un 20% de descuento.",
                'certification_info' => 'Certifica AAMEVi. Diploma digital al aprobar todas las evaluaciones del curso.',
                'teaching_staff' => "Dr. Martín López — Medicina del deporte\nLic. Sofía Torres — Kinesiología",
                'objectives' => "Prescribir actividad física según el perfil de riesgo del paciente\nDiseñar programas de entrenamiento progresivo\nReconocer contraindicaciones y señales de alarma",
                'enrollment_requirements' => "Título profesional en ciencias de la salud\nFotocopia de DNI",
                'modules' => [
                    ['Fisiología del ejercicio', [
                        'Los sistemas energéticos',
                        'Adaptaciones cardiovasculares',
                        'Adaptaciones musculoesqueléticas',
                        'La respuesta hormonal al ejercicio',
                        'Recuperación y sobreentrenamiento',
                    ]],
                    ['Evaluación de la condición física', [
                        'Anamnesis y estratificación de riesgo',
                        'Pruebas de capacidad aeróbica',
                        'La evaluación de la fuerza',
                        'Flexibilidad y movilidad',
                        'Interpretación de los resultados',
                    ]],
                    ['Prescripción del ejercicio aeróbico', [
                        'Frecuencia, intensidad, tiempo y tipo',
                        'Zonas de intensidad y percepción del esfuerzo',
                        'La progresión de la carga',
                        'Las recomendaciones semanales en adultos',
                        'La adaptación en principiantes',
                    ]],
                    ['Entrenamiento de fuerza', [
                        'Los principios del entrenamiento de fuerza',
                        'La selección de ejercicios',
                        'Series, repeticiones y descanso',
                        'La fuerza en adultos mayores',
                        'La prevención de lesiones',
                    ]],
                    ['Sedentarismo y movimiento cotidiano', [
                        'Los riesgos del comportamiento sedentario',
                        'Las pausas activas',
                        'El movimiento no estructurado',
                        'Podómetros y dispositivos de medición',
                        'Intervenciones en el lugar de trabajo',
                    ]],
                    ['Poblaciones especiales', [
                        'El ejercicio en el embarazo',
                        'El ejercicio en la infancia y la adolescencia',
                        'El ejercicio con patología cardiovascular',
                        'Ejercicio y salud mental',
                        'Armado de un plan para un caso real',
                    ]],
                ],
            ],

            [
                'title' => 'Sueño, estrés y salud mental',
                'description' => '<p>Los pilares que menos se indican y más pesan en el resultado. Cuatro módulos, con foco en herramientas aplicables en la consulta.</p>',
                'max_students' => 35,
                'teacher' => 'profesora@aamevi.ar',
                'location' => 'Campus Virtual AAMEVi',
                'specialties' => 'Psiquiatría, Psicología, Medicina del Estilo de Vida',
                'schedule_days' => 'Clases asincrónicas: disponible todos los días / Clases sincrónicas: lunes',
                'schedule_time' => 'Asincrónicas on-demand las 24 hs / Sincrónicas: 19:30 hs.',
                'investment_info' => "4 cuotas fijas mensuales sin interés de \$150.000 c/u.\nAbonando en un pago obtené un 20% de descuento.",
                'certification_info' => 'Certifica AAMEVi. Diploma digital al aprobar todas las evaluaciones del curso.',
                'teaching_staff' => "Lic. Valeria Campos — Psicología del sueño\nDr. Ezequiel Blanco — Psiquiatría",
                'objectives' => "Evaluar la calidad del sueño y su impacto en la salud\nAplicar técnicas de manejo del estrés en la consulta\nIntegrar la salud mental al abordaje del estilo de vida",
                'enrollment_requirements' => "Título profesional en ciencias de la salud\nFotocopia de DNI",
                'modules' => [
                    ['Arquitectura y regulación del sueño', [
                        'Fases y ciclos del sueño',
                        'El ritmo circadiano y la luz',
                        'Las necesidades de sueño según la edad',
                        'Las consecuencias de la deuda de sueño',
                        'Instrumentos de evaluación del sueño',
                    ]],
                    ['Higiene del sueño e insomnio', [
                        'Las medidas de higiene del sueño',
                        'La terapia cognitivo-conductual para el insomnio',
                        'Restricción de sueño y control de estímulos',
                        'El uso de pantallas y la cafeína',
                        'Cuándo derivar a un especialista',
                    ]],
                    ['Fisiología del estrés', [
                        'El eje hipotálamo-hipófiso-adrenal',
                        'Estrés agudo y estrés crónico',
                        'Estrés e inflamación',
                        'Estrés y conducta alimentaria',
                        'La medición del estrés percibido',
                    ]],
                    ['Manejo del estrés', [
                        'Respiración y relajación',
                        'Atención plena y meditación',
                        'La actividad física como regulador',
                        'Naturaleza y descanso',
                        'El plan personal de manejo del estrés',
                    ]],
                ],
            ],

            [
                'title' => 'Vínculos, comunidad y cambio de comportamiento',
                'description' => '<p>El pilar social y la pregunta que atraviesa a todos los demás: <strong>cómo se sostiene un cambio</strong> en el tiempo.</p>',
                'max_students' => 40,
                'teacher' => 'profesor@aamevi.ar',
                'location' => 'Campus Virtual AAMEVi',
                'specialties' => 'Coaching en Salud, Medicina del Estilo de Vida',
                'schedule_days' => 'Clases asincrónicas: disponible todos los días / Clases sincrónicas: viernes',
                'schedule_time' => 'Asincrónicas on-demand las 24 hs / Sincrónicas: 18:30 hs.',
                'investment_info' => "5 cuotas fijas mensuales sin interés de \$150.000 c/u.\nAbonando en un pago obtené un 20% de descuento.",
                'certification_info' => 'Certifica AAMEVi. Diploma digital al aprobar todas las evaluaciones del curso.',
                'teaching_staff' => "Lic. Rocío Medina — Coaching en salud\nDr. Federico Aguirre — Medicina familiar",
                'objectives' => "Aplicar la entrevista motivacional al sostenimiento del cambio\nDiseñar estrategias de apoyo comunitario\nAcompañar procesos de cambio a lo largo del tiempo",
                'enrollment_requirements' => "Título profesional en ciencias de la salud\nFotocopia de DNI",
                'modules' => [
                    ['Conexión social y salud', [
                        'La soledad como factor de riesgo',
                        'Redes de apoyo y longevidad',
                        'Calidad y cantidad de los vínculos',
                        'El aislamiento en adultos mayores',
                        'La evaluación del entorno social',
                    ]],
                    ['Evitar sustancias tóxicas', [
                        'Tabaquismo y cesación',
                        'El consumo de alcohol y el riesgo',
                        'La entrevista breve en consultorio',
                        'Terapias de reemplazo y farmacología',
                        'La prevención de recaídas',
                    ]],
                    ['Cambio de comportamiento', [
                        'Los modelos de cambio de conducta',
                        'La formación y el sostenimiento de hábitos',
                        'Autoeficacia y motivación intrínseca',
                        'Barreras y facilitadores del cambio',
                        'El diseño del entorno',
                    ]],
                    ['Intervención grupal y comunitaria', [
                        'Los grupos de apoyo',
                        'Talleres y educación grupal',
                        'Los programas comunitarios',
                        'Los determinantes sociales de la salud',
                        'La evaluación de impacto',
                    ]],
                    ['Integración y práctica clínica', [
                        'La consulta de estilo de vida paso a paso',
                        'La priorización de pilares según el paciente',
                        'Registro y seguimiento longitudinal',
                        'El trabajo en equipo interdisciplinario',
                        'Caso integrador final',
                    ]],
                ],
            ],

            /*
             * La edición cerrada.
             *
             * Los cinco cursos de arriba están dictándose: ninguno termina antes
             * de diciembre, así que con ellos solos no habría un solo alumno
             * recibido y las pantallas de certificados quedarían vacías. Éste ya
             * terminó —`schedule` le pone su propio cronograma, todo en el
             * pasado—, y de ahí salen las inscripciones finalizadas y los
             * certificados emitidos.
             *
             * Es corto a propósito: dos módulos de cinco clases alcanzan para
             * ver el circuito completo sin agregar cien filas más.
             */
            [
                'title' => 'Introducción a la Medicina del Estilo de Vida (edición 2025)',
                'description' => '<p>Edición <strong>ya finalizada</strong> del curso introductorio. Se conserva para consulta y para la emisión de certificados.</p>',
                'max_students' => 30,
                'teacher' => 'profesora@aamevi.ar',
                'schedule' => ['2025-08-05', '2025-11-25'],
                // Fuera del catálogo: una edición terminada no se puede cursar.
                // Los que la hicieron conservan su acceso y su certificado —el
                // aula no mira `is_active`, sólo la inscripción—
                'is_active' => false,
                'modules' => [
                    ['Los seis pilares', [
                        'Qué propone la medicina del estilo de vida',
                        'Alimentación basada en plantas',
                        'Actividad física y sedentarismo',
                        'Sueño y descanso reparador',
                        'Estrés, vínculos y sustancias',
                    ]],
                    ['De la teoría al consultorio', [
                        'La primera consulta de estilo de vida',
                        'Cómo se mide un cambio de hábito',
                        'El seguimiento a los tres meses',
                        'Errores frecuentes en la indicación',
                        'Caso de cierre',
                    ]],
                ],
            ],
        ];
    }
}
