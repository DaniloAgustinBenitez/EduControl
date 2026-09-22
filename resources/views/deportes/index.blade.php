@extends('layouts.app')
@section('titulo', 'Deportes')
@section('contenido')
<x-encabezado titulo="Deportes" :nuevo="route('deportes.create')" />
<x-tabla :columnas="['Deporte', 'Horarios', 'Inscriptos']">
    @foreach ($deportes as $deporte)
        <tr>
            <td>{{ $deporte->nombre }}<div class="small text-muted">{{ $deporte->descripcion }}</div></td>
            <td>{!! $deporte->horarios->map(fn ($h) => e($h->descripcion()))->join('<br>') ?: '<span class="text-danger">Sin horarios</span>' !!}</td>
            <td>{{ $deporte->inscripciones_count }}</td>
            <x-acciones :editar="route('deportes.edit', $deporte)" :eliminar="route('deportes.destroy', $deporte)">
                <a href="{{ route('horarios.index', ['deporte', $deporte->id]) }}" class="btn btn-sm btn-outline-secondary" title="Horarios"><i class="bi bi-clock"></i></a>
            </x-acciones>
        </tr>
    @endforeach
</x-tabla>
@endsection
