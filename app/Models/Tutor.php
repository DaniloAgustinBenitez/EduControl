<?php

namespace App\Models;

use App\Models\Concerns\EsPersona;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Table('tutores')]
#[Fillable(['user_id', 'dni', 'nombre', 'apellido', 'telefono'])]
class Tutor extends Model
{
    use EsPersona;

    public function alumnos(): BelongsToMany
    {
        return $this->belongsToMany(Alumno::class)->withPivot('parentesco');
    }
}
