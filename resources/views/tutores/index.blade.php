@extends('layouts.app')
@section('titulo', 'Tutores')
@section('contenido')
<x-encabezado titulo="Tutores" :nuevo="route('tutores.create')" />
<x-tabla :columnas="['Tutor', 'DNI', 'Correo', 'Teléfono', 'Alumnos a cargo']">
    @foreach ($tutores as $tutor)
        <tr>
            <td>{{ $tutor->nombreCompleto() }}</td>
            <td>{{ $tutor->dni }}</td>
            <td>{{ $tutor->user?->email }}</td>
            <td>{{ $tutor->telefono }}</td>
            <td>{{ $tutor->alumnos->map->nombreCompleto()->join('; ') ?: '—' }}</td>
            <x-acciones :editar="route('tutores.edit', $tutor)" :eliminar="route('tutores.destroy', $tutor)"
                mensaje="Se eliminará el tutor y su cuenta de acceso (los alumnos no se borran). ¿Continuar?" />
        </tr>
    @endforeach
</x-tabla>
@endsection
