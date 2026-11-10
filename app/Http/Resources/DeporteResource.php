<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Deporte con sus horarios, para que la app muestre las superposiciones (RN-04, RN-05). */
class DeporteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'horarios' => HorarioResource::collection($this->horarios),
        ];
    }
}
