<?php

namespace App\Http\Resources;

use App\Models\Horario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Franja horaria de un curso o de un deporte, siempre en formato HH:MM. */
class HorarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'dia' => (int) $this->dia,
            'dia_nombre' => Horario::DIAS[$this->dia],
            'inicio' => substr($this->hora_inicio, 0, 5),
            'fin' => substr($this->hora_fin, 0, 5),
            'descripcion' => $this->descripcion(),
        ];
    }
}
