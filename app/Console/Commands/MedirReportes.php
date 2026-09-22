<?php

namespace App\Console\Commands;

use App\Http\Controllers\ReporteController;
use App\Models\CicloLectivo;
use App\Services\ReporteService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * RNF-07: los reportes deben responder en menos de 3 segundos.
 */
#[Signature('reportes:medir')]
#[Description('Mide el tiempo de generación de cada reporte con los datos actuales')]
class MedirReportes extends Command
{
    private const LIMITE_MS = 3000;

    public function handle(ReporteService $reportes): int
    {
        $ciclo = CicloLectivo::actual();
        $filas = [];
        $ok = true;

        foreach (array_keys(ReporteController::TIPOS) as $tipo) {
            $inicio = hrtime(true);
            $reporte = $reportes->generar($tipo, $ciclo, null);
            $ms = (hrtime(true) - $inicio) / 1e6;
            $ok = $ok && $ms < self::LIMITE_MS;
            $filas[] = [$tipo, $reporte['filas']->count(), number_format($ms, 0).' ms', $ms < self::LIMITE_MS ? 'OK' : 'LENTO'];
        }

        $this->table(['Reporte', 'Filas', 'Tiempo', 'RNF-07 (< 3 s)'], $filas);

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
