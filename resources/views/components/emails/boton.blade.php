@props(['href'])

{{--
    Botón de un aviso. Va como tabla y no como `<a>` con padding porque Outlook
    ignora el padding de los enlaces y el botón queda del alto del texto.

    El teal pleno con texto oscuro da 5,12:1, que pasa AA — el mismo criterio que
    `.btn` en el sitio.
--}}
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:20px 0;">
    <tr>
        <td style="background-color:#00b8b3; border-radius:8px;">
            <a href="{{ $href }}"
               style="display:inline-block; padding:12px 22px; font-size:15px; font-weight:bold; color:#333333; text-decoration:none;">
                {{ $slot }}
            </a>
        </td>
    </tr>
</table>
