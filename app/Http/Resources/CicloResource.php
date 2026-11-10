<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Ciclo lectivo activo (objeto "Ciclo" de docs/api.md). */
class CicloResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'anio' => (int) $this->anio,
        ];
    }
}
