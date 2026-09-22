@extends('layouts.app')
@section('titulo', 'Ciclos lectivos')
@section('contenido')
<x-encabezado titulo="Ciclos lectivos" :nuevo="route('ciclos.create')" />
<x-tabla :columnas="['Año', 'Estado']">
    @foreach ($ciclos as $ciclo)
        <tr>
            <td>{{ $ciclo->anio }}</td>
            <td>@if ($ciclo->activo)<span class="badge text-bg-success">Activo</span>@else<span class="badge text-bg-secondary">Cerrado</span>@endif</td>
            <x-acciones :editar="route('ciclos.edit', $ciclo)" :eliminar="route('ciclos.destroy', $ciclo)" />
        </tr>
    @endforeach
</x-tabla>
@endsection
