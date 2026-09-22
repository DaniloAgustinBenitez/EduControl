<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Table('materias')]
#[Fillable(['curso_id', 'nombre'])]
class Materia extends Model
{
    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function profesores(): BelongsToMany
    {
        return $this->belongsToMany(Profesor::class);
    }
}
