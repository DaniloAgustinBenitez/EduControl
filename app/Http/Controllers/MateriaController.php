<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** RF-22: ABM de materias, asociadas a su curso. */
class MateriaController extends Controller
{
    public function index()
    {
        return view('materias.index', [
            'materias' => Materia::with(['curso.nivel', 'profesores'])->orderBy('curso_id')->orderBy('nombre')->get(),
        ]);
    }

    public function create()
    {
        return view('materias.form', ['materia' => new Materia, 'cursos' => $this->cursos()]);
    }

    public function store(Request $request)
    {
        Materia::create($this->validar($request));

        return redirect()->route('materias.index')->with('ok', 'Materia creada.');
    }

    public function edit(Materia $materia)
    {
        return view('materias.form', ['materia' => $materia, 'cursos' => $this->cursos()]);
    }

    public function update(Request $request, Materia $materia)
    {
        $materia->update($this->validar($request, $materia));

        return redirect()->route('materias.index')->with('ok', 'Materia actualizada.');
    }

    public function destroy(Materia $materia)
    {
        return $this->eliminar($materia, 'materias.index', 'No se pudo eliminar la materia.');
    }

    private function cursos()
    {
        return Curso::with('nivel')->orderBy('nivel_id')->orderBy('nombre')->get();
    }

    private function validar(Request $request, ?Materia $materia = null): array
    {
        return $request->validate([
            'curso_id' => ['required', 'exists:cursos,id'],
            'nombre' => [
                'required', 'string', 'max:80',
                Rule::unique('materias')->where('curso_id', $request->input('curso_id'))->ignore($materia),
            ],
        ], ['nombre.unique' => 'Ese curso ya tiene una materia con ese nombre.']);
    }
}
