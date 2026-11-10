<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Inscripciones del alumno en el ciclo actual, a partir de InscripcionService::estado().
 * El id de cada inscripción es el que la app usa para darla de baja (RF-12).
 */
class EstadoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        ['cursado' => $cursado, 'comedor' => $comedor, 'transporte' => $transporte, 'deportes' => $deportes] = $this->resource;

        return [
            'cursado' => $cursado ? ['id' => $cursado->id, 'curso' => new CursoResource($cursado->curso)] : null,
            'comedor' => $comedor ? ['id' => $comedor->id] : null,
            'transporte' => $transporte ? ['id' => $transporte->id, 'recorrido' => new RecorridoResource($transporte->recorrido)] : null,
            'deportes' => $deportes->map(fn ($inscripcion) => [
                'id' => $inscripcion->id,
                'deporte' => new DeporteResource($inscripcion->deporte),
            ])->values(),
        ];
    }
}
