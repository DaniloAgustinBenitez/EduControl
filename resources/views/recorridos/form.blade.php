@extends('layouts.app')
@section('titulo', 'Recorrido')
@section('contenido')
<x-formulario :modelo="$recorrido" titulo="recorrido" :crear="route('recorridos.store')" :actualizar="$recorrido->exists ? route('recorridos.update', $recorrido) : ''" :volver="route('recorridos.index')">
    <x-campo nombre="nombre" etiqueta="Nombre" :valor="$recorrido->nombre" requerido maxlength="60" />
    <x-campo nombre="descripcion" etiqueta="Zonas que cubre" :valor="$recorrido->descripcion" maxlength="255" />
</x-formulario>
@endsection
