<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Curso;
use App\Models\Materia;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica los requerimientos del documento que se corrigieron después de la primera entrega:
 * RF-01 (contacto del alumno), RF-02 (legajo del profesor), RF-13 y RF-16 (contenido de los
 * reportes) y RNF-05 (errores en lenguaje claro).
 */
class RequerimientosTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected $seeder = DatabaseSeeder::class;

    private function comoAdministrativo(): static
    {
        return $this->actingAs(User::where('email', 'administrativo@educontrol.test')->first());
    }

    public function test_rf01_el_alumno_exige_fecha_de_nacimiento_y_contacto(): void
    {
        $this->comoAdministrativo()
            ->post('/alumnos', ['legajo' => 'A-9001', 'dni' => '55000111', 'nombre' => 'Mora', 'apellido' => 'Quiroga'])
            ->assertSessionHasErrors(['fecha_nacimiento', 'telefono']);

        $this->post('/alumnos', [
            'legajo' => 'A-9001', 'dni' => '55000111', 'nombre' => 'Mora', 'apellido' => 'Quiroga',
            'fecha_nacimiento' => '2014-05-20', 'telefono' => '3794-100200',
            'email' => 'mora@example.com', 'direccion' => 'Belgrano 450',
        ])->assertSessionHasNoErrors();

        $alumno = Alumno::where('legajo', 'A-9001')->first();
        $this->assertSame('3794-100200', $alumno->telefono);
        $this->assertSame('mora@example.com', $alumno->email);
        $this->assertSame('Belgrano 450', $alumno->direccion);
    }

    public function test_rf02_el_profesor_exige_legajo_y_no_se_repite(): void
    {
        $base = ['nombre' => 'Ana', 'apellido' => 'Rivas', 'dni' => '26111222', 'email' => 'ana@example.com', 'password' => 'secreta123'];

        $this->comoAdministrativo()->post('/profesores', $base)->assertSessionHasErrors('legajo');

        $this->post('/profesores', $base + ['legajo' => 'P-0002'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('profesores', ['legajo' => 'P-0002', 'dni' => '26111222']);

        // El legajo es único, igual que el del alumno.
        $this->post('/profesores', [
            'legajo' => 'P-0002', 'nombre' => 'Luis', 'apellido' => 'Paz', 'dni' => '26111333',
            'email' => 'luis@example.com', 'password' => 'secreta123',
        ])->assertSessionHasErrors('legajo');
    }

    public function test_rf13_el_reporte_por_alumno_incluye_materias_y_contacto(): void
    {
        $respuesta = $this->comoAdministrativo()->get('/reportes/alumno');

        $respuesta->assertOk()
            ->assertSeeText('Materias')
            ->assertSeeText('Contacto')
            ->assertSeeText('familia.perez@example.com');

        // Las materias que se listan son las del curso del alumno.
        $materia = Curso::where('nombre', '1° año')->first()->materias()->first();
        $respuesta->assertSeeText($materia->nombre);
    }

    public function test_rf16_el_reporte_por_materia_lista_la_nomina_de_alumnos(): void
    {
        $materia = Materia::whereHas('curso', fn ($q) => $q->where('nombre', '1° año'))->first();

        // Sin filtro: resumen por materia.
        $this->comoAdministrativo()->get('/reportes/materia')->assertOk()->assertSeeText('Docentes');

        // Con una materia elegida: nómina alumno por alumno.
        $this->get('/reportes/materia?filtro='.$materia->id)
            ->assertOk()
            ->assertSeeText('Legajo')
            ->assertSeeText('Pérez, Sofía')
            ->assertSeeText('Fernández, Lucía');
    }

    public function test_rnf05_los_errores_se_informan_en_espanol(): void
    {
        $errores = $this->comoAdministrativo()
            ->post('/alumnos', ['legajo' => '', 'dni' => Alumno::first()->dni])
            ->assertSessionHasErrors(['legajo', 'dni', 'nombre'])
            ->getSession()->get('errors');

        $this->assertSame('Falta completar el legajo.', $errores->first('legajo'));
        $this->assertSame('Ya existe un alumno con ese DNI.', $errores->first('dni'));
        $this->assertSame('Falta completar el nombre.', $errores->first('nombre'));
    }
}
