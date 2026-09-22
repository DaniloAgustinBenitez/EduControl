@props(['modelo', 'crear', 'actualizar', 'volver', 'titulo'])

{{-- Formulario de alta/edición: hace POST o PUT según el modelo ya exista o no. --}}
<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h3 mb-3">{{ $modelo->exists ? 'Editar' : 'Nuevo' }} {{ $titulo }}</h1>
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ $modelo->exists ? $actualizar : $crear }}">
                    @csrf
                    @if ($modelo->exists) @method('PUT') @endif
                    {{ $slot }}
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ $volver }}" class="btn btn-outline-secondary">Cancelar</a>
                        <button class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
