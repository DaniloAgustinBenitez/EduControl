<?php

namespace App\Models;

use App\Models\Concerns\EsPersona;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Table('profesores')]
#[Fillable(['user_id', 'dni', 'nombre', 'apellido', 'telefono'])]
class Profesor extends Model
{
    use EsPersona;

    public function materias(): BelongsToMany
    {
        return $this->belongsToMany(Materia::class);
    }

    public function cursos(): BelongsToMany
    {
        return $this->belongsToMany(Curso::class);
    }

    public function niveles(): BelongsToMany
    {
        return $this->belongsToMany(Nivel::class);
    }
}
