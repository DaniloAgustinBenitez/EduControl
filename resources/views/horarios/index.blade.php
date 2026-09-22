@extends('layouts.app')
@section('titulo', 'Horarios')
@section('contenido')
<x-encabezado :titulo="$titulo">
    <a href="{{ $volver }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
</x-encabezado>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Día</th><th>Desde</th><th>Hasta</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($dueno->horarios as $horario)
                            <tr>
                                <td>{{ \App\Models\Horario::DIAS[$horario->dia] }}</td>
                                <td>{{ substr($horario->hora_inicio, 0, 5) }}</td>
                                <td>{{ substr($horario->hora_fin, 0, 5) }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('horarios.destroy', $horario) }}" data-confirmar="¿Quitar esta franja?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">Todavía no hay franjas cargadas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h2 class="h6">Agregar franja</h2>
                <form method="POST" action="{{ route('horarios.store', [$tipo, $dueno->id]) }}">
                    @csrf
                    <div class="mb-3">
                        <label for="dia" class="form-label">Día</label>
                        <select name="dia" id="dia" class="form-select">
                            @foreach (\App\Models\Horario::DIAS as $numero => $dia)
                                <option value="{{ $numero }}" @selected(old('dia') == $numero)>{{ $dia }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col"><x-campo nombre="hora_inicio" etiqueta="Desde" tipo="time" requerido /></div>
                        <div class="col"><x-campo nombre="hora_fin" etiqueta="Hasta" tipo="time" requerido /></div>
                    </div>
                    <button class="btn btn-primary w-100">Agregar</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
