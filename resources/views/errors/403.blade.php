@php
    // El mensaje por defecto de Laravel viene en inglés: en ese caso mostramos uno propio.
    $mensaje = $exception?->getMessage();
    $generico = ! $mensaje || $mensaje === 'This action is unauthorized.' || $mensaje === 'Forbidden';
@endphp

@include('errors.plantilla', [
    'codigo' => 403,
    'icono' => 'bi-shield-lock',
    'color' => 'ec-acento-ambar',
    'titulo' => 'No tenés permiso para ver esta pantalla',
    'detalle' => $generico
        ? 'Tu perfil no tiene acceso a esta sección. Si creés que es un error, avisale a la administración.'
        : $mensaje,
])
