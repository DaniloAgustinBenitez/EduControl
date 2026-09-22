@props(['titulo', 'subtitulo' => null, 'nuevo' => null, 'textoNuevo' => 'Nuevo', 'volver' => null])

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">{{ $titulo }}</h1>
        @if ($subtitulo)
            <p class="mb-0" style="color: var(--ec-tenue)">{{ $subtitulo }}</p>
        @endif
    </div>
    <div class="d-flex gap-2 flex-wrap">
        {{ $slot }}
        @if ($volver)
            <a href="{{ $volver }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
        @endif
        @if ($nuevo)
            <a href="{{ $nuevo }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> {{ $textoNuevo }}</a>
        @endif
    </div>
</div>
