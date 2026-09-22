<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Deporte;
use App\Models\Horario;
use App\Services\SuperposicionHoraria;
use Illuminate\Http\Request;

/**
 * Horarios de deportes (RF-23) y de cursado por curso (tarea 3.7, RN-05).
 */
class HorarioController extends Controller
{
    private const TIPOS = ['curso' => Curso::class, 'deporte' => Deporte::class];

    public function index(string $tipo, int $id)
    {
        $dueno = $this->dueno($tipo, $id);

        return view('horarios.index', [
            'tipo' => $tipo,
            'dueno' => $dueno,
            'titulo' => $tipo === 'curso' ? 'Horario de cursado: '.$dueno->nombreCompleto() : 'Horarios de '.$dueno->nombre,
            'volver' => route($tipo === 'curso' ? 'cursos.index' : 'deportes.index'),
        ]);
    }

    public function store(Request $request, string $tipo, int $id)
    {
        $dueno = $this->dueno($tipo, $id);
        $datos = $request->validate([
            'dia' => ['required', 'integer', 'between:1,7'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
        ], ['hora_fin.after' => 'La hora de fin debe ser posterior a la de inicio.']);

        // Un mismo curso o deporte no puede tener dos franjas que se pisen.
        if (SuperposicionHoraria::conflictos([$datos], $dueno->horarios)) {
            return back()->withInput()->with('error', 'Esa franja se superpone con otra ya cargada.');
        }

        $dueno->horarios()->create($datos);

        return back()->with('ok', 'Horario agregado.');
    }

    public function destroy(Horario $horario)
    {
        $horario->delete();

        return back()->with('ok', 'Horario eliminado.');
    }

    private function dueno(string $tipo, int $id): Curso|Deporte
    {
        return self::TIPOS[$tipo]::with('horarios')->findOrFail($id);
    }
}
