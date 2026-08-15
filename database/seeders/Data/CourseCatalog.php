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
        ];
    }
}
