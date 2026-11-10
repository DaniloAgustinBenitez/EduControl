<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AutogestionController;
use App\Http\Controllers\Api\DocenteController;
use App\Services\InscripcionService;
use Illuminate\Support\Facades\Route;

/*
 * API REST de la app mobile (RF-25). Contrato en docs/api.md.
 * Se autentica con token de Sanctum y usa los mismos permisos de Spatie que la web.
 */
Route::prefix('v1')->name('api.')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('perfil', [AuthController::class, 'perfil'])->name('perfil');

        // RF-27 — Autogestión del tutor
        Route::middleware('permission:inscripciones.autogestion')->group(function () {
            Route::get('hijos', [AutogestionController::class, 'index'])->name('hijos.index');
            Route::get('hijos/{alumno}', [AutogestionController::class, 'show'])->name('hijos.show');
            Route::get('catalogo', [AutogestionController::class, 'catalogo'])->name('catalogo');
            Route::post('hijos/{alumno}/inscripciones/{servicio}', [AutogestionController::class, 'inscribir'])
                ->whereIn('servicio', InscripcionService::SERVICIOS)->name('hijos.inscribir');
            Route::delete('hijos/{alumno}/inscripciones/{servicio}/{inscripcion}', [AutogestionController::class, 'baja'])
                ->whereIn('servicio', InscripcionService::SERVICIOS)->whereNumber('inscripcion')->name('hijos.baja');
        });

        // RF-28 — Panel del docente
        Route::middleware('permission:docente.panel')->prefix('docente')->name('docente.')->group(function () {
            Route::get('cursos', [DocenteController::class, 'index'])->name('cursos');
            Route::get('cursos/{curso}', [DocenteController::class, 'curso'])->name('curso');
        });
    });
});
