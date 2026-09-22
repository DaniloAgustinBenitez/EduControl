<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('recorridos')]
#[Fillable(['nombre', 'descripcion'])]
class Recorrido extends Model
{
    public function inscripciones(): HasMany
    {
        return $this->hasMany(InscripcionTransporte::class);
    }
}
