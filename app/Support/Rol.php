<?php

namespace App\Support;

/**
 * Los cuatro perfiles del sistema (RF-04) y los permisos que habilita cada uno.
 */
final class Rol
{
    public const ADMINISTRADOR = 'administrador';

    public const ADMINISTRATIVO = 'administrativo';

    public const DOCENTE = 'docente';

    public const TUTOR = 'tutor';

    public const PERMISOS = [
        self::ADMINISTRADOR => ['configuracion.gestionar', 'usuarios.gestionar', 'reportes.ver'],
        self::ADMINISTRATIVO => ['usuarios.gestionar', 'reportes.ver'],
        self::DOCENTE => ['docente.panel'],
        self::TUTOR => ['inscripciones.autogestion'],
    ];

    public const ETIQUETAS = [
        self::ADMINISTRADOR => 'Administrador',
        self::ADMINISTRATIVO => 'Administrativo',
        self::DOCENTE => 'Docente',
        self::TUTOR => 'Tutor / Padre',
    ];
}
