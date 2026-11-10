<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Curso con el nombre de su nivel (RN-02). */
class CursoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'nivel' => $this->nivel->nombre,
            'nombre_completo' => $this->nombreCompleto(),
        ];
    }
}
