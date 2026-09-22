<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\CicloLectivo;
use App\Models\Curso;
use App\Models\InscripcionComedor;
use App\Models\InscripcionDeporte;
use App\Models\InscripcionTransporte;
use App\Models\Profesor;
use Illuminate\Http\Request;

class InicioController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        // Docentes y tutores tienen su propia pantalla de inicio.
        if ($user->can('docente.panel')) {
            return redirect()->route('docente.index');
        }
        if ($user->can('inscripciones.autogestion')) {
            return redirect()->route('autogestion.index');
        }

        $ciclo = CicloLectivo::actual();
        $filtro = fn ($q) => $q->where('ciclo_lectivo_id', $ciclo?->id);

        return view('inicio', [
            'ciclo' => $ciclo,
            'totales' => [
                'Alumnos' => Alumno::count(),
                'Profesores' => Profesor::count(),
                'Cursos' => Curso::count(),
                'En comedor' => InscripcionComedor::where($filtro)->count(),
                'En transporte' => InscripcionTransporte::where($filtro)->count(),
                'Inscripciones a deportes' => InscripcionDeporte::where($filtro)->count(),
            ],
        ]);
    }
}
