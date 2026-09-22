<?php

namespace Database\Seeders;

use App\Models\CicloLectivo;
use App\Models\Curso;
use App\Models\Deporte;
use App\Models\Recorrido;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Volumen realista para medir los reportes (tarea 6.7, RNF-07).
 * Uso: php artisan db:seed --class=VolumenSeeder
 */
class VolumenSeeder extends Seeder
{
    public const ALUMNOS = 3000;

    public function run(): void
    {
        $faker = fake('es_AR');
        $ciclo = CicloLectivo::actual();
        $cursos = Curso::pluck('id');
        $recorridos = Recorrido::pluck('id');
        $deportes = Deporte::pluck('id');
        $ahora = now();
        $base = (int) DB::table('alumnos')->max('id');

        foreach (array_chunk(range(1, self::ALUMNOS), 500) as $lote) {
            DB::table('alumnos')->insert(array_map(fn ($n) => [
                'legajo' => sprintf('V-%05d', $base + $n),
                'dni' => (string) (40000000 + $base + $n),
                'nombre' => $faker->firstName(),
                'apellido' => $faker->lastName(),
                'fecha_nacimiento' => $faker->dateTimeBetween('-18 years', '-4 years')->format('Y-m-d'),
                'telefono' => $faker->numerify('3794-######'),
                'email' => $faker->unique()->safeEmail(),
                'curso_id' => $cursos->random(),
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ], $lote));
        }

        $alumnos = DB::table('alumnos')->where('legajo', 'like', 'V-%')->get(['id', 'curso_id']);
        $cursado = $comedor = $transporte = $deporte = [];

        foreach ($alumnos as $alumno) {
            $fila = ['alumno_id' => $alumno->id, 'ciclo_lectivo_id' => $ciclo->id, 'created_at' => $ahora, 'updated_at' => $ahora];
            $cursado[] = $fila + ['curso_id' => $alumno->curso_id];
            if (random_int(1, 100) <= 60) {
                $comedor[] = $fila;
            }
            if (random_int(1, 100) <= 40) {
                $transporte[] = $fila + ['recorrido_id' => $recorridos->random()];
            }
            // Deportes elegidos sin validar superposición: solo sirve para medir volumen.
            foreach ($deportes->random(random_int(0, 2)) as $deporteId) {
                $deporte[] = $fila + ['deporte_id' => $deporteId];
            }
        }

        foreach (['inscripciones_cursado' => $cursado, 'inscripciones_comedor' => $comedor, 'inscripciones_transporte' => $transporte, 'inscripciones_deporte' => $deporte] as $tabla => $filas) {
            foreach (array_chunk($filas, 500) as $lote) {
                DB::table($tabla)->insert($lote);
            }
        }
    }
}
