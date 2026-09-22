@extends('layouts.app')
@section('titulo', 'Alumno')
@section('contenido')
<x-formulario :modelo="$alumno" titulo="alumno" :crear="route('alumnos.store')" :actualizar="$alumno->exists ? route('alumnos.update', $alumno) : ''" :volver="route('alumnos.index')">
    <div class="row">
        <div class="col-md-6"><x-campo nombre="legajo" etiqueta="Legajo" :valor="$alumno->legajo" requerido maxlength="20" /></div>
        <div class="col-md-6"><x-campo nombre="dni" etiqueta="DNI" :valor="$alumno->dni" requerido maxlength="15" inputmode="numeric" /></div>
        <div class="col-md-6"><x-campo nombre="nombre" etiqueta="Nombre" :valor="$alumno->nombre" requerido maxlength="60" /></div>
        <div class="col-md-6"><x-campo nombre="apellido" etiqueta="Apellido" :valor="$alumno->apellido" requerido maxlength="60" /></div>
        <div class="col-md-6"><x-campo nombre="fecha_nacimiento" etiqueta="Fecha de nacimiento" tipo="date" :valor="$alumno->fecha_nacimiento?->format('Y-m-d')" /></div>
        <div class="col-md-6">
            @if ($alumno->exists)
                <label class="form-label">Curso</label>
                <p class="form-control-plaintext">{{ $alumno->curso?->nombreCompleto() ?? 'Sin curso' }}
                    <small class="text-muted d-block">Se cambia desde el listado de alumnos (un solo curso por alumno).</small></p>
            @else
                <label for="curso_id" class="form-label">Curso</label>
                <select name="curso_id" id="curso_id" class="form-select">
                    <option value="">— Sin curso —</option>
                    @foreach ($cursos as $curso)
                        <option value="{{ $curso->id }}" @selected(old('curso_id') == $curso->id)>{{ $curso->nombreCompleto() }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>
</x-formulario>
@endsection
