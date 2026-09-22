<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // RF-08 / RNF-04: una inscripción al cursado por alumno y ciclo lectivo.
        Schema::create('inscripciones_cursado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumno_id')->constrained('alumnos')->cascadeOnDelete();
            $table->foreignId('ciclo_lectivo_id')->constrained('ciclos_lectivos')->restrictOnDelete();
            $table->foreignId('curso_id')->constrained('cursos')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['alumno_id', 'ciclo_lectivo_id']);
        });

        // RF-10: comedor, sin duplicados por ciclo.
        Schema::create('inscripciones_comedor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumno_id')->constrained('alumnos')->cascadeOnDelete();
            $table->foreignId('ciclo_lectivo_id')->constrained('ciclos_lectivos')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['alumno_id', 'ciclo_lectivo_id']);
        });

        // RN-06: un único recorrido de transporte por alumno y ciclo.
        Schema::create('inscripciones_transporte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumno_id')->constrained('alumnos')->cascadeOnDelete();
            $table->foreignId('ciclo_lectivo_id')->constrained('ciclos_lectivos')->restrictOnDelete();
            $table->foreignId('recorrido_id')->constrained('recorridos')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['alumno_id', 'ciclo_lectivo_id']);
        });

        // RF-09: deportes; el tope de dos (RN-03) y la superposición (RN-04/05) se validan en el servicio.
        Schema::create('inscripciones_deporte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumno_id')->constrained('alumnos')->cascadeOnDelete();
            $table->foreignId('ciclo_lectivo_id')->constrained('ciclos_lectivos')->restrictOnDelete();
            $table->foreignId('deporte_id')->constrained('deportes')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['alumno_id', 'ciclo_lectivo_id', 'deporte_id'], 'insc_deporte_unica');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripciones_deporte');
        Schema::dropIfExists('inscripciones_transporte');
        Schema::dropIfExists('inscripciones_comedor');
        Schema::dropIfExists('inscripciones_cursado');
    }
};
