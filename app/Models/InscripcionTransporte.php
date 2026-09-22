<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('inscripciones_transporte')]
#[Fillable(['alumno_id', 'ciclo_lectivo_id', 'recorrido_id'])]
class InscripcionTransporte extends Model
{
    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }

    public function cicloLectivo(): BelongsTo
    {
        return $this->belongsTo(CicloLectivo::class);
    }

    public function recorrido(): BelongsTo
    {
        return $this->belongsTo(Recorrido::class);
    }
}
