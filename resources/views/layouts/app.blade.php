<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Inicio') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.min.css">
    <style>
        body { background: #f5f6f8; }
        .navbar-brand { font-weight: 600; }
        .card { border: 0; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .stat { font-size: 2rem; font-weight: 600; }
    </style>
</head>
<body>
@auth
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ route('inicio') }}"><i class="bi bi-mortarboard"></i> {{ config('app.name') }}</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-label="Menú">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav me-auto">
                @can('configuracion.gestionar')
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Configuración</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('ciclos.index') }}">Ciclos lectivos</a></li>
                            <li><a class="dropdown-item" href="{{ route('niveles.index') }}">Niveles</a></li>
                            <li><a class="dropdown-item" href="{{ route('cursos.index') }}">Cursos</a></li>
                            <li><a class="dropdown-item" href="{{ route('materias.index') }}">Materias</a></li>
                            <li><a class="dropdown-item" href="{{ route('deportes.index') }}">Deportes</a></li>
                            <li><a class="dropdown-item" href="{{ route('recorridos.index') }}">Recorridos</a></li>
                        </ul>
                    </li>
                @endcan
                @can('usuarios.gestionar')
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Personas</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('alumnos.index') }}">Alumnos</a></li>
                            <li><a class="dropdown-item" href="{{ route('profesores.index') }}">Profesores</a></li>
                            <li><a class="dropdown-item" href="{{ route('tutores.index') }}">Tutores</a></li>
                        </ul>
                    </li>
                @endcan
                @can('reportes.ver')
                    <li class="nav-item"><a class="nav-link" href="{{ route('reportes.index') }}">Reportes</a></li>
                @endcan
                @can('docente.panel')
                    <li class="nav-item"><a class="nav-link" href="{{ route('docente.index') }}">Mis cursos</a></li>
                @endcan
                @can('inscripciones.autogestion')
                    <li class="nav-item"><a class="nav-link" href="{{ route('autogestion.index') }}">Mis hijos</a></li>
                @endcan
            </ul>
            <span class="navbar-text text-white me-3">
                <i class="bi bi-person-circle"></i> {{ auth()->user()->name }}
                <small class="opacity-75">({{ \App\Support\Rol::ETIQUETAS[auth()->user()->getRoleNames()->first()] ?? 'sin rol' }})</small>
            </span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-outline-light btn-sm">Salir</button>
            </form>
        </div>
    </div>
</nav>
@endauth

<main class="container pb-5">
    @if (session('ok'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('ok') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @yield('contenido')
</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.min.js"></script>
<script>
    // Componente de tabla reutilizable (tarea 3.1): toda tabla con clase .datatable se inicializa sola.
    $('table.datatable').each(function () {
        new DataTable(this, {
            responsive: true,
            pageLength: 25,
            order: [],
            columnDefs: [{ targets: 'no-orden', orderable: false, searchable: false }],
            language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/es-ES.json' },
        });
    });

    // Confirmación antes de eliminar.
    document.querySelectorAll('form[data-confirmar]').forEach(form => {
        form.addEventListener('submit', e => { if (!confirm(form.dataset.confirmar)) e.preventDefault(); });
    });
</script>
@stack('scripts')
</body>
</html>
