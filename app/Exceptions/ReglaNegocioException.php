<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Una operación rechazada por una regla de negocio (RN-01 a RN-07).
 * El mensaje está pensado para mostrarse tal cual al usuario.
 */
class ReglaNegocioException extends RuntimeException
{
    public function __construct(string $message, public readonly ?string $regla = null)
    {
        parent::__construct($message);
    }
}
