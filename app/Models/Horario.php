<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Table('horarios')]
#[Fillable(['dia', 'hora_inicio', 'hora_fin'])]
class Horario extends Model
{
    public const DIAS = [
        1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves',
        5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo',
    ];

    public function horarioable(): MorphTo
    {
        return $this->morphTo();
    }

    public function descripcion(): string
    {
        return self::DIAS[$this->dia].' '.substr($this->hora_inicio, 0, 5).' a '.substr($this->hora_fin, 0, 5);
    }
}
