<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

abstract class Controller
{
    /**
     * Elimina un registro y, si otro lo referencia (clave foránea), avisa en lugar de fallar.
     * $antes corre en la misma transacción (por ejemplo, borrar sus horarios).
     */
    protected function eliminar(Model $modelo, string $ruta, string $enUso, ?callable $antes = null): RedirectResponse
    {
        try {
            DB::transaction(function () use ($modelo, $antes) {
                $antes && $antes();
                $modelo->delete();
            });
        } catch (QueryException) {
            return redirect()->route($ruta)->with('error', $enUso);
        }

        return redirect()->route($ruta)->with('ok', 'Registro eliminado.');
    }
}
