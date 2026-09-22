@extends('layouts.app')
@section('titulo', 'Reportes')
@section('contenido')
<x-encabezado titulo="Reportes" subtitulo="Consultá, filtrá y exportá a PDF o Excel" />
<div class="row g-3">
    @foreach ($tipos as $clave => [$nombre, $descripcion, $icono])
        <div class="col-sm-6 col-xl-4">
            <a href="{{ route('reportes.show', $clave) }}" class="card h-100 text-decoration-none">
                <div class="card-body">
                    <span class="ec-metrica-icono mb-3 {{ ['ec-acento-verde', 'ec-acento-violeta', 'ec-acento-ambar', 'ec-acento-rosa'][$loop->index % 4] }}">
                        <i class="bi {{ $icono }}"></i>
                    </span>
                    <h2 class="h5 mb-1">{{ $nombre }}</h2>
                    <p class="mb-0" style="color: var(--ec-tenue)">{{ $descripcion }}</p>
                </div>
                <div class="card-footer bg-transparent border-0 pb-3">
                    <span class="small fw-semibold" style="color: var(--ec-primario)">Abrir <i class="bi bi-arrow-right"></i></span>
                </div>
            </a>
        </div>
    @endforeach
</div>
@endsection
