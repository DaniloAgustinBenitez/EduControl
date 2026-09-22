<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Curso;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** RF-01: ABM de alumnos. RF-07 / RN-01: asignación a un único curso. */
class AlumnoController extends Controller
{
    public function index()
    {
        return view('alumnos.index', [
            'alumnos' => Alumno::with(['curso.nivel', 'tutores'])->orderBy('apellido')->orderBy('nombre')->get(),
            'cursos' => $this->cursos(),
        ]);
    }

    public function create()
    {
        return view('alumnos.form', ['alumno' => new Alumno, 'cursos' => $this->cursos()]);
    }

    public function store(Request $request)
    {
        $alumno = new Alumno($this->validar($request));
        $alumno->curso_id = $request->validate(['curso_id' => ['nullable', 'exists:cursos,id']])['curso_id'] ?? null;
        $alumno->save();

        return redirect()->route('alumnos.index')->with('ok', 'Alumno creado.');
    }

    public function edit(Alumno $alumno)
    {
        return view('alumnos.form', ['alumno' => $alumno, 'cursos' => $this->cursos()]);
    }

    public function update(Request $request, Alumno $alumno)
    {
        $alumno->update($this->validar($request, $alumno));

        return redirect()->route('alumnos.index')->with('ok', 'Alumno actualizado.');
    }

    public function destroy(Alumno $alumno)
    {
        return $this->eliminar($alumno, 'alumnos.index', 'No se pudo eliminar el alumno.');
    }

    /**
     * RN-01: un alumno pertenece a un solo curso. Para cambiarlo hay que quitar primero el actual.
     */
    public function asignarCurso(Request $request, Alumno $alumno)
    {
        $request->validate(['curso_id' => ['nullable', 'exists:cursos,id']]);
        $nuevo = $request->input('curso_id') ?: null;

        if ($nuevo && $alumno->curso_id && $alumno->curso_id != $nuevo) {
            return back()->with('error', "{$alumno->nombreCompleto()} ya está asignado a {$alumno->curso->nombreCompleto()}. Un alumno solo puede tener un curso: quitalo primero.");
        }

        $alumno->curso()->associate($nuevo)->save();

        return back()->with('ok', $nuevo ? 'Curso asignado.' : 'Se quitó el curso del alumno.');
    }

    private function cursos()
    {
        return Curso::with('nivel')->orderBy('nivel_id')->orderBy('nombre')->get();
    }

    private function validar(Request $request, ?Alumno $alumno = null): array
    {
        return $request->validate([
            'legajo' => ['required', 'string', 'max:20', Rule::unique('alumnos')->ignore($alumno)],
            'dni' => ['required', 'string', 'max:15', Rule::unique('alumnos')->ignore($alumno)],
            'nombre' => ['required', 'string', 'max:60'],
            'apellido' => ['required', 'string', 'max:60'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
        ], [
            'legajo.unique' => 'Ya existe un alumno con ese legajo.',
            'dni.unique' => 'Ya existe un alumno con ese DNI.',
        ]);
    }
}
