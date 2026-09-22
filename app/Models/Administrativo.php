<?php

namespace App\Models;

use App\Models\Concerns\EsPersona;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('administrativos')]
#[Fillable(['user_id', 'dni', 'nombre', 'apellido', 'telefono'])]
class Administrativo extends Model
{
    use EsPersona;
}
