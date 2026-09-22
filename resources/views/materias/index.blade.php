@extends('layouts.app')
@section('titulo', 'Materias')
@section('contenido')
<x-encabezado titulo="Materias" :nuevo="route('materias.create')" textoNuevo="Nueva" />
<x-tabla :columnas="['Materia', 'Curso', 'Nivel', 'Docentes']">
    @foreach ($materias as $materia)
        <tr>
            <td>{{ $materia->nombre }}</td>
            <td>{{ $materia->curso->nombre }}</td>
            <td>{{ $materia->curso->nivel->nombre }}</td>
            <td>{{ $materia->profesores->map->nombreCompleto()->join('; ') ?: '—' }}</td>
            <x-acciones :editar="route('materias.edit', $materia)" :eliminar="route('materias.destroy', $materia)" />
        </tr>
    @endforeach
</x-tabla>
@endsection
