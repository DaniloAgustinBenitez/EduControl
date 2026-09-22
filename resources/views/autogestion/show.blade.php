@extends('layouts.app')
@section('titulo', $alumno->nombre)
@section('contenido')
<x-encabezado :titulo="$alumno->nombre.' '.$alumno->apellido"
    :subtitulo="'Legajo '.$alumno->legajo.' · '.($alumno->curso?->nombreCompleto() ?? 'Sin curso asignado')"
    :volver="route('autogestion.index')" />

@if ($alumno->curso?->horarios->isNotEmpty())
    <p class="small" style="color: var(--ec-tenue)">
        <i class="bi bi-clock"></i> Horario de cursado: {{ $alumno->curso->horarios->map->descripcion()->join(' · ') }}
    </p>
@endif

@if (! $ciclo)
    <div class="alert alert-warning d-flex align-items-center gap-2">
        <i class="bi bi-calendar-x"></i> <div>Las inscripciones están cerradas: no hay un ciclo lectivo activo.</div>
    </div>
@else
<div class="row g-3">
    {{-- Cursado (RF-08) --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="ec-metrica-icono"><i class="bi bi-book"></i></span>
                    <div>
                        <h2 class="h5 mb-0">Cursado {{ $ciclo->anio }}</h2>
                        <span class="ec-pill {{ $estado['cursado'] ? 'ec-pill-si' : 'ec-pill-no' }}">{{ $estado['cursado'] ? 'Inscripto' : 'Sin inscribir' }}</span>
                    </div>
                </div>
                @if ($estado['cursado'])
                    <p class="mb-3">Inscripto en <strong>{{ $estado['cursado']->curso->nombreCompleto() }}</strong>.</p>
                    @include('autogestion.baja', ['servicio' => 'cursado', 'inscripcion' => $estado['cursado'], 'texto' => 'Dar de baja'])
                @elseif ($alumno->curso)
                    <p class="mb-3">Confirmá la inscripción al cursado de {{ $alumno->curso->nombreCompleto() }}.</p>
                    <form method="POST" action="{{ route('autogestion.inscribir', [$alumno, 'cursado']) }}">@csrf<button class="btn btn-primary btn-sm">Inscribir al cursado</button></form>
                @else
                    <p class="mb-0" style="color: var(--ec-tenue)">La administración todavía no le asignó un curso.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Comedor (RF-10) --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="ec-metrica-icono ec-acento-ambar"><i class="bi bi-cup-hot"></i></span>
                    <div>
                        <h2 class="h5 mb-0">Comedor</h2>
                        <span class="ec-pill {{ $estado['comedor'] ? 'ec-pill-si' : 'ec-pill-no' }}">{{ $estado['comedor'] ? 'Inscripto' : 'Sin inscribir' }}</span>
                    </div>
                </div>
                @if ($estado['comedor'])
                    <p class="mb-3">Tiene el servicio de comedor activo.</p>
                    @include('autogestion.baja', ['servicio' => 'comedor', 'inscripcion' => $estado['comedor'], 'texto' => 'Dar de baja'])
                @else
                    <form method="POST" action="{{ route('autogestion.inscribir', [$alumno, 'comedor']) }}">@csrf<button class="btn btn-primary btn-sm">Inscribir al comedor</button></form>
                @endif
            </div>
        </div>
    </div>

    {{-- Transporte (RF-11, RN-06) --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="ec-metrica-icono ec-acento-rosa"><i class="bi bi-bus-front"></i></span>
                    <div>
                        <h2 class="h5 mb-0">Transporte</h2>
                        <span class="ec-pill {{ $estado['transporte'] ? 'ec-pill-si' : 'ec-pill-no' }}">{{ $estado['transporte'] ? 'Inscripto' : 'Sin inscribir' }}</span>
                    </div>
                </div>
                @if ($estado['transporte'])
                    <p class="mb-1">Recorrido <strong>{{ $estado['transporte']->recorrido->nombre }}</strong>.</p>
                    <p class="small mb-3" style="color: var(--ec-tenue)">Solo se permite un recorrido. Para cambiarlo, dalo de baja y elegí otro.</p>
                    @include('autogestion.baja', ['servicio' => 'transporte', 'inscripcion' => $estado['transporte'], 'texto' => 'Dar de baja'])
                @else
                    <form method="POST" action="{{ route('autogestion.inscribir', [$alumno, 'transporte']) }}">
                        @csrf
                        @foreach ($recorridos as $recorrido)
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="recorrido_id" value="{{ $recorrido->id }}" id="rec{{ $recorrido->id }}" required>
                                <label class="form-check-label" for="rec{{ $recorrido->id }}">
                                    {{ $recorrido->nombre }}
                                    <span class="small d-block" style="color: var(--ec-tenue)">{{ $recorrido->descripcion }}</span>
                                </label>
                            </div>
                        @endforeach
                        <button class="btn btn-primary btn-sm mt-3">Inscribir al recorrido</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Deportes (RF-09, RN-03, RN-04, RN-05) --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="ec-metrica-icono ec-acento-verde"><i class="bi bi-trophy"></i></span>
                    <div>
                        <h2 class="h5 mb-0">Deportes</h2>
                        <span class="ec-pill {{ $estado['deportes']->isNotEmpty() ? 'ec-pill-si' : 'ec-pill-no' }}">
                            {{ $estado['deportes']->count() }} de {{ $maxDeportes }}
                        </span>
                    </div>
                </div>

                @foreach ($estado['deportes'] as $inscripcion)
                    <div class="d-flex justify-content-between align-items-center gap-2 border-bottom py-2">
                        <div>
                            <div class="fw-semibold">{{ $inscripcion->deporte->nombre }}</div>
                            <div class="small" style="color: var(--ec-tenue)">{{ $inscripcion->deporte->horarios->map->descripcion()->join(' · ') }}</div>
                        </div>
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
                    <p class="small mt-3 mb-0" style="color: var(--ec-tenue)">Alcanzó el máximo de {{ $maxDeportes }} deportes.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endif
@endsection
