<?php

namespace Database\Seeders;

use App\Models\CicloLectivo;
use App\Models\Curso;
use App\Models\Deporte;
use App\Models\Nivel;
use App\Models\Recorrido;
use Illuminate\Database\Seeder;

class InstitucionSeeder extends Seeder
{
    public function run(): void
    {
        CicloLectivo::firstOrCreate(['anio' => 2026], ['activo' => true]);

        // nivel => [cursos, materias, horario de cursado de lunes a viernes]
        $estructura = [
            'Inicial' => [['Sala de 4', 'Sala de 5'], ['Juego', 'Expresión artística'], ['08:00', '12:00']],
            'Primario' => [['1° grado', '2° grado', '3° grado', '4° grado', '5° grado', '6° grado'], ['Matemática', 'Prácticas del lenguaje', 'Ciencias naturales', 'Ciencias sociales', 'Educación física'], ['08:00', '12:30']],
            'Secundario' => [['1° año', '2° año', '3° año', '4° año', '5° año'], ['Matemática', 'Lengua y literatura', 'Historia', 'Biología', 'Inglés', 'Educación física'], ['07:30', '13:00']],
        ];

        foreach ($estructura as $nombreNivel => [$cursos, $materias, [$desde, $hasta]]) {
            $nivel = Nivel::firstOrCreate(['nombre' => $nombreNivel]);

            foreach ($cursos as $nombreCurso) {
                $curso = Curso::firstOrCreate(['nivel_id' => $nivel->id, 'nombre' => $nombreCurso]);

                foreach ($materias as $materia) {
                    $curso->materias()->firstOrCreate(['nombre' => $materia]);
                }

                if ($curso->horarios()->doesntExist()) {
                    foreach (range(1, 5) as $dia) {
                        $curso->horarios()->create(['dia' => $dia, 'hora_inicio' => $desde, 'hora_fin' => $hasta]);
                    }
                }
            }
        }

        // Básquet se superpone con Fútbol a propósito, para mostrar la validación RN-04.
        $deportes = [
            'Fútbol' => [[1, '14:00', '15:30'], [3, '14:00', '15:30']],
            'Vóley' => [[2, '14:00', '15:30'], [4, '14:00', '15:30']],
            'Básquet' => [[1, '15:00', '16:30'], [3, '15:00', '16:30']],
            'Natación' => [[5, '14:00', '15:00']],
            'Hándbol' => [[2, '16:00', '17:30']],
            'Atletismo' => [[6, '09:00', '11:00']],
        ];

        foreach ($deportes as $nombre => $horarios) {
            $deporte = Deporte::firstOrCreate(['nombre' => $nombre]);

            if ($deporte->horarios()->doesntExist()) {
                foreach ($horarios as [$dia, $desde, $hasta]) {
                    $deporte->horarios()->create(['dia' => $dia, 'hora_inicio' => $desde, 'hora_fin' => $hasta]);
                }
            }
        }

        foreach (['Norte' => 'Barrios del norte y ruta provincial', 'Sur' => 'Barrios del sur y zona industrial', 'Este' => 'Centro y costanera', 'Oeste' => 'Barrios del oeste y country'] as $nombre => $descripcion) {
            Recorrido::firstOrCreate(['nombre' => $nombre], ['descripcion' => $descripcion]);
        }
    }
}
