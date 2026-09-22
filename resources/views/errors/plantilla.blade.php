<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $codigo }} · {{ config('app.name') }}</title>
    <script>
        try {
            document.documentElement.dataset.bsTheme = localStorage.getItem('ec-tema') || 'light';
        } catch (e) {}
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/educontrol.css') }}">
</head>
<body>
<div class="ec-acceso">
    <div class="card" style="max-width: 520px">
        <div class="card-body text-center p-5">
            <span class="ec-metrica-icono mx-auto mb-3 {{ $color ?? '' }}" style="width: 64px; height: 64px; font-size: 1.75rem">
                <i class="bi {{ $icono }}"></i>
            </span>
            <div class="display-6 fw-bold" style="color: var(--ec-tinta)">{{ $codigo }}</div>
            <h1 class="h5 mt-2">{{ $titulo }}</h1>
            <p style="color: var(--ec-tenue)">{{ $detalle }}</p>
            <div class="d-flex gap-2 justify-content-center mt-4">
                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
                <a href="{{ auth()->check() ? route('inicio') : route('login') }}" class="btn btn-primary">
                    <i class="bi bi-house"></i> {{ auth()->check() ? 'Ir al inicio' : 'Ingresar' }}
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
