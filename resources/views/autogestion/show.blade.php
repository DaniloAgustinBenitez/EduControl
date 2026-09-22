@extends('layouts.app')
@section('titulo', $alumno->nombre)
@section('contenido')
<x-encabezado :titulo="$alumno->nombre.' '.$alumno->apellido">
    <a href="{{ route('autogestion.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Mis hijos</a>
</x-encabezado>

<p class="text-muted">
    Legajo {{ $alumno->legajo }} · {{ $alumno->curso?->nombreCompleto() ?? 'Sin curso asignado' }}
    @if ($alumno->curso?->horarios->isNotEmpty())
        <br>Horario de cursado: {{ $alumno->curso->horarios->map->descripcion()->join(' · ') }}
    @endif
</p>

@if (! $ciclo)
    <div class="alert alert-warning">Las inscripciones están cerradas: no hay un ciclo lectivo activo.</div>
@else
<div class="row g-3">
    {{-- Cursado (RF-08) --}}
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <h2 class="h5"><i class="bi bi-book"></i> Cursado {{ $ciclo->anio }}</h2>
            @if ($estado['cursado'])
                <p class="mb-2 text-success">Inscripto en {{ $estado['cursado']->curso->nombreCompleto() }}.</p>
                @include('autogestion.baja', ['servicio' => 'cursado', 'inscripcion' => $estado['cursado'], 'texto' => 'Dar de baja'])
            @elseif ($alumno->curso)
                <p>Confirmá la inscripción al cursado de {{ $alumno->curso->nombreCompleto() }}.</p>
                <form method="POST" action="{{ route('autogestion.inscribir', [$alumno, 'cursado']) }}">@csrf<button class="btn btn-primary btn-sm">Inscribir al cursado</button></form>
            @else
                <p class="text-muted mb-0">La administración todavía no le asignó un curso.</p>
            @endif
        </div></div>
    </div>

    {{-- Comedor (RF-10) --}}
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <h2 class="h5"><i class="bi bi-cup-hot"></i> Comedor</h2>
            @if ($estado['comedor'])
                <p class="mb-2 text-success">Inscripto al servicio de comedor.</p>
                @include('autogestion.baja', ['servicio' => 'comedor', 'inscripcion' => $estado['comedor'], 'texto' => 'Dar de baja'])
            @else
                <form method="POST" action="{{ route('autogestion.inscribir', [$alumno, 'comedor']) }}">@csrf<button class="btn btn-primary btn-sm">Inscribir al comedor</button></form>
            @endif
        </div></div>
    </div>

    {{-- Transporte (RF-11, RN-06) --}}
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <h2 class="h5"><i class="bi bi-bus-front"></i> Transporte</h2>
            @if ($estado['transporte'])
                <p class="mb-2 text-success">Recorrido {{ $estado['transporte']->recorrido->nombre }}.</p>
                <p class="small text-muted">Solo se permite un recorrido. Para cambiarlo, dalo de baja y elegí otro.</p>
                @include('autogestion.baja', ['servicio' => 'transporte', 'inscripcion' => $estado['transporte'], 'texto' => 'Dar de baja'])
            @else
                <form method="POST" action="{{ route('autogestion.inscribir', [$alumno, 'transporte']) }}">
                    @csrf
                    @foreach ($recorridos as $recorrido)
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="recorrido_id" value="{{ $recorrido->id }}" id="rec{{ $recorrido->id }}" required>
                            <label class="form-check-label" for="rec{{ $recorrido->id }}">{{ $recorrido->nombre }} <small class="text-muted">{{ $recorrido->descripcion }}</small></label>
                        </div>
                    @endforeach
                    <button class="btn btn-primary btn-sm mt-2">Inscribir al recorrido</button>
                </form>
            @endif
        </div></div>
    </div>

    {{-- Deportes (RF-09, RN-03, RN-04, RN-05) --}}
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <h2 class="h5"><i class="bi bi-trophy"></i> Deportes <small class="text-muted">({{ $estado['deportes']->count() }} de {{ $maxDeportes }})</small></h2>
            @foreach ($estado['deportes'] as $inscripcion)
                <div class="d-flex justify-content-between align-items-center border-bottom py-1">
                    <div>{{ $inscripcion->deporte->nombre }}
                        <div class="small text-muted">{{ $inscripcion->deporte->horarios->map->descripcion()->join(' · ') }}</div></div>
                    @include('autogestion.baja', ['servicio' => 'deporte', 'inscripcion' => $inscripcion, 'texto' => 'Baja'])
                </div>
            @endforeach

            @if ($estado['deportes']->count() < $maxDeportes)
                <form method="POST" action="{{ route('autogestion.inscribir', [$alumno, 'deporte']) }}" class="mt-3">
                    @csrf
                    <select name="deporte_id" class="form-select form-select-sm mb-2" required aria-label="Deporte">
                        <option value="">Elegí un deporte…</option>
                        @foreach ($deportes->whereNotIn('id', $estado['deportes']->pluck('deporte_id')) as $deporte)
                            <option value="{{ $deporte->id }}">{{ $deporte->nombre }} — {{ $deporte->horarios->map->descripcion()->join(' · ') }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary btn-sm">Inscribir</button>
                    <div class="form-text">No se permiten deportes con horarios superpuestos entre sí ni con el cursado.</div>
                </form>
            @else
                <p class="small text-muted mt-2 mb-0">Alcanzó el máximo de {{ $maxDeportes }} deportes.</p>
            @endif
        </div></div>
    </div>
</div>
@endif
@endsection
