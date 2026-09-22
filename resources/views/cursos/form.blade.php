@extends('layouts.app')
@section('titulo', 'Curso')
@section('contenido')
<x-formulario :modelo="$curso" titulo="curso" :crear="route('cursos.store')" :actualizar="$curso->exists ? route('cursos.update', $curso) : ''" :volver="route('cursos.index')">
    <div class="mb-3">
        <label for="nivel_id" class="form-label">Nivel <span class="text-danger">*</span></label>
        <select name="nivel_id" id="nivel_id" class="form-select" required>
            <option value="">Elegí un nivel…</option>
            @foreach ($niveles as $nivel)
                <option value="{{ $nivel->id }}" @selected(old('nivel_id', $curso->nivel_id) == $nivel->id)>{{ $nivel->nombre }}</option>
            @endforeach
        </select>
    </div>
    <x-campo nombre="nombre" etiqueta="Nombre (ej. 1° año)" :valor="$curso->nombre" requerido maxlength="60" />
</x-formulario>
@endsection
