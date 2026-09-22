@extends('layouts.app')
@section('titulo', 'Materia')
@section('contenido')
<x-formulario :modelo="$materia" titulo="materia" :crear="route('materias.store')" :actualizar="$materia->exists ? route('materias.update', $materia) : ''" :volver="route('materias.index')">
    <div class="mb-3">
        <label for="curso_id" class="form-label">Curso <span class="text-danger">*</span></label>
        <select name="curso_id" id="curso_id" class="form-select" required>
            <option value="">Elegí un curso…</option>
            @foreach ($cursos as $curso)
                <option value="{{ $curso->id }}" @selected(old('curso_id', $materia->curso_id) == $curso->id)>{{ $curso->nombreCompleto() }}</option>
            @endforeach
        </select>
    </div>
    <x-campo nombre="nombre" etiqueta="Nombre" :valor="$materia->nombre" requerido maxlength="80" />
</x-formulario>
@endsection
