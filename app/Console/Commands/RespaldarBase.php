<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * RNF-09: copia de seguridad de la base MySQL con mysqldump.
 * Deja el archivo en storage/app/respaldos y conserva los últimos N.
 */
#[Signature('db:respaldar {--conservar=14 : Cantidad de respaldos a conservar}')]
#[Description('Genera una copia de seguridad de la base de datos')]
class RespaldarBase extends Command
{
    public function handle(): int
    {
        $db = config('database.connections.'.config('database.default'));
        if (($db['driver'] ?? null) !== 'mysql') {
            $this->error('El respaldo está pensado para MySQL.');

            return self::FAILURE;
        }

        $carpeta = storage_path('app/respaldos');
        is_dir($carpeta) || mkdir($carpeta, 0755, true);
        $archivo = $carpeta.DIRECTORY_SEPARATOR.$db['database'].'-'.now()->format('Ymd-His').'.sql';

        $proceso = new Process([
            config('database.respaldo.mysqldump'),
            '--host='.$db['host'], '--port='.$db['port'], '--user='.$db['username'],
            '--single-transaction', '--routines', '--no-tablespaces', '--default-character-set=utf8mb4',
            '--result-file='.$archivo,
            $db['database'],
        ], env: ['MYSQL_PWD' => (string) $db['password']], timeout: 600);
        $proceso->run();

        if (! $proceso->isSuccessful()) {
            @unlink($archivo);
            $this->error('Falló mysqldump: '.trim($proceso->getErrorOutput()));

            return self::FAILURE;
        }

        $this->podar($carpeta, (int) $this->option('conservar'));
        $this->info('Respaldo generado: '.$archivo.' ('.number_format(filesize($archivo) / 1024, 0).' KB)');

        return self::SUCCESS;
    }

    private function podar(string $carpeta, int $conservar): void
    {
        $respaldos = glob($carpeta.DIRECTORY_SEPARATOR.'*.sql');
        rsort($respaldos);
        array_map('unlink', array_slice($respaldos, max(1, $conservar)));
    }
}
