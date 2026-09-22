@extends('layouts.app')
@section('titulo', 'Deporte')
@section('contenido')
<x-formulario :modelo="$deporte" titulo="deporte" :crear="route('deportes.store')" :actualizar="$deporte->exists ? route('deportes.update', $deporte) : ''" :volver="route('deportes.index')">
    <x-campo nombre="nombre" etiqueta="Nombre" :valor="$deporte->nombre" requerido maxlength="60" />
    <x-campo nombre="descripcion" etiqueta="Descripción" :valor="$deporte->descripcion" maxlength="255" />
</x-formulario>
@endsection
