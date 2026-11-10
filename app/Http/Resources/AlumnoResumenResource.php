<?php

namespace App\Http\Resources;

use App\Models\Alumno;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Hijo del tutor con su curso y el estado de sus inscripciones.
 * No expone DNI, teléfono, correo ni domicilio del alumno.
 */
class AlumnoResumenResource extends JsonResource
{
    /** @param  array|null  $estado  resultado de InscripcionService::estado(), o null si no hay ciclo activo */
    public function __construct(Alumno $alumno, private readonly ?array $estado)
    {
        parent::__construct($alumno);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'legajo' => $this->legajo,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'nombre_completo' => $this->nombreCompleto(),
            'curso' => $this->curso ? new CursoResource($this->curso) : null,
            'estado' => $this->estado !== null ? new EstadoResource($this->estado) : null,
        ];
    }
}
