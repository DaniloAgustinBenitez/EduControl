<?php

namespace App\Http\Controllers;

use App\Models\Recorrido;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** RF-24: ABM de los recorridos de transporte. */
class RecorridoController extends Controller
{
    public function index()
    {
        return view('recorridos.index', ['recorridos' => Recorrido::withCount('inscripciones')->orderBy('nombre')->get()]);
    }

    public function create()
    {
        return view('recorridos.form', ['recorrido' => new Recorrido]);
    }

    public function store(Request $request)
    {
        Recorrido::create($this->validar($request));

        return redirect()->route('recorridos.index')->with('ok', 'Recorrido creado.');
    }

    public function edit(Recorrido $recorrido)
    {
        return view('recorridos.form', compact('recorrido'));
    }

    public function update(Request $request, Recorrido $recorrido)
    {
        $recorrido->update($this->validar($request, $recorrido));

        return redirect()->route('recorridos.index')->with('ok', 'Recorrido actualizado.');
    }

    public function destroy(Recorrido $recorrido)
    {
        return $this->eliminar($recorrido, 'recorridos.index', 'No se puede eliminar un recorrido con alumnos inscriptos.');
    }

    private function validar(Request $request, ?Recorrido $recorrido = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:60', Rule::unique('recorridos')->ignore($recorrido)],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
