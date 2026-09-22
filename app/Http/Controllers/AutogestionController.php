<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\Alumno;
use App\Models\CicloLectivo;
use App\Models\Deporte;
use App\Models\Recorrido;
use App\Services\InscripcionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Autogestión del padre/tutor (HU5 a HU8): ve a sus hijos, los inscribe y da de baja servicios.
 * Toda acción pasa por AlumnoPolicy::operar (RN-07) y las reglas viven en InscripcionService.
 */
class AutogestionController extends Controller
{
    public function __construct(private InscripcionService $inscripciones) {}

    public function index(Request $request)
    {
        $tutor = $request->user()->tutor;
        $ciclo = CicloLectivo::actual();
        $hijos = $tutor ? $tutor->alumnos()->with('curso.nivel')->orderBy('nombre')->get() : collect();

        return view('autogestion.index', [
            'ciclo' => $ciclo,
            'hijos' => $hijos->map(fn (Alumno $alumno) => [
                'alumno' => $alumno,
                'estado' => $ciclo ? $this->inscripciones->estado($alumno, $ciclo) : null,
            ]),
        ]);
    }

    public function show(Alumno $alumno)
    {
        Gate::authorize('operar', $alumno);
        $ciclo = CicloLectivo::actual();

        return view('autogestion.show', [
            'alumno' => $alumno->load('curso.nivel', 'curso.horarios'),
            'ciclo' => $ciclo,
            'estado' => $ciclo ? $this->inscripciones->estado($alumno, $ciclo) : null,
            'recorridos' => Recorrido::orderBy('nombre')->get(),
            'deportes' => Deporte::with('horarios')->orderBy('nombre')->get(),
            'maxDeportes' => InscripcionService::MAX_DEPORTES,
        ]);
    }

    public function inscribir(Request $request, Alumno $alumno, string $servicio)
    {
        Gate::authorize('operar', $alumno);

        if (! $ciclo = CicloLectivo::actual()) {
            return back()->with('error', 'No hay un ciclo lectivo abierto para inscripciones.');
        }

        try {
            match ($servicio) {
                'cursado' => $this->inscripciones->inscribirCursado($alumno, $ciclo),
                'comedor' => $this->inscripciones->inscribirComedor($alumno, $ciclo),
                'transporte' => $this->inscripciones->inscribirTransporte($alumno, $ciclo,
                    Recorrido::findOrFail($request->validate(['recorrido_id' => ['required', 'exists:recorridos,id']])['recorrido_id'])),
                'deporte' => $this->inscripciones->inscribirDeporte($alumno, $ciclo,
                    Deporte::findOrFail($request->validate(['deporte_id' => ['required', 'exists:deportes,id']])['deporte_id'])),
            };
        } catch (ReglaNegocioException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('ok', 'Inscripción registrada.');
    }

    public function baja(Alumno $alumno, string $servicio, int $inscripcion)
    {
        Gate::authorize('operar', $alumno);

        if (! $this->inscripciones->baja($alumno, $servicio, $inscripcion)) {
            abort(404);
        }

        return back()->with('ok', 'Se dio de baja la inscripción.');
    }
}
