@extends('layouts.app')
@section('titulo', 'Nivel')
@section('contenido')
<x-formulario :modelo="$nivel" titulo="nivel" :crear="route('niveles.store')" :actualizar="$nivel->exists ? route('niveles.update', $nivel) : ''" :volver="route('niveles.index')">
    <x-campo nombre="nombre" etiqueta="Nombre" :valor="$nivel->nombre" requerido maxlength="60" />
</x-formulario>
@endsection
