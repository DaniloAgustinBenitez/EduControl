@extends('layouts.app')
@section('titulo', 'Mis cursos')
@section('contenido')
<x-encabezado titulo="Mis materias y cursos" />

@if (! $profesor)
    <div class="alert alert-warning">Tu cuenta no está vinculada a un profesor. Pedile a la administración que la vincule.</div>
@else
    <p class="text-muted">
        Niveles: {{ $profesor->niveles->pluck('nombre')->join(', ') ?: 'ninguno asignado' }}
    </p>

    <div class="row g-3">
        @forelse ($profesor->cursos as $curso)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="h5">{{ $curso->nombre }} <small class="text-muted">{{ $curso->nivel->nombre }}</small></h2>
                        <div class="mb-2">
                            @forelse ($materiasPorCurso[$curso->nombreCompleto()] ?? [] as $materia)
                                <span class="badge text-bg-primary">{{ $materia->nombre }}</span>
                            @empty
                                <span class="text-muted small">Sin materias asignadas en este curso</span>
                            @endforelse
                        </div>
                        <div class="small text-muted">
                            {{ $curso->horarios->map->descripcion()->join(' · ') ?: 'Sin horario de cursado' }}
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0">
                        <a href="{{ route('docente.curso', $curso) }}" class="btn btn-sm btn-outline-primary">Ver alumnos</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col"><div class="alert alert-info">Todavía no tenés cursos asignados.</div></div>
        @endforelse
    </div>
@endif
@endsection
