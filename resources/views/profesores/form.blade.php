@extends('layouts.app')
@section('titulo', 'Profesor')
@section('contenido')
<x-formulario :modelo="$profesor" titulo="profesor" :crear="route('profesores.store')" :actualizar="$profesor->exists ? route('profesores.update', $profesor) : ''" :volver="route('profesores.index')">
    @include('partials.persona', ['persona' => $profesor, 'conLegajo' => true])
</x-formulario>
@endsection
