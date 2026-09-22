<?php

namespace App\Policies;

use App\Models\Alumno;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * RN-07 / RNF-01: un tutor solo puede ver y operar sobre los alumnos vinculados a él,
 * aunque intente entrar escribiendo la URL a mano.
 */
class AlumnoPolicy
{
    public function operar(User $user, Alumno $alumno): Response
    {
        if ($user->can('usuarios.gestionar')) {
            return Response::allow();
        }

        $vinculado = $user->tutor !== null
            && $user->tutor->alumnos()->whereKey($alumno->id)->exists();

        return $vinculado
            ? Response::allow()
            : Response::deny('Solo podés gestionar las inscripciones de los alumnos que tenés a cargo.');
    }
}
