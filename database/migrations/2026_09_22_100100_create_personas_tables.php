<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cada cuenta de usuario se vincula con su entidad real (RF-03, tarea 2.3).
        foreach (['profesores', 'tutores', 'administrativos'] as $tabla) {
            Schema::create($tabla, function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
                $table->string('dni', 15)->unique();
                $table->string('nombre', 60);
                $table->string('apellido', 60)->index();
                $table->string('telefono', 30)->nullable();
                $table->timestamps();
            });
        }

        // RN-01: un alumno tiene a lo sumo un curso asignado.
        Schema::create('alumnos', function (Blueprint $table) {
            $table->id();
            $table->string('legajo', 20)->unique();
            $table->string('dni', 15)->unique();
            $table->string('nombre', 60);
            $table->string('apellido', 60)->index();
            $table->date('fecha_nacimiento')->nullable();
            $table->foreignId('curso_id')->nullable()->constrained('cursos')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('alumno_tutor', function (Blueprint $table) {
            $table->foreignId('alumno_id')->constrained('alumnos')->cascadeOnDelete();
            $table->foreignId('tutor_id')->constrained('tutores')->cascadeOnDelete();
            $table->string('parentesco', 30)->nullable();
            $table->primary(['alumno_id', 'tutor_id']);
        });

        Schema::create('materia_profesor', function (Blueprint $table) {
            $table->foreignId('materia_id')->constrained('materias')->cascadeOnDelete();
            $table->foreignId('profesor_id')->constrained('profesores')->cascadeOnDelete();
            $table->primary(['materia_id', 'profesor_id']);
        });

        Schema::create('curso_profesor', function (Blueprint $table) {
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->foreignId('profesor_id')->constrained('profesores')->cascadeOnDelete();
            $table->primary(['curso_id', 'profesor_id']);
        });

        Schema::create('nivel_profesor', function (Blueprint $table) {
            $table->foreignId('nivel_id')->constrained('niveles')->cascadeOnDelete();
            $table->foreignId('profesor_id')->constrained('profesores')->cascadeOnDelete();
            $table->primary(['nivel_id', 'profesor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nivel_profesor');
        Schema::dropIfExists('curso_profesor');
        Schema::dropIfExists('materia_profesor');
        Schema::dropIfExists('alumno_tutor');
        Schema::dropIfExists('alumnos');
        Schema::dropIfExists('administrativos');
        Schema::dropIfExists('tutores');
        Schema::dropIfExists('profesores');
    }
};
