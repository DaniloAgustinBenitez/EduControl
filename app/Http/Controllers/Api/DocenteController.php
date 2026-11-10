<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AlumnoNominaResource;
use App\Http\Resources\CursoDocenteResource;
use App\Http\Resources\MateriaResource;
use App\Models\Curso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** HU4 desde la app (RF-28): el docente ve solo sus cursos, sus materias y la nómina. */
class DocenteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $profesor = $request->user()->profesor;

        if (! $profesor) {
            return response()->json(['cursos' => []]);
        }

        $materias = $profesor->materias()->orderBy('nombre')->get()->groupBy('curso_id');
        $cursos = $profesor->cursos()->with(['nivel', 'horarios'])->orderBy('nivel_id')->orderBy('nombre')->get();

        // En cada curso, solo las materias que dicta este docente.
        $cursos->each(fn (Curso $curso) => $curso->setRelation('materias', $materias->get($curso->id, collect())));

        return response()->json(['cursos' => CursoDocenteResource::collection($cursos)]);
    }

    public function curso(Request $request, Curso $curso): JsonResponse
    {
        $profesor = $request->user()->profesor;
        abort_unless($profesor && $profesor->cursos()->whereKey($curso->id)->exists(), 403, 'Ese curso no está asignado a tu cuenta.');

        $curso->load(['nivel', 'horarios', 'alumnos' => fn ($q) => $q->orderBy('apellido')->orderBy('nombre')]);

        return response()->json([
            'curso' => new CursoDocenteResource($curso),
            'materias' => MateriaResource::collection($profesor->materias()->where('curso_id', $curso->id)->orderBy('nombre')->get()),
            'alumnos' => AlumnoNominaResource::collection($curso->alumnos),
        ]);
    }
}
