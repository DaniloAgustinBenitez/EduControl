@extends('layouts.app')
@section('titulo', 'Ciclo lectivo')
@section('contenido')
<x-formulario :modelo="$ciclo" titulo="ciclo lectivo" :crear="route('ciclos.store')" :actualizar="$ciclo->exists ? route('ciclos.update', $ciclo) : ''" :volver="route('ciclos.index')">
    <x-campo nombre="anio" etiqueta="Año" tipo="number" :valor="$ciclo->anio" requerido min="2000" max="2100" />
    <div class="form-check mb-3">
        <input type="checkbox" name="activo" id="activo" value="1" class="form-check-input" @checked(old('activo', $ciclo->activo))>
        <label for="activo" class="form-check-label">Ciclo activo (habilita las inscripciones y desactiva los demás)</label>
    </div>
</x-formulario>
@endsection
