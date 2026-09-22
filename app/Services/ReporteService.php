<?php

namespace App\Services;

use App\Models\Alumno;
use App\Models\CicloLectivo;
use App\Models\Curso;
use App\Models\Deporte;
use App\Models\InscripcionDeporte;
use App\Models\InscripcionTransporte;
use App\Models\Materia;
use App\Models\Profesor;
use App\Models\Recorrido;
use Illuminate\Support\Collection;

/**
 * Arma los seis reportes consolidados (RF-13 a RF-18) como título + columnas + filas,
 * para mostrarlos en pantalla o exportarlos a PDF / planilla (RF-19) con el mismo contenido.
 */
class ReporteService
{
    /**
     * @return array{titulo: string, columnas: array<int, string>, filas: Collection}
     */
    public function generar(string $tipo, CicloLectivo $ciclo, ?int $filtro): array
    {
        return match ($tipo) {
            'alumno' => $this->porAlumno($ciclo, $filtro),
            'docente' => $this->porDocente($filtro),
            'curso' => $this->porCurso($ciclo, $filtro),
            'materia' => $this->porMateria($filtro),
            'deporte' => $this->porDeporte($ciclo, $filtro),
            'recorrido' => $this->porRecorrido($ciclo, $filtro),
        };
    }

    /** Opciones del filtro de cada reporte: [etiqueta del filtro, id => nombre]. */
    public function opcionesFiltro(string $tipo): array
    {
        return match ($tipo) {
            'alumno', 'curso' => ['Curso', Curso::with('nivel')->orderBy('nivel_id')->orderBy('nombre')->get()->mapWithKeys(fn ($c) => [$c->id => $c->nombreCompleto()])],
            'docente' => ['Docente', Profesor::orderBy('apellido')->get()->mapWithKeys(fn ($p) => [$p->id => $p->nombreCompleto()])],
            'materia' => ['Materia (para ver la nómina de alumnos)', Materia::with('curso.nivel')->get()
                ->sortBy(fn ($m) => [$m->curso->nivel_id, $m->curso->nombre, $m->nombre])
                ->mapWithKeys(fn ($m) => [$m->id => $m->nombre.' — '.$m->curso->nombreCompleto()])],
            'deporte' => ['Deporte', Deporte::orderBy('nombre')->pluck('nombre', 'id')],
            'recorrido' => ['Recorrido', Recorrido::orderBy('nombre')->pluck('nombre', 'id')],
        };
    }

    /** RF-13: situación de cada alumno en todos los servicios. */
    private function porAlumno(CicloLectivo $ciclo, ?int $cursoId): array
    {
        $filas = $this->alumnosConServicios($ciclo, $cursoId)->map(fn (Alumno $a) => [
            $a->legajo,
            $a->nombreCompleto(),
            $a->dni,
            trim($a->telefono.($a->email ? ' · '.$a->email : '')) ?: '—',
            $a->curso?->nombreCompleto() ?? '—',
            $a->curso?->materias->pluck('nombre')->join(', ') ?: '—',
            ...$this->servicios($a),
        ]);

        return $this->armar("Reporte por alumno — ciclo {$ciclo->anio}",
            ['Legajo', 'Alumno', 'DNI', 'Contacto', 'Curso', 'Materias', 'Cursado', 'Comedor', 'Transporte', 'Deportes'], $filas);
    }

    /** RF-14: niveles, cursos y materias de cada docente. */
    private function porDocente(?int $profesorId): array
    {
        $filas = Profesor::with(['niveles', 'cursos.nivel', 'materias.curso'])
            ->when($profesorId, fn ($q) => $q->whereKey($profesorId))
            ->orderBy('apellido')->orderBy('nombre')->get()
            ->map(fn (Profesor $p) => [
                $p->nombreCompleto(),
                $p->dni,
                $p->niveles->pluck('nombre')->join(', ') ?: '—',
                $p->cursos->map->nombreCompleto()->join('; ') ?: '—',
                $p->materias->map(fn ($m) => $m->nombre.' ('.$m->curso->nombre.')')->join('; ') ?: '—',
            ]);

        return $this->armar('Reporte por docente', ['Docente', 'DNI', 'Niveles', 'Cursos', 'Materias'], $filas);
    }

    /** RF-15: alumnos de cada curso con sus servicios. */
    private function porCurso(CicloLectivo $ciclo, ?int $cursoId): array
    {
        $filas = $this->alumnosConServicios($ciclo, $cursoId, soloConCurso: true)
            ->sortBy(fn (Alumno $a) => [$a->curso->nivel_id, $a->curso->nombre, $a->apellido])
            ->map(fn (Alumno $a) => [
                $a->curso->nombre,
                $a->curso->nivel->nombre,
                $a->legajo,
                $a->nombreCompleto(),
                ...$this->servicios($a),
            ])->values();

        return $this->armar("Reporte por curso — ciclo {$ciclo->anio}",
            ['Curso', 'Nivel', 'Legajo', 'Alumno', 'Cursado', 'Comedor', 'Transporte', 'Deportes'], $filas);
    }

    /**
     * RF-16: materias con su docente a cargo y sus alumnos.
     * Sin filtro devuelve el resumen por materia; al elegir una, la nómina alumno por alumno.
     */
    private function porMateria(?int $materiaId): array
    {
        if ($materiaId) {
            $materia = Materia::with(['curso.nivel', 'profesores', 'curso.alumnos' => fn ($q) => $q->orderBy('apellido')->orderBy('nombre')])
                ->findOrFail($materiaId);
            $docentes = $materia->profesores->map->nombreCompleto()->join('; ') ?: 'Sin docente';

            $filas = $materia->curso->alumnos->map(fn (Alumno $a) => [
                $materia->nombre,
                $materia->curso->nombreCompleto(),
                $docentes,
                $a->legajo,
                $a->nombreCompleto(),
            ]);

            return $this->armar("Reporte de {$materia->nombre} — {$materia->curso->nombreCompleto()}",
                ['Materia', 'Curso', 'Docentes', 'Legajo', 'Alumno'], $filas);
        }

        $filas = Materia::with(['curso' => fn ($q) => $q->withCount('alumnos'), 'curso.nivel', 'profesores'])
            ->get()
            ->sortBy(fn ($m) => [$m->curso->nivel_id, $m->curso->nombre, $m->nombre])
            ->map(fn (Materia $m) => [
                $m->nombre,
                $m->curso->nombre,
                $m->curso->nivel->nombre,
                $m->profesores->map->nombreCompleto()->join('; ') ?: 'Sin docente',
                $m->curso->alumnos_count,
            ])->values();

        return $this->armar('Reporte por materia', ['Materia', 'Curso', 'Nivel', 'Docentes', 'Alumnos'], $filas);
    }

    /** RF-17: inscriptos por deporte. */
    private function porDeporte(CicloLectivo $ciclo, ?int $deporteId): array
    {
        $filas = InscripcionDeporte::with(['deporte.horarios', 'alumno.curso.nivel'])
            ->where('ciclo_lectivo_id', $ciclo->id)
            ->when($deporteId, fn ($q) => $q->where('deporte_id', $deporteId))
            ->get()
            ->sortBy(fn ($i) => [$i->deporte->nombre, $i->alumno->apellido])
            ->map(fn (InscripcionDeporte $i) => [
                $i->deporte->nombre,
                $i->deporte->horarios->map->descripcion()->join(' · '),
                $i->alumno->legajo,
                $i->alumno->nombreCompleto(),
                $i->alumno->curso?->nombreCompleto() ?? '—',
            ])->values();

        return $this->armar("Reporte por deporte — ciclo {$ciclo->anio}", ['Deporte', 'Horario', 'Legajo', 'Alumno', 'Curso'], $filas);
    }

    /** RF-18: inscriptos por recorrido de transporte. */
    private function porRecorrido(CicloLectivo $ciclo, ?int $recorridoId): array
    {
        $filas = InscripcionTransporte::with(['recorrido', 'alumno.curso.nivel', 'alumno.tutores'])
            ->where('ciclo_lectivo_id', $ciclo->id)
            ->when($recorridoId, fn ($q) => $q->where('recorrido_id', $recorridoId))
            ->get()
            ->sortBy(fn ($i) => [$i->recorrido->nombre, $i->alumno->apellido])
            ->map(fn (InscripcionTransporte $i) => [
                $i->recorrido->nombre,
                $i->alumno->legajo,
                $i->alumno->nombreCompleto(),
                $i->alumno->curso?->nombreCompleto() ?? '—',
                $i->alumno->tutores->map(fn ($t) => $t->nombreCompleto().($t->telefono ? " ({$t->telefono})" : ''))->join('; ') ?: '—',
            ])->values();

        return $this->armar("Reporte por recorrido — ciclo {$ciclo->anio}", ['Recorrido', 'Legajo', 'Alumno', 'Curso', 'Tutores'], $filas);
    }

    private function alumnosConServicios(CicloLectivo $ciclo, ?int $cursoId, bool $soloConCurso = false): Collection
    {
        $delCiclo = fn ($q) => $q->where('ciclo_lectivo_id', $ciclo->id);

        return Alumno::with([
            'curso.nivel',
            'curso.materias',
            'inscripcionesCursado' => $delCiclo,
            'inscripcionesComedor' => $delCiclo,
            'inscripcionesTransporte' => fn ($q) => $delCiclo($q)->with('recorrido'),
            'inscripcionesDeporte' => fn ($q) => $delCiclo($q)->with('deporte'),
        ])
            ->when($cursoId, fn ($q) => $q->where('curso_id', $cursoId))
            ->when($soloConCurso, fn ($q) => $q->whereNotNull('curso_id'))
            ->orderBy('apellido')->orderBy('nombre')
            ->get();
    }

    private function servicios(Alumno $a): array
    {
        return [
            $a->inscripcionesCursado->isNotEmpty() ? 'Sí' : 'No',
            $a->inscripcionesComedor->isNotEmpty() ? 'Sí' : 'No',
            $a->inscripcionesTransporte->first()?->recorrido->nombre ?? 'No',
            $a->inscripcionesDeporte->pluck('deporte.nombre')->join(', ') ?: 'No',
        ];
    }

    private function armar(string $titulo, array $columnas, Collection $filas): array
    {
        return compact('titulo', 'columnas', 'filas');
    }
}
