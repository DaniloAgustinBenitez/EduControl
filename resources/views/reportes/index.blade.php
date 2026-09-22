@extends('layouts.app')
@section('titulo', 'Reportes')
@section('contenido')
<x-encabezado titulo="Reportes" />
<div class="row g-3">
    @foreach ($tipos as $clave => [$nombre, $descripcion, $icono])
        <div class="col-sm-6 col-lg-4">
            <a href="{{ route('reportes.show', $clave) }}" class="card h-100 text-decoration-none">
                <div class="card-body">
                    <i class="bi {{ $icono }} text-primary fs-3"></i>
                    <h2 class="h5 mt-2 text-body">{{ $nombre }}</h2>
                    <p class="text-muted mb-0">{{ $descripcion }}</p>
                </div>
            </a>
        </div>
    @endforeach
</div>
@endsection
