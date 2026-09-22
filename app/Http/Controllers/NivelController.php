<?php

namespace App\Http\Controllers;

use App\Models\Nivel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** RF-20: ABM de niveles educativos. */
class NivelController extends Controller
{
    public function index()
    {
        return view('niveles.index', ['niveles' => Nivel::withCount('cursos')->orderBy('nombre')->get()]);
    }

    public function create()
    {
        return view('niveles.form', ['nivel' => new Nivel]);
    }

    public function store(Request $request)
    {
        Nivel::create($this->validar($request));

        return redirect()->route('niveles.index')->with('ok', 'Nivel creado.');
    }

    public function edit(Nivel $nivel)
    {
        return view('niveles.form', compact('nivel'));
    }

    public function update(Request $request, Nivel $nivel)
    {
        $nivel->update($this->validar($request, $nivel));

        return redirect()->route('niveles.index')->with('ok', 'Nivel actualizado.');
    }

    public function destroy(Nivel $nivel)
    {
        return $this->eliminar($nivel, 'niveles.index', 'No se puede eliminar un nivel que tiene cursos.');
    }

    private function validar(Request $request, ?Nivel $nivel = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:60', Rule::unique('niveles')->ignore($nivel)],
        ]);
    }
}
