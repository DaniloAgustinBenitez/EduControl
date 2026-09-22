<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Ingresar') · {{ config('app.name') }}</title>
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
        <div class="card ec-acceso-caja">
            <div class="row g-0">
                <div class="col-lg-6 ec-acceso-marca d-none d-lg-flex">
                    <span class="ec-logo mb-3" style="width: 48px; height: 48px; font-size: 1.5rem; background: rgba(255,255,255,.18)">
                        <i class="bi bi-mortarboard-fill"></i>
                    </span>
                    <h1>{{ config('app.name') }}</h1>
                    <p class="mb-4 opacity-75">Gestión escolar: inscripciones, cursos y reportes en un solo lugar.</p>
                    <ul class="list-unstyled mb-0">
                        <li><i class="bi bi-check2-circle me-2"></i> Inscripciones online para las familias</li>
                        <li><i class="bi bi-check2-circle me-2"></i> Cursado, comedor, transporte y deportes</li>
                        <li><i class="bi bi-check2-circle me-2"></i> Reportes exportables a PDF y Excel</li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <div class="p-4 p-lg-5">
                        @include('partials.avisos')
                        @yield('contenido')
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
