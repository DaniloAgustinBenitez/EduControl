@extends('layouts.app')

@section('contenido')
<x-encabezado titulo="Panel de administración" subtitulo="Resumen del ciclo lectivo en curso" />

@unless ($ciclo)
    <div class="alert alert-warning d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>No hay un ciclo lectivo activo, así que las inscripciones están cerradas.
            @can('configuracion.gestionar')<a href="{{ route('ciclos.index') }}" class="alert-link">Activá uno acá</a>.@endcan
        </div>
    </div>
@endunless

@php($iconos = [
    'Alumnos' => ['bi-person-vcard', ''],
    'Profesores' => ['bi-person-badge', 'ec-acento-violeta'],
    'Cursos' => ['bi-door-open', 'ec-acento-ambar'],
    'En comedor' => ['bi-cup-hot', 'ec-acento-verde'],
    'En transporte' => ['bi-bus-front', 'ec-acento-rosa'],
    'Inscripciones a deportes' => ['bi-trophy', 'ec-acento-verde'],
])

<div class="row g-3">
    @foreach ($totales as $etiqueta => $valor)
        <div class="col-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body ec-metrica">
                    <span class="ec-metrica-icono {{ $iconos[$etiqueta][1] ?? '' }}"><i class="bi {{ $iconos[$etiqueta][0] ?? 'bi-dot' }}"></i></span>
                    <div>
                        <div class="ec-metrica-valor">{{ number_format($valor, 0, ',', '.') }}</div>
                        <div class="ec-metrica-etiqueta">{{ $etiqueta }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<h2 class="h5 mt-4 mb-3">Accesos rápidos</h2>
<div class="row g-3">
    @php($accesos = [
        ['usuarios.gestionar', 'alumnos.create', 'bi-person-plus', 'Nuevo alumno', 'Dar de alta un alumno y asignarle curso'],
        ['usuarios.gestionar', 'tutores.create', 'bi-person-heart', 'Nuevo tutor', 'Crear la cuenta de un padre o tutor'],
        ['reportes.ver', 'reportes.index', 'bi-bar-chart', 'Reportes', 'Consultar y exportar a PDF o Excel'],
        ['configuracion.gestionar', 'cursos.index', 'bi-door-open', 'Cursos y horarios', 'Administrar cursos y su horario de cursado'],
    ])
    @foreach ($accesos as [$permiso, $ruta, $icono, $titulo, $detalle])
        @can($permiso)
            <div class="col-sm-6 col-xl-3">
                <a href="{{ route($ruta) }}" class="card h-100 text-decoration-none">
                    <div class="card-body">
                        <span class="ec-metrica-icono mb-2"><i class="bi {{ $icono }}"></i></span>
                        <div class="fw-semibold" style="color: var(--ec-tinta)">{{ $titulo }}</div>
                        <div class="small" style="color: var(--ec-tenue)">{{ $detalle }}</div>
                    </div>
                </a>
            </div>
        @endcan
    @endforeach
</div>
@endsection
