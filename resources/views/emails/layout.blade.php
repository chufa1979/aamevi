{{--
    Marco común de los avisos.

    Escrito con tablas y estilos en línea porque un cliente de correo no es un
    navegador: Gmail descarta buena parte de lo que va en `<style>`, y ni flexbox
    ni grid existen acá. La franja de los seis pilares es el isotipo reducido a
    lo que sí se puede pintar, igual que en el certificado.

    El cuerpo se renderiza al encolar y queda guardado en `email_queue`: lo que
    figura ahí es exactamente lo que salió.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $asunto }}</title>
</head>
<body style="margin:0; padding:0; background-color:#ececec; font-family:Arial,Helvetica,sans-serif; color:#333333;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#ececec; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff;">
                    <tr>
                        <td style="padding:0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td height="6" style="background-color:#0071b6; font-size:0; line-height:0;">&nbsp;</td>
                                    <td height="6" style="background-color:#00b8b3; font-size:0; line-height:0;">&nbsp;</td>
                                    <td height="6" style="background-color:#01875f; font-size:0; line-height:0;">&nbsp;</td>
                                    <td height="6" style="background-color:#edbc42; font-size:0; line-height:0;">&nbsp;</td>
                                    <td height="6" style="background-color:#f46707; font-size:0; line-height:0;">&nbsp;</td>
                                    <td height="6" style="background-color:#d04742; font-size:0; line-height:0;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px 32px 8px;">
                            <p style="margin:0; font-size:20px; font-weight:bold; letter-spacing:2px; color:#007c79;">AAMEVi</p>
                            <p style="margin:4px 0 0; font-size:11px; letter-spacing:1px; text-transform:uppercase; color:#5b5b5b;">
                                Asociación Argentina de Medicina del Estilo de Vida
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:16px 32px 32px; font-size:15px; line-height:1.6;">
                            @yield('cuerpo')
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:18px 32px; background-color:#333333; font-size:12px; line-height:1.5; color:#ececec;">
                            Recibís este correo porque tenés una cuenta en la plataforma de formación de AAMEVi.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
