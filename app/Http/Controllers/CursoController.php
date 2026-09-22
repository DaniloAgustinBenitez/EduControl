<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Nivel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** RF-21: ABM de cursos. RN-02: el nivel es obligatorio. */
class CursoController extends Controller
{
    public function index()
    {
        return view('cursos.index', [
            'cursos' => Curso::with('nivel')->withCount(['alumnos', 'materias', 'horarios'])->orderBy('nivel_id')->orderBy('nombre')->get(),
        ]);
    }

    public function create()
    {
        return view('cursos.form', ['curso' => new Curso, 'niveles' => Nivel::orderBy('nombre')->get()]);
    }

    public function store(Request $request)
    {
        $curso = Curso::create($this->validar($request));

        return redirect()->route('horarios.index', ['curso', $curso->id])->with('ok', 'Curso creado. Cargá ahora su horario de cursado.');
    }

    public function edit(Curso $curso)
    {
        return view('cursos.form', ['curso' => $curso, 'niveles' => Nivel::orderBy('nombre')->get()]);
    }

    public function update(Request $request, Curso $curso)
    {
        $curso->update($this->validar($request, $curso));

        return redirect()->route('cursos.index')->with('ok', 'Curso actualizado.');
    }

    public function destroy(Curso $curso)
    {
        return $this->eliminar($curso, 'cursos.index', 'No se puede eliminar un curso con inscripciones.',
            fn () => $curso->horarios()->delete());
    }

    private function validar(Request $request, ?Curso $curso = null): array
    {
        return $request->validate([
            'nivel_id' => ['required', 'exists:niveles,id'],
            'nombre' => [
                'required', 'string', 'max:60',
                Rule::unique('cursos')->where('nivel_id', $request->input('nivel_id'))->ignore($curso),
            ],
        ], [
            'nivel_id.required' => 'Todo curso debe pertenecer a un nivel.',
            'nombre.unique' => 'Ya existe un curso con ese nombre en el nivel elegido.',
        ]);
    }
}
