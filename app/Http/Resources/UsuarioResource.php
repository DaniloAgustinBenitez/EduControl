<?php

namespace App\Http\Resources;

use App\Support\Rol;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Usuario autenticado con su rol y sus permisos (RF-04, RF-26). */
class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $rol = $this->getRoleNames()->first();

        return [
            'id' => $this->id,
            'nombre' => $this->name,
            'email' => $this->email,
            'rol' => $rol,
            'rol_etiqueta' => Rol::ETIQUETAS[$rol] ?? null,
            'permisos' => $this->getAllPermissions()->pluck('name')->values(),
            'usa_app' => in_array($rol, Rol::USAN_APP, true),
        ];
    }
}
