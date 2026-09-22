<?php

namespace Database\Seeders;

use App\Models\Administrativo;
use App\Models\Alumno;
use App\Models\Curso;
use App\Models\Materia;
use App\Models\Profesor;
use App\Models\Tutor;
use App\Models\User;
use App\Support\Rol;
use Illuminate\Database\Seeder;

/**
 * Un usuario de prueba por rol (tarea 2.6). Contraseña de todos: "password".
 */
class UsuariosPruebaSeeder extends Seeder
{
    public function run(): void
    {
        $this->usuario('admin@educacion.test', 'Ana Administradora', Rol::ADMINISTRADOR);

        $administrativo = $this->usuario('administrativo@educacion.test', 'Pablo Gómez', Rol::ADMINISTRATIVO);
        Administrativo::firstOrCreate(['dni' => '20111222'], ['user_id' => $administrativo->id, 'nombre' => 'Pablo', 'apellido' => 'Gómez']);

        $docenteUser = $this->usuario('docente@educacion.test', 'Laura Martínez', Rol::DOCENTE);
        $profesor = Profesor::firstOrCreate(['dni' => '25333444'], ['user_id' => $docenteUser->id, 'nombre' => 'Laura', 'apellido' => 'Martínez']);
        $primero = Curso::where('nombre', '1° año')->firstOrFail();
        $profesor->niveles()->syncWithoutDetaching([$primero->nivel_id]);
        $profesor->cursos()->syncWithoutDetaching([$primero->id]);
        $profesor->materias()->syncWithoutDetaching(
            Materia::where('curso_id', $primero->id)->whereIn('nombre', ['Matemática', 'Biología'])->pluck('id')
        );

        $tutorUser = $this->usuario('tutor@educacion.test', 'Carlos Pérez', Rol::TUTOR);
        $tutor = Tutor::firstOrCreate(['dni' => '28555666'], ['user_id' => $tutorUser->id, 'nombre' => 'Carlos', 'apellido' => 'Pérez']);

        $hijos = [
            ['legajo' => 'A-0001', 'dni' => '50111001', 'nombre' => 'Sofía', 'apellido' => 'Pérez', 'curso' => '1° año'],
            ['legajo' => 'A-0002', 'dni' => '52111002', 'nombre' => 'Tomás', 'apellido' => 'Pérez', 'curso' => '4° grado'],
        ];
        foreach ($hijos as $datos) {
            $alumno = Alumno::firstOrCreate(['legajo' => $datos['legajo']], collect($datos)->except('curso')->all());
            $alumno->curso()->associate(Curso::where('nombre', $datos['curso'])->first())->save();
            $tutor->alumnos()->syncWithoutDetaching([$alumno->id => ['parentesco' => 'Padre']]);
        }

        // Un alumno que no es hijo del tutor de prueba, para verificar RN-07.
        $ajeno = Alumno::firstOrCreate(['legajo' => 'A-0003'], ['dni' => '50111003', 'nombre' => 'Lucía', 'apellido' => 'Fernández']);
        $ajeno->curso()->associate($primero)->save();
    }

    private function usuario(string $email, string $nombre, string $rol): User
    {
        $user = User::firstOrCreate(['email' => $email], ['name' => $nombre, 'password' => 'password']);
        $user->syncRoles([$rol]);

        return $user;
    }
}
