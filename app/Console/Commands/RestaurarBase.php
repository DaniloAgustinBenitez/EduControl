<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * RNF-09: restaura un respaldo generado con db:respaldar. Reemplaza los datos actuales.
 */
#[Signature('db:restaurar {archivo? : Ruta del .sql; si se omite, usa el respaldo más reciente} {--force : No pedir confirmación}')]
#[Description('Restaura la base de datos desde una copia de seguridad')]
class RestaurarBase extends Command
{
    public function handle(): int
    {
        $db = config('database.connections.'.config('database.default'));
        $archivo = $this->argument('archivo') ?? $this->masReciente();

        if (! $archivo || ! is_file($archivo)) {
            $this->error('No se encontró el archivo de respaldo.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Se reemplazarán TODOS los datos de «{$db['database']}» con {$archivo}. ¿Continuar?")) {
            return self::FAILURE;
        }

        $proceso = new Process([
            config('database.respaldo.mysql'),
            '--host='.$db['host'], '--port='.$db['port'], '--user='.$db['username'],
            '--default-character-set=utf8mb4',
            $db['database'],
        ], env: ['MYSQL_PWD' => (string) $db['password']], input: fopen($archivo, 'r'), timeout: 600);
        $proceso->run();

        if (! $proceso->isSuccessful()) {
            $this->error('Falló la restauración: '.trim($proceso->getErrorOutput()));

            return self::FAILURE;
        }

        $this->info('Base restaurada desde '.$archivo);

        return self::SUCCESS;
    }

    private function masReciente(): ?string
    {
        $respaldos = glob(storage_path('app/respaldos').DIRECTORY_SEPARATOR.'*.sql');
        rsort($respaldos);

        return $respaldos[0] ?? null;
    }
}
