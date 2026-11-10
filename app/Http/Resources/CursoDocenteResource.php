<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * Curso visto por el docente (HU4): suma los horarios y, si se cargaron en la relación
 * "materias", solo las materias que dicta ese docente en el curso.
 */
class CursoDocenteResource extends CursoResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'horarios' => HorarioResource::collection($this->horarios),
            'materias' => MateriaResource::collection($this->whenLoaded('materias')),
        ];
    }
}
