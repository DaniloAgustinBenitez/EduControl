<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Table('deportes')]
#[Fillable(['nombre', 'descripcion'])]
class Deporte extends Model
{
    public function horarios(): MorphMany
    {
        return $this->morphMany(Horario::class, 'horarioable')->orderBy('dia')->orderBy('hora_inicio');
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(InscripcionDeporte::class);
    }
}
