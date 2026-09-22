@extends('layouts.app')
@section('titulo', 'Mis hijos')
@section('contenido')
<x-encabezado titulo="Mis hijos" :subtitulo="$ciclo ? 'Inscripciones del ciclo lectivo '.$ciclo->anio : 'Inscripciones cerradas'" />

@unless ($ciclo)
    <div class="alert alert-warning d-flex align-items-center gap-2">
        <i class="bi bi-calendar-x"></i>
        <div>Las inscripciones están cerradas porque no hay un ciclo lectivo activo. Consultá en la escuela.</div>
    </div>
@endunless

<div class="row g-3">
    @forelse ($hijos as ['alumno' => $alumno, 'estado' => $estado])
        <div class="col-md-6 col-xxl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="ec-avatar" style="width: 46px; height: 46px; flex-basis: 46px">
                            {{ Str::substr($alumno->nombre, 0, 1) }}{{ Str::substr($alumno->apellido, 0, 1) }}
                        </span>
                        <div>
                            <h2 class="h5 mb-0">{{ $alumno->nombre }} {{ $alumno->apellido }}</h2>
                            <div class="small" style="color: var(--ec-tenue)">
                                Legajo {{ $alumno->legajo }} · {{ $alumno->curso?->nombreCompleto() ?? 'Sin curso asignado' }}
                            </div>
                        </div>
                    </div>

                    @if ($estado)
                        @php($servicios = [
                            ['Cursado', 'bi-book', (bool) $estado['cursado'], null],
                            ['Comedor', 'bi-cup-hot', (bool) $estado['comedor'], null],
                            ['Transporte', 'bi-bus-front', (bool) $estado['transporte'], $estado['transporte']?->recorrido->nombre],
                            ['Deportes', 'bi-trophy', $estado['deportes']->isNotEmpty(), $estado['deportes']->pluck('deporte.nombre')->join(', ')],
                        ])
                        <ul class="list-unstyled mb-0">
                            @foreach ($servicios as [$nombre, $icono, $activo, $detalle])
                                <li class="d-flex align-items-center gap-2 py-1">
                                    <i class="bi {{ $icono }}" style="color: var(--ec-tenue)"></i>
                                    <span>{{ $nombre }}</span>
                                    @if ($detalle)<span class="small" style="color: var(--ec-tenue)">— {{ $detalle }}</span>@endif
                                    <span class="ec-pill {{ $activo ? 'ec-pill-si' : 'ec-pill-no' }} ms-auto">
                                        <i class="bi {{ $activo ? 'bi-check-circle-fill' : 'bi-dash-circle' }}"></i>
                                        {{ $activo ? 'Inscripto' : 'Sin inscribir' }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                <div class="card-footer bg-transparent border-0 pb-3">
                    <a href="{{ route('autogestion.show', $alumno) }}" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-pencil-square"></i> Gestionar inscripciones
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="col">
            <div class="card"><div class="ec-vacio">
                <i class="bi bi-people"></i>
                No tenés alumnos vinculados a tu cuenta. Comunicate con la administración de la escuela.
            </div></div>
        </div>
    @endforelse
</div>
@endsection
