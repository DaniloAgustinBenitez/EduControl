<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Table('cursos')]
#[Fillable(['nivel_id', 'nombre'])]
class Curso extends Model
{
    public function nivel(): BelongsTo
    {
        return $this->belongsTo(Nivel::class);
    }

    public function materias(): HasMany
    {
        return $this->hasMany(Materia::class);
    }

    public function alumnos(): HasMany
    {
        return $this->hasMany(Alumno::class);
    }

    public function profesores(): BelongsToMany
    {
        return $this->belongsToMany(Profesor::class);
    }

    /** Horarios de cursado (RN-05). */
    public function horarios(): MorphMany
    {
        return $this->morphMany(Horario::class, 'horarioable')->orderBy('dia')->orderBy('hora_inicio');
    }

    public function nombreCompleto(): string
    {
        return $this->nombre.' — '.$this->nivel->nombre;
    }
}
