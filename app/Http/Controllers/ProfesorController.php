<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Materia;
use App\Models\Nivel;
use App\Models\Profesor;
use App\Services\CuentaService;
use App\Support\Rol;
use Illuminate\Http\Request;

/** RF-02: ABM de profesores. RF-05 / RF-06: asignación de materias, cursos y niveles. */
class ProfesorController extends Controller
{
    public function __construct(private CuentaService $cuentas) {}

    public function index()
    {
        return view('profesores.index', [
            'profesores' => Profesor::with('user')->withCount(['materias', 'cursos', 'niveles'])->orderBy('apellido')->get(),
        ]);
    }

    public function create()
    {
        return view('profesores.form', ['profesor' => new Profesor]);
    }

    public function store(Request $request)
    {
        $profesor = $this->cuentas->guardar(new Profesor, $request->validate(CuentaService::reglas(null, 'profesores')), Rol::DOCENTE);

        return redirect()->route('profesores.asignaciones', $profesor)->with('ok', 'Profesor creado. Asignale sus materias y cursos.');
    }

    public function edit(Profesor $profesor)
    {
        return view('profesores.form', compact('profesor'));
    }

    public function update(Request $request, Profesor $profesor)
    {
        $this->cuentas->guardar($profesor, $request->validate(CuentaService::reglas($profesor, 'profesores')), Rol::DOCENTE);

        return redirect()->route('profesores.index')->with('ok', 'Profesor actualizado.');
    }

    public function destroy(Profesor $profesor)
    {
        $this->cuentas->eliminar($profesor);

        return redirect()->route('profesores.index')->with('ok', 'Profesor eliminado.');
    }

    public function asignaciones(Profesor $profesor)
    {
        return view('profesores.asignaciones', [
            'profesor' => $profesor->load(['materias', 'cursos', 'niveles']),
            'niveles' => Nivel::orderBy('nombre')->get(),
            'cursos' => Curso::with('nivel')->orderBy('nivel_id')->orderBy('nombre')->get(),
            'materias' => Materia::with('curso.nivel')->orderBy('curso_id')->orderBy('nombre')->get()->groupBy(fn ($m) => $m->curso->nombreCompleto()),
        ]);
    }

    public function guardarAsignaciones(Request $request, Profesor $profesor)
    {
        $datos = $request->validate([
            'niveles' => ['array'], 'niveles.*' => ['exists:niveles,id'],
            'cursos' => ['array'], 'cursos.*' => ['exists:cursos,id'],
            'materias' => ['array'], 'materias.*' => ['exists:materias,id'],
        ]);

        // Coherencia: un docente dicta en los cursos de sus materias y en los niveles de sus cursos.
        $materias = Materia::whereIn('id', $datos['materias'] ?? [])->get();
        $cursos = collect($datos['cursos'] ?? [])->merge($materias->pluck('curso_id'))->unique();
        $niveles = collect($datos['niveles'] ?? [])->merge(Curso::whereIn('id', $cursos)->pluck('nivel_id'))->unique();

        $profesor->materias()->sync($materias->pluck('id'));
        $profesor->cursos()->sync($cursos);
        $profesor->niveles()->sync($niveles);

        return redirect()->route('profesores.index')->with('ok', 'Asignaciones guardadas.');
    }
}
