<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use Illuminate\Http\Request;

/** HU4: el docente ve solo sus materias, cursos y niveles. */
class DocenteController extends Controller
{
    public function index(Request $request)
    {
        $profesor = $request->user()->profesor?->load(['materias.curso.nivel', 'cursos.nivel', 'cursos.horarios', 'niveles']);

        return view('docente.index', [
            'profesor' => $profesor,
            'materiasPorCurso' => $profesor?->materias->groupBy(fn ($m) => $m->curso->nombreCompleto()) ?? collect(),
        ]);
    }

    public function curso(Request $request, Curso $curso)
    {
        $profesor = $request->user()->profesor;
        abort_unless($profesor && $profesor->cursos()->whereKey($curso->id)->exists(), 403, 'Ese curso no está asignado a tu cuenta.');

        return view('docente.curso', [
            'curso' => $curso->load(['nivel', 'horarios', 'alumnos' => fn ($q) => $q->orderBy('apellido')->orderBy('nombre')]),
            'materias' => $profesor->materias()->where('curso_id', $curso->id)->orderBy('nombre')->get(),
        ]);
    }
}
