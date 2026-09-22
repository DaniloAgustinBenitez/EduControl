@extends('layouts.app')
@section('titulo', $reporte['titulo'])
@section('contenido')
<x-encabezado :titulo="$reporte['titulo']">
    <a href="{{ route('reportes.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Reportes</a>
</x-encabezado>

{{-- Vista base de reporte con filtros (tarea 6.1) --}}
<form method="GET" class="card mb-3">
    <div class="card-body row g-2 align-items-end">
        <div class="col-sm-4 col-md-3">
            <label for="ciclo" class="form-label">Ciclo lectivo</label>
            <select name="ciclo" id="ciclo" class="form-select">
                @foreach ($ciclos as $opcion)
                    <option value="{{ $opcion->id }}" @selected($opcion->id === $ciclo->id)>{{ $opcion->anio }}{{ $opcion->activo ? ' (activo)' : '' }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-8 col-md-5">
            <label for="filtro" class="form-label">{{ $filtro[0] }}</label>
            <select name="filtro" id="filtro" class="form-select">
                <option value="">Todos</option>
                @foreach ($filtro[1] as $id => $nombre)
                    <option value="{{ $id }}" @selected(request('filtro') == $id)>{{ $nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4 d-flex gap-2 flex-wrap">
            <button class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
            <button name="formato" value="pdf" class="btn btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
            <button name="formato" value="xlsx" class="btn btn-outline-success"><i class="bi bi-file-earmark-spreadsheet"></i> Excel</button>
        </div>
    </div>
</form>

<x-tabla :columnas="$reporte['columnas']" :acciones="false">
    @foreach ($reporte['filas'] as $fila)
        <tr>@foreach ($fila as $celda)<td>{{ $celda }}</td>@endforeach</tr>
    @endforeach
</x-tabla>
<p class="text-muted small mt-2">{{ $reporte['filas']->count() }} registros · generado {{ now()->format('d/m/Y H:i') }}</p>
@endsection
