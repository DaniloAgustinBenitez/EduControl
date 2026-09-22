<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait EsPersona
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function nombreCompleto(): string
    {
        return $this->apellido.', '.$this->nombre;
    }
}
