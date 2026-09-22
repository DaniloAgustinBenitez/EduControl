<?php

namespace App\Http\Controllers;

use App\Models\Deporte;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** RF-23: ABM de deportes. Los horarios se cargan desde HorarioController. */
class DeporteController extends Controller
{
    public function index()
    {
        return view('deportes.index', [
            'deportes' => Deporte::with('horarios')->withCount('inscripciones')->orderBy('nombre')->get(),
        ]);
    }

    public function create()
    {
        return view('deportes.form', ['deporte' => new Deporte]);
    }

    public function store(Request $request)
    {
        $deporte = Deporte::create($this->validar($request));

        return redirect()->route('horarios.index', ['deporte', $deporte->id])->with('ok', 'Deporte creado. Cargá ahora sus horarios.');
    }

    public function edit(Deporte $deporte)
    {
        return view('deportes.form', compact('deporte'));
    }

    public function update(Request $request, Deporte $deporte)
    {
        $deporte->update($this->validar($request, $deporte));

        return redirect()->route('deportes.index')->with('ok', 'Deporte actualizado.');
    }

    public function destroy(Deporte $deporte)
    {
        return $this->eliminar($deporte, 'deportes.index', 'No se puede eliminar un deporte con alumnos inscriptos.',
            fn () => $deporte->horarios()->delete());
    }

    private function validar(Request $request, ?Deporte $deporte = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:60', Rule::unique('deportes')->ignore($deporte)],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
