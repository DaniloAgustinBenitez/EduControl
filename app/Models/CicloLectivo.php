<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('ciclos_lectivos')]
#[Fillable(['anio', 'activo'])]
class CicloLectivo extends Model
{
    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public static function actual(): ?self
    {
        return static::where('activo', true)->orderByDesc('anio')->first();
    }
}
