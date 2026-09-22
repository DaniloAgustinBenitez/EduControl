@extends('layouts.app')
@section('titulo', 'Niveles')
@section('contenido')
<x-encabezado titulo="Niveles educativos" :nuevo="route('niveles.create')" />
<x-tabla :columnas="['Nombre', 'Cursos']">
    @foreach ($niveles as $nivel)
        <tr>
            <td>{{ $nivel->nombre }}</td>
            <td>{{ $nivel->cursos_count }}</td>
            <x-acciones :editar="route('niveles.edit', $nivel)" :eliminar="route('niveles.destroy', $nivel)" />
        </tr>
    @endforeach
</x-tabla>
@endsection
