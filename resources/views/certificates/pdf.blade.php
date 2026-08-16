{{--
    El certificado, tal como se imprime.

    Está escrito para dompdf, que no es un navegador: no hay flexbox ni grid, los
    márgenes negativos no andan y las medidas en `rem` no significan nada. Por eso
    va todo en tablas y puntos, que es lo que ese motor renderiza igual siempre.

    La tipografía es DejaVu Sans, la única que dompdf trae con acentos y eñes
    resueltos. Montserrat exigiría empaquetar los archivos de la fuente y
    registrarlos; no vale la pena por una hoja.

    La franja de seis colores es el isotipo de AAMEVi —los seis pilares de la
    medicina del estilo de vida— reducido a lo que este motor sí sabe pintar.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $certificate->certificate_number }}</title>
    <style>
        @page { margin: 0; }

        body {
            margin: 0;
            font-family: 'DejaVu Sans', sans-serif;
            color: #333333;
        }

        /*
         * A4 apaisado son 595pt de alto. dompdf no estira un bloque hasta el
         * pie, así que el pie va posicionado contra el borde de la hoja y el
         * cuerpo se centra dentro de una celda de alto fijo: es la única forma
         * de que un título largo no empuje todo hacia abajo.
         */
        .hoja {
            padding: 0 54pt;
        }

        .cuerpo { width: 100%; border-collapse: collapse; }

        .cuerpo td {
            height: 500pt;
            vertical-align: middle;
            text-align: center;
        }

        .pilares { width: 100%; border-collapse: collapse; }
        .pilares td { height: 8pt; }

        .marca {
            font-size: 22pt;
            font-weight: bold;
            letter-spacing: 3pt;
            color: #007c79;
        }

        .institucion {
            font-size: 8.5pt;
            letter-spacing: 1.5pt;
            text-transform: uppercase;
            color: #5b5b5b;
            padding-top: 4pt;
        }

        .titulo {
            font-size: 16pt;
            letter-spacing: 5pt;
            text-transform: uppercase;
            padding-top: 42pt;
        }

        .leyenda { font-size: 11pt; color: #5b5b5b; padding-top: 30pt; }

        .alumno {
            font-size: 30pt;
            padding-top: 10pt;
            padding-bottom: 8pt;
        }

        .curso {
            font-size: 16pt;
            font-weight: bold;
            color: #007c79;
            padding-top: 8pt;
        }

        .horas { font-size: 10pt; color: #5b5b5b; padding-top: 14pt; }

        .pie {
            position: absolute;
            left: 54pt;
            right: 54pt;
            bottom: 42pt;
            width: 734pt;
            border-collapse: collapse;
        }
        .pie td { font-size: 8.5pt; color: #5b5b5b; vertical-align: bottom; }

        .firma {
            border-top: 1px solid #d3d3d2;
            padding-top: 5pt;
            width: 200pt;
        }

        .firma .nombre { font-size: 10pt; color: #333333; }

        .numero {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 9pt;
            color: #333333;
        }
    </style>
</head>
<body>
    {{-- Los seis colores del isotipo, en el orden del logo --}}
    <table class="pilares">
        <tr>
            <td style="background-color: #0071b6"></td>
            <td style="background-color: #00b8b3"></td>
            <td style="background-color: #01875f"></td>
            <td style="background-color: #edbc42"></td>
            <td style="background-color: #f46707"></td>
            <td style="background-color: #d04742"></td>
        </tr>
    </table>

    <div class="hoja">
        <table class="cuerpo"><tr><td>
            <div class="marca">AAMEVi</div>
            <div class="institucion">Asociación Argentina de Medicina del Estilo de Vida</div>

            <div class="titulo">Certificado de finalización</div>

            <div class="leyenda">Se deja constancia de que</div>
            <div class="alumno">{{ $certificate->enrollment->student->user->full_name }}</div>

            <div class="leyenda" style="padding-top: 0">completó satisfactoriamente el curso</div>
            <div class="curso">{{ $certificate->enrollment->course->title }}</div>

            <div class="horas">
                {{ $clases }} {{ $clases === 1 ? 'clase' : 'clases' }} ·
                Emitido el {{ $certificate->issued_at->translatedFormat('d \d\e F \d\e Y') }}
            </div>
        </td></tr></table>

        <table class="pie">
            <tr>
                <td>
                    <div class="firma">
                        <div class="nombre">{{ $certificate->enrollment->course->teacher?->user?->full_name }}</div>
                        <div>Docente a cargo</div>
                    </div>
                </td>
                <td style="text-align: right">
                    <div class="numero">{{ $certificate->certificate_number }}</div>
                    <div>Número de certificado</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
