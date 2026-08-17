<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;

/**
 * Vacía la cola de avisos.
 *
 * En el servidor lo llama cron cada cinco minutos (ver `docs/DEPLOY.md`). El
 * límite por corrida existe para que una tanda grande no se coma el tiempo
 * máximo de ejecución del hosting: lo que no salga ahora sale en la corrida
 * siguiente, que es dentro de cinco minutos.
 */
class SendQueuedEmails extends Command
{
    protected $signature = 'emails:enviar {--limite=50 : Cuántos mandar como máximo en esta corrida}';

    protected $description = 'Envía los avisos pendientes de email_queue';

    public function handle(NotificationService $avisos): int
    {
        $resultado = $avisos->drain((int) $this->option('limite'));

        $this->info("Avisos enviados: {$resultado['enviados']}. Fallidos: {$resultado['fallidos']}.");

        return self::SUCCESS;
    }
}
