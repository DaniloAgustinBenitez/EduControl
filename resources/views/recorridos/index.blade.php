@extends('layouts.app')
@section('titulo', 'Recorridos')
@section('contenido')
<x-encabezado titulo="Recorridos de transporte" :nuevo="route('recorridos.create')" />
<x-tabla :columnas="['Recorrido', 'Zonas', 'Inscriptos']">
    @foreach ($recorridos as $recorrido)
        <tr>
            <td>{{ $recorrido->nombre }}</td>
            <td>{{ $recorrido->descripcion }}</td>
            <td>{{ $recorrido->inscripciones_count }}</td>
            <x-acciones :editar="route('recorridos.edit', $recorrido)" :eliminar="route('recorridos.destroy', $recorrido)" />
        </tr>
    @endforeach
</x-tabla>
@endsection
