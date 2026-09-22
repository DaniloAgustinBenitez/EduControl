@extends('layouts.app')
@section('titulo', $curso->nombre)
@section('contenido')
<x-encabezado :titulo="$curso->nombreCompleto()">
    <a href="{{ route('docente.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
</x-encabezado>

<p>
    Mis materias en este curso:
    @forelse ($materias as $materia)
        <span class="badge text-bg-primary">{{ $materia->nombre }}</span>
    @empty
        <span class="text-muted">ninguna</span>
    @endforelse
</p>

<x-tabla :columnas="['Legajo', 'Alumno', 'DNI']" :acciones="false">
    @foreach ($curso->alumnos as $alumno)
        <tr>
            <td>{{ $alumno->legajo }}</td>
            <td>{{ $alumno->nombreCompleto() }}</td>
            <td>{{ $alumno->dni }}</td>
        </tr>
    @endforeach
</x-tabla>
@endsection
