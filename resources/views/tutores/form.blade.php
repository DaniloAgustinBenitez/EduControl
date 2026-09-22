@extends('layouts.app')
@section('titulo', 'Tutor')
@section('contenido')
<x-formulario :modelo="$tutor" titulo="tutor" :crear="route('tutores.store')" :actualizar="$tutor->exists ? route('tutores.update', $tutor) : ''" :volver="route('tutores.index')">
    @include('partials.persona', ['persona' => $tutor])

    <h2 class="h6 mt-2">Alumnos a cargo</h2>
    <div class="row">
        <div class="col-md-8 mb-3">
            @php($vinculados = old('alumnos', $tutor->alumnos->pluck('id')->all()))
            <select name="alumnos[]" class="form-select" multiple size="8" aria-label="Alumnos a cargo">
                @foreach ($alumnos as $alumno)
                    <option value="{{ $alumno->id }}" @selected(in_array($alumno->id, $vinculados))>
                        {{ $alumno->nombreCompleto() }} — {{ $alumno->legajo }}{{ $alumno->curso ? ' ('.$alumno->curso->nombre.')' : '' }}
                    </option>
                @endforeach
            </select>
            <div class="form-text">Mantené Ctrl apretado para elegir más de uno.</div>
        </div>
        <div class="col-md-4">
            <x-campo nombre="parentesco" etiqueta="Parentesco" :valor="$tutor->alumnos->first()?->pivot->parentesco" maxlength="30" placeholder="Madre, padre, tutor legal…" />
        </div>
    </div>
</x-formulario>
@endsection
