@extends('layouts.app')
@section('titulo', 'Mis hijos')
@section('contenido')
<x-encabezado titulo="Mis hijos">
    <span class="badge text-bg-secondary align-self-center">Ciclo lectivo {{ $ciclo?->anio ?? 'cerrado' }}</span>
</x-encabezado>

@unless ($ciclo)
    <div class="alert alert-warning">Las inscripciones están cerradas: no hay un ciclo lectivo activo.</div>
@endunless

<div class="row g-3">
    @forelse ($hijos as ['alumno' => $alumno, 'estado' => $estado])
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h5 mb-1">{{ $alumno->nombre }} {{ $alumno->apellido }}</h2>
                    <div class="text-muted small mb-3">Legajo {{ $alumno->legajo }} · {{ $alumno->curso?->nombreCompleto() ?? 'Sin curso asignado' }}</div>
                    @if ($estado)
                        <ul class="list-unstyled mb-0">
                            <li><i class="bi {{ $estado['cursado'] ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' }}"></i> Cursado</li>
                            <li><i class="bi {{ $estado['comedor'] ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' }}"></i> Comedor</li>
                            <li><i class="bi {{ $estado['transporte'] ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' }}"></i>
                                Transporte{{ $estado['transporte'] ? ': '.$estado['transporte']->recorrido->nombre : '' }}</li>
                            <li><i class="bi {{ $estado['deportes']->isNotEmpty() ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' }}"></i>
                                Deportes{{ $estado['deportes']->isNotEmpty() ? ': '.$estado['deportes']->pluck('deporte.nombre')->join(', ') : '' }}</li>
                        </ul>
                    @endif
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="{{ route('autogestion.show', $alumno) }}" class="btn btn-primary btn-sm">Gestionar inscripciones</a>
                </div>
            </div>
        </div>
    @empty
        <div class="col"><div class="alert alert-info">No tenés alumnos vinculados a tu cuenta. Comunicate con la administración.</div></div>
    @endforelse
</div>
@endsection
