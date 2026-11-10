<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ReglaNegocioException;
use App\Http\Controllers\Controller;
use App\Http\Resources\AlumnoDetalleResource;
use App\Http\Resources\AlumnoResumenResource;
use App\Http\Resources\CicloResource;
use App\Http\Resources\DeporteResource;
use App\Http\Resources\EstadoResource;
use App\Http\Resources\RecorridoResource;
use App\Models\Alumno;
use App\Models\CicloLectivo;
use App\Models\Deporte;
use App\Models\Recorrido;
use App\Services\InscripcionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Autogestión del tutor desde la app (RF-27): mismo flujo que la web (HU5 a HU8).
 * Toda acción sobre un alumno pasa por AlumnoPolicy::operar (RN-07) y las reglas viven en
 * InscripcionService; una ReglaNegocioException se responde como 422 en bootstrap/app.php.
 */
class AutogestionController extends Controller
{
    public function __construct(private InscripcionService $inscripciones) {}

    public function index(Request $request): JsonResponse
    {
        $ciclo = CicloLectivo::actual();

        return response()->json([
            'ciclo' => $this->ciclo($ciclo),
            'hijos' => $this->inscripciones->hijosConEstado($request->user()->tutor, $ciclo)
                ->map(fn (array $hijo) => new AlumnoResumenResource($hijo['alumno'], $hijo['estado']))
                ->values(),
        ]);
    }

    public function show(Alumno $alumno): JsonResponse
    {
        Gate::authorize('operar', $alumno);
        $ciclo = CicloLectivo::actual();

        return response()->json([
            'ciclo' => $this->ciclo($ciclo),
            'alumno' => new AlumnoDetalleResource(
                $alumno->load('curso.nivel', 'curso.horarios'),
                $ciclo ? $this->inscripciones->estado($alumno, $ciclo) : null
            ),
        ]);
    }

    public function catalogo(): JsonResponse
    {
        return response()->json([
            'ciclo' => $this->ciclo(CicloLectivo::actual()),
            'max_deportes' => InscripcionService::MAX_DEPORTES,
            'deportes' => DeporteResource::collection(Deporte::with('horarios')->orderBy('nombre')->get()),
            'recorridos' => RecorridoResource::collection(Recorrido::orderBy('nombre')->get()),
        ]);
    }

    public function inscribir(Request $request, Alumno $alumno, string $servicio): JsonResponse
    {
        Gate::authorize('operar', $alumno);

        if (! $ciclo = CicloLectivo::actual()) {
            throw new ReglaNegocioException('No hay un ciclo lectivo abierto para inscripciones.');
        }

        match ($servicio) {
            'cursado' => $this->inscripciones->inscribirCursado($alumno, $ciclo),
            'comedor' => $this->inscripciones->inscribirComedor($alumno, $ciclo),
            'transporte' => $this->inscripciones->inscribirTransporte($alumno, $ciclo,
                Recorrido::findOrFail($request->validate(['recorrido_id' => ['required', 'exists:recorridos,id']])['recorrido_id'])),
            'deporte' => $this->inscripciones->inscribirDeporte($alumno, $ciclo,
                Deporte::findOrFail($request->validate(['deporte_id' => ['required', 'exists:deportes,id']])['deporte_id'])),
        };

        return response()->json([
            'message' => 'Inscripción registrada.',
            'estado' => new EstadoResource($this->inscripciones->estado($alumno, $ciclo)),
        ], 201);
    }

    /** RF-12: si la inscripción no es de ese alumno responde 404, aunque se cambie el id en la URL. */
    public function baja(Alumno $alumno, string $servicio, int $inscripcion): JsonResponse
    {
        Gate::authorize('operar', $alumno);

        if (! $this->inscripciones->baja($alumno, $servicio, $inscripcion)) {
            abort(404);
        }

        $ciclo = CicloLectivo::actual();

        return response()->json([
            'message' => 'Se dio de baja la inscripción.',
            'estado' => $ciclo ? new EstadoResource($this->inscripciones->estado($alumno, $ciclo)) : null,
        ]);
    }

    private function ciclo(?CicloLectivo $ciclo): ?CicloResource
    {
        return $ciclo ? new CicloResource($ciclo) : null;
    }
}
