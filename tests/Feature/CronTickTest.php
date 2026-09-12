<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Artisan;

/**
 * `cron/tick` existe para el hosting sin `crontab` por SSH (ver
 * docs/DEPLOY.md): el panel del hosting pega a esta URL en lugar de correr
 * `schedule:run` desde una línea de cron. `signed` es la única protección, así
 * que sin la firma correcta la ruta tiene que rechazar el pedido.
 */
class CronTickTest extends TestCase
{
    public function test_sin_firma_lo_rechaza(): void
    {
        $this->get('/cron/tick')->assertForbidden();
    }

    public function test_con_firma_corre_el_scheduler_y_responde_ok(): void
    {
        Artisan::shouldReceive('call')->once()->with('schedule:run');

        $enlace = URL::signedRoute('cron.tick');

        $this->get($enlace)->assertOk()->assertSee('OK');
    }
}
