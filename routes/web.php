<?php

use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AutogestionController;
use App\Http\Controllers\CicloLectivoController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\DeporteController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\NivelController;
use App\Http\Controllers\ProfesorController;
use App\Http\Controllers\RecorridoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\TutorController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'create'])->name('login');
    Route::post('login', [AuthController::class, 'store'])->middleware('throttle:6,1');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/', InicioController::class)->name('inicio');

    // Etapa 3 — Configuración institucional
    Route::middleware('permission:configuracion.gestionar')->group(function () {
        Route::resource('niveles', NivelController::class)->except('show')->parameters(['niveles' => 'nivel']);
        Route::resource('cursos', CursoController::class)->except('show');
        Route::resource('materias', MateriaController::class)->except('show');
        Route::resource('deportes', DeporteController::class)->except('show');
        Route::resource('recorridos', RecorridoController::class)->except('show');
        Route::resource('ciclos', CicloLectivoController::class)->except('show')->parameters(['ciclos' => 'ciclo']);

        Route::get('horarios/{tipo}/{id}', [HorarioController::class, 'index'])->whereIn('tipo', ['curso', 'deporte'])->name('horarios.index');
        Route::post('horarios/{tipo}/{id}', [HorarioController::class, 'store'])->whereIn('tipo', ['curso', 'deporte'])->name('horarios.store');
        Route::delete('horarios/{horario}', [HorarioController::class, 'destroy'])->name('horarios.destroy');
    });

    // Etapa 4 — Gestión de usuarios
    Route::middleware('permission:usuarios.gestionar')->group(function () {
        Route::resource('alumnos', AlumnoController::class)->except('show');
        Route::put('alumnos/{alumno}/curso', [AlumnoController::class, 'asignarCurso'])->name('alumnos.curso');
        Route::resource('profesores', ProfesorController::class)->except('show')->parameters(['profesores' => 'profesor']);
        Route::get('profesores/{profesor}/asignaciones', [ProfesorController::class, 'asignaciones'])->name('profesores.asignaciones');
        Route::put('profesores/{profesor}/asignaciones', [ProfesorController::class, 'guardarAsignaciones'])->name('profesores.asignaciones.guardar');
        Route::resource('tutores', TutorController::class)->except('show')->parameters(['tutores' => 'tutor']);
    });

    // Etapa 4.7 — Pantalla del docente
    Route::middleware('permission:docente.panel')->prefix('docente')->name('docente.')->group(function () {
        Route::get('/', [DocenteController::class, 'index'])->name('index');
        Route::get('cursos/{curso}', [DocenteController::class, 'curso'])->name('curso');
    });

    // Etapa 5 — Autogestión de inscripciones del padre/tutor
    Route::middleware('permission:inscripciones.autogestion')->prefix('autogestion')->name('autogestion.')->group(function () {
        Route::get('/', [AutogestionController::class, 'index'])->name('index');
        Route::get('alumnos/{alumno}', [AutogestionController::class, 'show'])->name('show');
        Route::post('alumnos/{alumno}/{servicio}', [AutogestionController::class, 'inscribir'])
            ->whereIn('servicio', ['cursado', 'comedor', 'transporte', 'deporte'])->name('inscribir');
        Route::delete('alumnos/{alumno}/{servicio}/{inscripcion}', [AutogestionController::class, 'baja'])
            ->whereIn('servicio', ['cursado', 'comedor', 'transporte', 'deporte'])->whereNumber('inscripcion')->name('baja');
    });

    // Etapa 6 — Reportes
    Route::middleware('permission:reportes.ver')->prefix('reportes')->name('reportes.')->group(function () {
        Route::get('/', [ReporteController::class, 'index'])->name('index');
        Route::get('{tipo}', [ReporteController::class, 'show'])
            ->whereIn('tipo', array_keys(ReporteController::TIPOS))->name('show');
    });
});
