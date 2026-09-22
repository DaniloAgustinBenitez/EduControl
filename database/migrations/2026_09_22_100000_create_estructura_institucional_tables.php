<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('niveles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60)->unique();
            $table->timestamps();
        });

        // RN-02: todo curso pertenece obligatoriamente a un nivel.
        Schema::create('cursos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nivel_id')->constrained('niveles')->restrictOnDelete();
            $table->string('nombre', 60);
            $table->timestamps();
            $table->unique(['nivel_id', 'nombre']);
        });

        Schema::create('materias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->string('nombre', 80);
            $table->timestamps();
            $table->unique(['curso_id', 'nombre']);
        });

        Schema::create('deportes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60)->unique();
            $table->string('descripcion')->nullable();
            $table->timestamps();
        });

        Schema::create('recorridos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60)->unique();
            $table->string('descripcion')->nullable();
            $table->timestamps();
        });

        // RN-04 / RN-05: el horario es una entidad propia, compartida por deportes y cursos.
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->morphs('horarioable');
            $table->unsignedTinyInteger('dia'); // 1 = lunes ... 7 = domingo
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->timestamps();
        });

        Schema::create('ciclos_lectivos', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anio')->unique();
            $table->boolean('activo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ciclos_lectivos');
        Schema::dropIfExists('horarios');
        Schema::dropIfExists('recorridos');
        Schema::dropIfExists('deportes');
        Schema::dropIfExists('materias');
        Schema::dropIfExists('cursos');
        Schema::dropIfExists('niveles');
    }
};
