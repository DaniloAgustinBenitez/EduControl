@props(['titulo', 'nuevo' => null, 'textoNuevo' => 'Nuevo'])

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">{{ $titulo }}</h1>
    <div class="d-flex gap-2">
        {{ $slot }}
        @if ($nuevo)
            <a href="{{ $nuevo }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> {{ $textoNuevo }}</a>
        @endif
    </div>
</div>
