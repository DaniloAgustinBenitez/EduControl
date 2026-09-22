@extends('layouts.app')
@section('titulo', 'Alumnos')
@section('contenido')
<x-encabezado titulo="Alumnos" :nuevo="route('alumnos.create')" />
<x-tabla :columnas="['Legajo', 'Alumno', 'DNI', 'Curso', 'Tutores']">
    @foreach ($alumnos as $alumno)
        <tr>
            <td>{{ $alumno->legajo }}</td>
            <td>{{ $alumno->nombreCompleto() }}</td>
            <td>{{ $alumno->dni }}</td>
            <td>{{ $alumno->curso?->nombreCompleto() ?? '—' }}</td>
            <td>{{ $alumno->tutores->map->nombreCompleto()->join('; ') ?: '—' }}</td>
            <x-acciones :editar="route('alumnos.edit', $alumno)" :eliminar="route('alumnos.destroy', $alumno)"
                mensaje="Se eliminará el alumno junto con todas sus inscripciones. ¿Continuar?">
                <button type="button" class="btn btn-sm btn-outline-secondary" title="Asignar curso"
                    data-bs-toggle="modal" data-bs-target="#modalCurso"
                    data-accion="{{ route('alumnos.curso', $alumno) }}" data-alumno="{{ $alumno->nombreCompleto() }}" data-curso="{{ $alumno->curso_id }}">
                    <i class="bi bi-mortarboard"></i>
                </button>
            </x-acciones>
        </tr>
    @endforeach
</x-tabla>

{{-- RN-01: un único modal para asignar curso, compartido por todas las filas. --}}
<div class="modal fade" id="modalCurso" tabindex="-1" aria-labelledby="modalCursoTitulo" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header">
                <h2 class="modal-title h5" id="modalCursoTitulo">Asignar curso</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Alumno: <strong data-campo="alumno"></strong></p>
                <select name="curso_id" class="form-select" aria-label="Curso">
                    <option value="">— Sin curso —</option>
                    @foreach ($cursos as $curso)
                        <option value="{{ $curso->id }}">{{ $curso->nombreCompleto() }}</option>
                    @endforeach
                </select>
                <div class="form-text">Un alumno pertenece a un solo curso. Para cambiarlo, primero elegí "Sin curso".</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('modalCurso').addEventListener('show.bs.modal', e => {
        const boton = e.relatedTarget, modal = e.target;
        modal.querySelector('form').action = boton.dataset.accion;
        modal.querySelector('[data-campo=alumno]').textContent = boton.dataset.alumno;
        modal.querySelector('select').value = boton.dataset.curso;
    });
</script>
@endpush
