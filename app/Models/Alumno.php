<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('alumnos')]
#[Fillable(['legajo', 'dni', 'nombre', 'apellido', 'fecha_nacimiento'])]
class Alumno extends Model
{
    protected function casts(): array
    {
        return ['fecha_nacimiento' => 'date'];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function tutores(): BelongsToMany
    {
        return $this->belongsToMany(Tutor::class)->withPivot('parentesco');
    }

    public function inscripcionesCursado(): HasMany
    {
        return $this->hasMany(InscripcionCursado::class);
    }

    public function inscripcionesComedor(): HasMany
    {
        return $this->hasMany(InscripcionComedor::class);
    }

    public function inscripcionesTransporte(): HasMany
    {
        return $this->hasMany(InscripcionTransporte::class);
    }

    public function inscripcionesDeporte(): HasMany
    {
        return $this->hasMany(InscripcionDeporte::class);
    }

    public function nombreCompleto(): string
    {
        return $this->apellido.', '.$this->nombre;
    }
}
