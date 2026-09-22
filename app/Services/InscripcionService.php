<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\Alumno;
use App\Models\CicloLectivo;
use App\Models\Curso;
use App\Models\Deporte;
use App\Models\Horario;
use App\Models\InscripcionComedor;
use App\Models\InscripcionCursado;
use App\Models\InscripcionDeporte;
use App\Models\InscripcionTransporte;
use App\Models\Recorrido;
use Illuminate\Support\Facades\DB;

/**
 * Punto único de entrada para inscribir y dar de baja servicios (interfaz acordada en la tarea 5.1).
 * Toda regla de negocio se valida acá; los controladores solo traducen la excepción a un mensaje.
 */
class InscripcionService
{
    public const MAX_DEPORTES = 2; // RN-03

    public const SERVICIOS = ['cursado', 'comedor', 'transporte', 'deporte'];

    public function inscribirCursado(Alumno $alumno, CicloLectivo $ciclo): InscripcionCursado
    {
        if (! $alumno->curso_id) {
            throw new ReglaNegocioException('El alumno no tiene un curso asignado. Pedí a la administración que lo asigne.', 'RN-01');
        }

        if (InscripcionCursado::where(['alumno_id' => $alumno->id, 'ciclo_lectivo_id' => $ciclo->id])->exists()) {
            throw new ReglaNegocioException("El alumno ya está inscripto al cursado del ciclo {$ciclo->anio}.", 'RN-01');
        }

        return InscripcionCursado::create([
            'alumno_id' => $alumno->id,
            'ciclo_lectivo_id' => $ciclo->id,
            'curso_id' => $alumno->curso_id,
        ]);
    }

    public function inscribirComedor(Alumno $alumno, CicloLectivo $ciclo): InscripcionComedor
    {
        if (InscripcionComedor::where(['alumno_id' => $alumno->id, 'ciclo_lectivo_id' => $ciclo->id])->exists()) {
            throw new ReglaNegocioException('El alumno ya está inscripto al comedor.');
        }

        return InscripcionComedor::create(['alumno_id' => $alumno->id, 'ciclo_lectivo_id' => $ciclo->id]);
    }

    public function inscribirTransporte(Alumno $alumno, CicloLectivo $ciclo, Recorrido $recorrido): InscripcionTransporte
    {
        $actual = InscripcionTransporte::with('recorrido')
            ->where(['alumno_id' => $alumno->id, 'ciclo_lectivo_id' => $ciclo->id])
            ->first();

        if ($actual) {
            throw new ReglaNegocioException(
                "El alumno ya tiene asignado el recorrido {$actual->recorrido->nombre}. Solo se permite un recorrido: dalo de baja para elegir otro.",
                'RN-06'
            );
        }

        return InscripcionTransporte::create([
            'alumno_id' => $alumno->id,
            'ciclo_lectivo_id' => $ciclo->id,
            'recorrido_id' => $recorrido->id,
        ]);
    }

    public function inscribirDeporte(Alumno $alumno, CicloLectivo $ciclo, Deporte $deporte): InscripcionDeporte
    {
        return DB::transaction(function () use ($alumno, $ciclo, $deporte) {
            $actuales = InscripcionDeporte::with('deporte.horarios')
                ->where(['alumno_id' => $alumno->id, 'ciclo_lectivo_id' => $ciclo->id])
                ->lockForUpdate()
                ->get();

            if ($actuales->contains('deporte_id', $deporte->id)) {
                throw new ReglaNegocioException("El alumno ya está inscripto a {$deporte->nombre}.");
            }

            if ($actuales->count() >= self::MAX_DEPORTES) {
                throw new ReglaNegocioException('Se permite un máximo de '.self::MAX_DEPORTES.' deportes por alumno.', 'RN-03');
            }

            $nuevos = $deporte->horarios;

            foreach ($actuales as $inscripcion) {
                if ($choque = SuperposicionHoraria::conflictos($nuevos, $inscripcion->deporte->horarios)) {
                    throw new ReglaNegocioException(
                        "{$deporte->nombre} se superpone con {$inscripcion->deporte->nombre} ({$this->describir($choque)}).",
                        'RN-04'
                    );
                }
            }

            $cursado = $this->horariosDeCursado($alumno, $ciclo);
            if ($choque = SuperposicionHoraria::conflictos($nuevos, $cursado)) {
                throw new ReglaNegocioException(
                    "{$deporte->nombre} se superpone con el horario de cursado ({$this->describir($choque)}).",
                    'RN-05'
                );
            }

            return InscripcionDeporte::create([
                'alumno_id' => $alumno->id,
                'ciclo_lectivo_id' => $ciclo->id,
                'deporte_id' => $deporte->id,
            ]);
        });
    }

    /**
     * Baja de una inscripción activa (RF-12). Devuelve false si no pertenece al alumno.
     */
    public function baja(Alumno $alumno, string $servicio, int $inscripcionId): bool
    {
        return (bool) $this->modelo($servicio)::where('id', $inscripcionId)
            ->where('alumno_id', $alumno->id)
            ->delete();
    }

    /**
     * Estado de cada servicio del alumno en el ciclo (pantalla de autogestión, RF-12).
     */
    public function estado(Alumno $alumno, CicloLectivo $ciclo): array
    {
        $filtro = ['alumno_id' => $alumno->id, 'ciclo_lectivo_id' => $ciclo->id];

        return [
            'cursado' => InscripcionCursado::with('curso.nivel')->where($filtro)->first(),
            'comedor' => InscripcionComedor::where($filtro)->first(),
            'transporte' => InscripcionTransporte::with('recorrido')->where($filtro)->first(),
            'deportes' => InscripcionDeporte::with('deporte.horarios')->where($filtro)->get(),
        ];
    }

    /**
     * Horario de cursado que rige para el alumno: el curso de su inscripción del ciclo, o su curso asignado.
     */
    private function horariosDeCursado(Alumno $alumno, CicloLectivo $ciclo)
    {
        $cursoId = InscripcionCursado::where(['alumno_id' => $alumno->id, 'ciclo_lectivo_id' => $ciclo->id])
            ->value('curso_id') ?? $alumno->curso_id;

        return $cursoId ? Horario::where('horarioable_type', Curso::class)->where('horarioable_id', $cursoId)->get() : collect();
    }

    private function describir(array $conflictos): string
    {
        [$nuevo] = $conflictos[0];

        return $nuevo instanceof Horario ? $nuevo->descripcion() : Horario::DIAS[$nuevo['dia']];
    }

    private function modelo(string $servicio): string
    {
        return match ($servicio) {
            'cursado' => InscripcionCursado::class,
            'comedor' => InscripcionComedor::class,
            'transporte' => InscripcionTransporte::class,
            'deporte' => InscripcionDeporte::class,
        };
    }
}
