<?php

namespace Tests\Feature\Api;

use App\Models\Curso;
use App\Models\User;
use App\Support\Rol;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Panel del docente por la API (RF-28, HU4): solo sus cursos y sus materias. */
class DocenteTest extends TestCase
{
    use ConToken, RefreshDatabase;

    protected $seed = true;

    protected $seeder = DatabaseSeeder::class;

    private const HORARIO = ['dia', 'dia_nombre', 'inicio', 'fin', 'descripcion'];

    private const CURSO = ['id', 'nombre', 'nivel', 'nombre_completo', 'horarios' => ['*' => self::HORARIO]];

    private function curso(string $nombre): Curso
    {
        return Curso::where('nombre', $nombre)->firstOrFail();
    }

    public function test_cursos_con_las_materias_del_docente(): void
    {
        $this->como('docente@educontrol.test')->getJson('/api/v1/docente/cursos')
            ->assertOk()
            ->assertJsonStructure(['cursos' => ['*' => self::CURSO + ['materias' => ['*' => ['id', 'nombre']]]]])
            ->assertJsonCount(1, 'cursos')
            ->assertJsonPath('cursos.0.nombre_completo', '1° año — Secundario')
            ->assertJsonCount(5, 'cursos.0.horarios')
            ->assertJsonPath('cursos.0.horarios.0.inicio', '07:30')
            // El curso tiene seis materias, pero el docente dicta solo dos.
            ->assertJsonPath('cursos.0.materias.*.nombre', ['Biología', 'Matemática']);
    }

    public function test_usuario_sin_profesor_vinculado_no_tiene_cursos(): void
    {
        $user = User::create(['name' => 'Sin Legajo', 'email' => 'suelto@educontrol.test', 'password' => 'password']);
        $user->assignRole(Rol::DOCENTE);

        $this->como('suelto@educontrol.test')->getJson('/api/v1/docente/cursos')
            ->assertOk()
            ->assertExactJson(['cursos' => []]);
    }

    public function test_detalle_del_curso_con_nomina_ordenada(): void
    {
        $this->como('docente@educontrol.test')->getJson('/api/v1/docente/cursos/'.$this->curso('1° año')->id)
            ->assertOk()
            ->assertJsonStructure([
                'curso' => self::CURSO,
                'materias' => ['*' => ['id', 'nombre']],
                'alumnos' => ['*' => ['id', 'legajo', 'dni', 'nombre', 'apellido', 'nombre_completo']],
            ])
            ->assertJsonMissingPath('curso.materias')
            ->assertJsonPath('materias.*.nombre', ['Biología', 'Matemática'])
            ->assertJsonPath('alumnos.*.nombre_completo', ['Fernández, Lucía', 'Pérez, Sofía'])
            ->assertJsonPath('alumnos.1.dni', '50111001');
    }

    public function test_curso_no_asignado_responde_403(): void
    {
        $this->como('docente@educontrol.test')->getJson('/api/v1/docente/cursos/'.$this->curso('2° año')->id)
            ->assertForbidden()
            ->assertExactJson(['message' => 'Ese curso no está asignado a tu cuenta.']);
    }

    public function test_curso_inexistente_responde_404(): void
    {
        $this->como('docente@educontrol.test')->getJson('/api/v1/docente/cursos/9999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'No se encontró lo que buscabas.']);
    }

    public function test_el_tutor_no_entra_al_panel_docente(): void
    {
        $this->como('tutor@educontrol.test');

        foreach (['/api/v1/docente/cursos', '/api/v1/docente/cursos/'.$this->curso('1° año')->id] as $url) {
            $this->getJson($url)
                ->assertForbidden()
                ->assertExactJson(['message' => 'Tu perfil no tiene acceso a esta sección.']);
        }
    }
}
