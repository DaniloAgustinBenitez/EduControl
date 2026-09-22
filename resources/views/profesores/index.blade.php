@extends('layouts.app')
@section('titulo', 'Profesores')
@section('contenido')
<x-encabezado titulo="Profesores" :nuevo="route('profesores.create')" />
<x-tabla :columnas="['Profesor', 'DNI', 'Correo', 'Materias', 'Cursos', 'Niveles']">
    @foreach ($profesores as $profesor)
        <tr>
            <td>{{ $profesor->nombreCompleto() }}</td>
            <td>{{ $profesor->dni }}</td>
            <td>{{ $profesor->user?->email }}</td>
            <td>{{ $profesor->materias_count }}</td>
            <td>{{ $profesor->cursos_count }}</td>
            <td>{{ $profesor->niveles_count }}</td>
            <x-acciones :editar="route('profesores.edit', $profesor)" :eliminar="route('profesores.destroy', $profesor)"
                mensaje="Se eliminará el profesor y su cuenta de acceso. ¿Continuar?">
                <a href="{{ route('profesores.asignaciones', $profesor) }}" class="btn btn-sm btn-outline-secondary" title="Materias y cursos"><i class="bi bi-diagram-3"></i></a>
            </x-acciones>
        </tr>
    @endforeach
</x-tabla>
@endsection
