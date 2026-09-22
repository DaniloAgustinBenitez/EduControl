@extends('layouts.app')
@section('titulo', 'Asignaciones')
@section('contenido')
<x-encabezado :titulo="'Asignaciones de '.$profesor->nombreCompleto()">
    <a href="{{ route('profesores.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
</x-encabezado>

<p class="text-muted">Al asignar una materia se agregan solos su curso y su nivel.</p>

<form method="POST" action="{{ route('profesores.asignaciones.guardar', $profesor) }}">
    @csrf @method('PUT')
    <div class="row g-3">
        <div class="col-md-6 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <h2 class="h6">Niveles (RF-06)</h2>
                @foreach ($niveles as $nivel)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="niveles[]" value="{{ $nivel->id }}" id="nivel{{ $nivel->id }}" @checked($profesor->niveles->contains($nivel))>
                        <label class="form-check-label" for="nivel{{ $nivel->id }}">{{ $nivel->nombre }}</label>
                    </div>
                @endforeach
                <h2 class="h6 mt-3">Cursos (RF-06)</h2>
                @foreach ($cursos as $curso)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="cursos[]" value="{{ $curso->id }}" id="curso{{ $curso->id }}" @checked($profesor->cursos->contains($curso))>
                        <label class="form-check-label" for="curso{{ $curso->id }}">{{ $curso->nombreCompleto() }}</label>
                    </div>
                @endforeach
            </div></div>
        </div>
        <div class="col-md-6 col-lg-9">
            <div class="card h-100"><div class="card-body">
                <h2 class="h6">Materias (RF-05)</h2>
                <div class="row">
                    @foreach ($materias as $curso => $lista)
                        <div class="col-lg-4 mb-3">
                            <div class="fw-semibold small text-muted">{{ $curso }}</div>
                            @foreach ($lista as $materia)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="materias[]" value="{{ $materia->id }}" id="materia{{ $materia->id }}" @checked($profesor->materias->contains($materia))>
                                    <label class="form-check-label" for="materia{{ $materia->id }}">{{ $materia->nombre }}</label>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div></div>
        </div>
    </div>
    <div class="text-end mt-3"><button class="btn btn-primary">Guardar asignaciones</button></div>
</form>
@endsection
