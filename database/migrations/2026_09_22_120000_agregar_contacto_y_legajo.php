<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RF-01: el alumno debe tener datos de contacto.
 * RF-02: el profesor debe tener legajo, igual que el alumno.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alumnos', function (Blueprint $table) {
            $table->string('telefono', 30)->nullable()->after('fecha_nacimiento');
            $table->string('email', 255)->nullable()->after('telefono');
            $table->string('direccion', 160)->nullable()->after('email');
        });

        Schema::table('profesores', function (Blueprint $table) {
            $table->string('legajo', 20)->nullable()->unique()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('profesores', function (Blueprint $table) {
            $table->dropColumn('legajo');
        });

        Schema::table('alumnos', function (Blueprint $table) {
            $table->dropColumn(['telefono', 'email', 'direccion']);
        });
    }
};
