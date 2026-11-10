<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/** Detalle de un hijo: el resumen más los horarios de cursado de su curso (RN-05). */
class AlumnoDetalleResource extends AlumnoResumenResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'horarios_cursado' => HorarioResource::collection($this->curso?->horarios ?? collect()),
        ];
    }
}
