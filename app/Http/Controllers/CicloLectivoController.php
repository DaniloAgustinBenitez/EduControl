<?php

namespace App\Http\Controllers;

use App\Models\CicloLectivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** RF-08: ciclos lectivos. Solo uno puede estar activo para inscripciones. */
class CicloLectivoController extends Controller
{
    public function index()
    {
        return view('ciclos.index', ['ciclos' => CicloLectivo::orderByDesc('anio')->get()]);
    }

    public function create()
    {
        return view('ciclos.form', ['ciclo' => new CicloLectivo(['anio' => now()->year])]);
    }

    public function store(Request $request)
    {
        $this->guardar(new CicloLectivo, $request);

        return redirect()->route('ciclos.index')->with('ok', 'Ciclo lectivo creado.');
    }

    public function edit(CicloLectivo $ciclo)
    {
        return view('ciclos.form', compact('ciclo'));
    }

    public function update(Request $request, CicloLectivo $ciclo)
    {
        $this->guardar($ciclo, $request);

        return redirect()->route('ciclos.index')->with('ok', 'Ciclo lectivo actualizado.');
    }

    public function destroy(CicloLectivo $ciclo)
    {
        return $this->eliminar($ciclo, 'ciclos.index', 'No se puede eliminar un ciclo con inscripciones.');
    }

    private function guardar(CicloLectivo $ciclo, Request $request): void
    {
        $datos = $request->validate([
            'anio' => ['required', 'integer', 'between:2000,2100', Rule::unique('ciclos_lectivos')->ignore($ciclo)],
        ]);
        $datos['activo'] = $request->boolean('activo');

        DB::transaction(function () use ($ciclo, $datos) {
            if ($datos['activo']) {
                CicloLectivo::where('id', '!=', $ciclo->id ?? 0)->update(['activo' => false]);
            }
            $ciclo->fill($datos)->save();
        });
    }
}
