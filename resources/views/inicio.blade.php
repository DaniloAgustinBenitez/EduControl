@extends('layouts.app')

@section('contenido')
<x-encabezado titulo="Panel de administración">
    <span class="badge text-bg-secondary align-self-center">Ciclo lectivo {{ $ciclo?->anio ?? 'sin definir' }}</span>
</x-encabezado>

@unless ($ciclo)
    <div class="alert alert-warning">No hay un ciclo lectivo activo. Las inscripciones quedan deshabilitadas hasta activar uno.</div>
@endunless

<div class="row g-3">
    @foreach ($totales as $etiqueta => $valor)
        <div class="col-6 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ $etiqueta }}</div>
                    <div class="stat">{{ $valor }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
