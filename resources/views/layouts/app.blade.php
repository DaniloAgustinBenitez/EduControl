<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Inicio') · {{ config('app.name') }}</title>
    {{-- El tema se aplica antes de pintar para que no haya un destello blanco. --}}
    <script>
        try {
            document.documentElement.dataset.bsTheme = localStorage.getItem('ec-tema') || 'light';
        } catch (e) {}
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="{{ asset('css/educontrol.css') }}">
</head>
<body>
@auth
<div class="ec-layout">
    {{-- Menú lateral: fijo en escritorio, deslizable en celular --}}
    <nav class="ec-lateral offcanvas-lg offcanvas-start" tabindex="-1" id="menuLateral" aria-label="Menú principal">
        <a href="{{ route('inicio') }}" class="ec-marca">
            <span class="ec-logo"><i class="bi bi-mortarboard-fill"></i></span>
            {{ config('app.name') }}
        </a>

        <div class="ec-nav">
            <a href="{{ route('inicio') }}" @class(['activo' => request()->routeIs('inicio')])>
                <i class="bi bi-speedometer2"></i> Inicio
            </a>

            @can('inscripciones.autogestion')
                <div class="ec-nav-titulo">Familia</div>
                <a href="{{ route('autogestion.index') }}" @class(['activo' => request()->routeIs('autogestion.*')])>
                    <i class="bi bi-people-fill"></i> Mis hijos
                </a>
            @endcan

            @can('docente.panel')
                <div class="ec-nav-titulo">Docencia</div>
                <a href="{{ route('docente.index') }}" @class(['activo' => request()->routeIs('docente.*')])>
                    <i class="bi bi-journal-bookmark"></i> Mis cursos
                </a>
            @endcan

            @can('usuarios.gestionar')
                <div class="ec-nav-titulo">Personas</div>
                <a href="{{ route('alumnos.index') }}" @class(['activo' => request()->routeIs('alumnos.*')])><i class="bi bi-person-vcard"></i> Alumnos</a>
                <a href="{{ route('profesores.index') }}" @class(['activo' => request()->routeIs('profesores.*')])><i class="bi bi-person-badge"></i> Profesores</a>
                <a href="{{ route('tutores.index') }}" @class(['activo' => request()->routeIs('tutores.*')])><i class="bi bi-people"></i> Tutores</a>
            @endcan

            @can('configuracion.gestionar')
                <div class="ec-nav-titulo">Institución</div>
                <a href="{{ route('ciclos.index') }}" @class(['activo' => request()->routeIs('ciclos.*')])><i class="bi bi-calendar3"></i> Ciclos lectivos</a>
                <a href="{{ route('niveles.index') }}" @class(['activo' => request()->routeIs('niveles.*')])><i class="bi bi-diagram-2"></i> Niveles</a>
                <a href="{{ route('cursos.index') }}" @class(['activo' => request()->routeIs('cursos.*')])><i class="bi bi-door-open"></i> Cursos</a>
                <a href="{{ route('materias.index') }}" @class(['activo' => request()->routeIs('materias.*')])><i class="bi bi-journal-text"></i> Materias</a>
                <a href="{{ route('deportes.index') }}" @class(['activo' => request()->routeIs('deportes.*')])><i class="bi bi-trophy"></i> Deportes</a>
                <a href="{{ route('recorridos.index') }}" @class(['activo' => request()->routeIs('recorridos.*')])><i class="bi bi-bus-front"></i> Recorridos</a>
            @endcan

            @can('reportes.ver')
                <div class="ec-nav-titulo">Análisis</div>
                <a href="{{ route('reportes.index') }}" @class(['activo' => request()->routeIs('reportes.*')])><i class="bi bi-bar-chart"></i> Reportes</a>
            @endcan
        </div>

        <div class="ec-usuario">
            <span class="ec-avatar">{{ Str::of(auth()->user()->name)->explode(' ')->take(2)->map(fn ($p) => Str::substr($p, 0, 1))->join('') }}</span>
            <div class="flex-grow-1 min-width-0">
                <div class="fw-semibold text-truncate" style="color: var(--ec-tinta)">{{ auth()->user()->name }}</div>
                <div class="small text-truncate" style="color: var(--ec-tenue)">{{ \App\Support\Rol::ETIQUETAS[auth()->user()->getRoleNames()->first()] ?? 'Sin rol' }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-sm btn-outline-secondary border-0" title="Cerrar sesión"><i class="bi bi-box-arrow-right"></i></button>
            </form>
        </div>
    </nav>

    <div class="ec-contenido">
        <header class="ec-barra">
            <button class="btn btn-sm btn-outline-secondary d-lg-none" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#menuLateral" aria-controls="menuLateral" aria-label="Abrir menú">
                <i class="bi bi-list"></i>
            </button>
            <span class="fw-semibold d-lg-none">{{ config('app.name') }}</span>
            <div class="ms-auto d-flex align-items-center gap-2">
                @if ($cicloActivo = \App\Models\CicloLectivo::actual())
                    <span class="ec-pill ec-pill-si" title="Ciclo lectivo activo"><i class="bi bi-calendar-check"></i> {{ $cicloActivo->anio }}</span>
                @else
                    <span class="ec-pill ec-pill-no" title="No hay ciclo lectivo activo"><i class="bi bi-calendar-x"></i> Sin ciclo</span>
                @endif
                <button class="btn btn-sm btn-outline-secondary border-0" id="btnTema" title="Cambiar entre claro y oscuro" aria-label="Cambiar tema">
                    <i class="bi bi-moon-stars"></i>
                </button>
            </div>
        </header>

        <main class="container-fluid p-3 p-lg-4">
            @include('partials.avisos')
            @yield('contenido')
        </main>
    </div>
</div>
@else
    @include('partials.avisos')
    @yield('contenido')
@endauth

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

    // Tema claro / oscuro, recordado en el navegador.
    const btnTema = document.getElementById('btnTema');
    const pintarIcono = () => {
        if (!btnTema) return;
        const oscuro = document.documentElement.dataset.bsTheme === 'dark';
        btnTema.innerHTML = `<i class="bi bi-${oscuro ? 'sun' : 'moon-stars'}"></i>`;
    };
    btnTema?.addEventListener('click', () => {
        const tema = document.documentElement.dataset.bsTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.dataset.bsTheme = tema;
        try { localStorage.setItem('ec-tema', tema); } catch (e) {}
        pintarIcono();
    });
    pintarIcono();
</script>
@stack('scripts')
</body>
</html>
