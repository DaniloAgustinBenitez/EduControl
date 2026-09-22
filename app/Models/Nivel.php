<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('niveles')]
#[Fillable(['nombre'])]
class Nivel extends Model
{
    public function cursos(): HasMany
    {
        return $this->hasMany(Curso::class);
    }

    public function profesores(): BelongsToMany
    {
        return $this->belongsToMany(Profesor::class);
    }
}
