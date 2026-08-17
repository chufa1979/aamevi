<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Lo programado.
 *
 * En el servidor cron llama a `schedule:run` cada minuto y Laravel decide qué
 * toca; es una sola línea de cron para todo el proyecto en lugar de una por
 * tarea. El detalle de cómo se configura está en `docs/DEPLOY.md`.
 *
 * `withoutOverlapping` importa: si una tanda de correos se demora más de cinco
 * minutos, la corrida siguiente no tiene que empezar a mandar los mismos.
 */
Schedule::command('emails:enviar')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Temprano: el aviso es de un día para el otro, y a las 7 el destinatario
// todavía tiene el día por delante para acomodarse
Schedule::command('emails:recordatorios')
    ->dailyAt('07:00');
