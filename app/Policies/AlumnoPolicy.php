<?php

namespace App\Policies;

use App\Models\Alumno;
use App\Models\User;

/**
 * RN-07 / RNF-01: un tutor solo puede ver y operar sobre los alumnos vinculados a él,
 * aunque intente entrar escribiendo la URL a mano.
 */
class AlumnoPolicy
{
    public function operar(User $user, Alumno $alumno): bool
    {
        if ($user->can('usuarios.gestionar')) {
            return true;
        }

        return $user->tutor !== null
            && $user->tutor->alumnos()->whereKey($alumno->id)->exists();
    }
}
