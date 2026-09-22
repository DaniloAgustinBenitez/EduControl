@extends('layouts.app')
@section('titulo', 'Cursos')
@section('contenido')
<x-encabezado titulo="Cursos" :nuevo="route('cursos.create')" />
<x-tabla :columnas="['Curso', 'Nivel', 'Materias', 'Alumnos', 'Franjas de cursado']">
    @foreach ($cursos as $curso)
        <tr>
            <td>{{ $curso->nombre }}</td>
            <td>{{ $curso->nivel->nombre }}</td>
            <td>{{ $curso->materias_count }}</td>
            <td>{{ $curso->alumnos_count }}</td>
            <td>{!! $curso->horarios_count ?: '<span class="text-danger">Sin horario</span>' !!}</td>
            <x-acciones :editar="route('cursos.edit', $curso)" :eliminar="route('cursos.destroy', $curso)">
                <a href="{{ route('horarios.index', ['curso', $curso->id]) }}" class="btn btn-sm btn-outline-secondary" title="Horario de cursado"><i class="bi bi-clock"></i></a>
            </x-acciones>
        </tr>
    @endforeach
</x-tabla>
@endsection
