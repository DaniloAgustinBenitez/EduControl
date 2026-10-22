<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Curso;
use App\Models\Deporte;
use App\Models\Materia;
use App\Models\Nivel;
use App\Models\Recorrido;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** ABM de configuración institucional (RF-20 a RF-24): alta, edición, validación y baja. */
class ConfiguracionTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected $seeder = DatabaseSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::where('email', 'admin@educontrol.test')->first());
    }

    /** Inscribe a Sofía (hija del tutor de prueba) al cursado y a lo que se indique, como lo haría el tutor. */
    private function inscribirASofia(string $servicio, array $datos): void
    {
        $url = '/autogestion/alumnos/'.Alumno::where('legajo', 'A-0001')->value('id');
        $tutor = User::where('email', 'tutor@educontrol.test')->first();

        $this->actingAs($tutor)->post("$url/cursado");
        $this->actingAs($tutor)->post("$url/$servicio", $datos)->assertSessionHas('ok');
        $this->actingAs(User::where('email', 'admin@educontrol.test')->first());
    }

    public function test_abm_de_nivel(): void
    {
        $this->get('/niveles')->assertOk();
        $this->get('/niveles/create')->assertOk();

        $this->post('/niveles', ['nombre' => 'Terciario'])->assertRedirect('/niveles')->assertSessionHas('ok');
        $nivel = Nivel::where('nombre', 'Terciario')->firstOrFail();

        $this->post('/niveles', ['nombre' => 'Terciario'])->assertSessionHasErrors('nombre');

        $this->get("/niveles/{$nivel->id}/edit")->assertOk()->assertSee('Terciario');
        $this->put("/niveles/{$nivel->id}", ['nombre' => 'Nivel Terciario'])->assertSessionHas('ok');
        $this->assertSame('Nivel Terciario', $nivel->fresh()->nombre);

        $this->delete("/niveles/{$nivel->id}")->assertSessionHas('ok');
        $this->assertModelMissing($nivel);
    }

    public function test_abm_de_materia(): void
    {
        $curso = Curso::first();
        $this->get('/materias')->assertOk();
        $this->get('/materias/create')->assertOk();

        $this->post('/materias', ['curso_id' => $curso->id, 'nombre' => 'Robótica'])->assertSessionHas('ok');
        $materia = Materia::where('nombre', 'Robótica')->firstOrFail();

        // El nombre se repite solo dentro del mismo curso.
        $this->post('/materias', ['curso_id' => $curso->id, 'nombre' => 'Robótica'])->assertSessionHasErrors('nombre');
        $this->post('/materias', ['nombre' => 'Sin curso'])->assertSessionHasErrors('curso_id');

        $this->get("/materias/{$materia->id}/edit")->assertOk();
        $this->put("/materias/{$materia->id}", ['curso_id' => $curso->id, 'nombre' => 'Robótica II'])->assertSessionHas('ok');
        $this->assertSame('Robótica II', $materia->fresh()->nombre);

        $this->delete("/materias/{$materia->id}")->assertSessionHas('ok');
        $this->assertModelMissing($materia);
    }

    public function test_abm_de_deporte(): void
    {
        $this->get('/deportes')->assertOk();
        $this->get('/deportes/create')->assertOk();

        $this->post('/deportes', ['nombre' => 'Handball', 'descripcion' => 'Mixto'])->assertSessionHas('ok');
        $deporte = Deporte::where('nombre', 'Handball')->firstOrFail();

        $this->post('/deportes', ['nombre' => 'Handball'])->assertSessionHasErrors('nombre');

        $this->get("/deportes/{$deporte->id}/edit")->assertOk();
        $this->put("/deportes/{$deporte->id}", ['nombre' => 'Handball', 'descripcion' => 'Femenino'])->assertSessionHas('ok');
        $this->assertSame('Femenino', $deporte->fresh()->descripcion);

        $this->delete("/deportes/{$deporte->id}")->assertSessionHas('ok');
        $this->assertModelMissing($deporte);
    }

    public function test_no_se_elimina_un_deporte_con_inscriptos(): void
    {
        $futbol = Deporte::where('nombre', 'Fútbol')->firstOrFail();
        $this->inscribirASofia('deporte', ['deporte_id' => $futbol->id]);

        $this->delete("/deportes/{$futbol->id}")->assertSessionHas('error');
        $this->assertModelExists($futbol);
        $this->assertGreaterThan(0, $futbol->horarios()->count(), 'Los horarios no se borran si la baja falla.');
    }

    public function test_abm_de_recorrido(): void
    {
        $this->get('/recorridos')->assertOk();
        $this->get('/recorridos/create')->assertOk();

        $this->post('/recorridos', ['nombre' => 'Recorrido Oeste', 'descripcion' => 'Barrio Oeste'])->assertSessionHas('ok');
        $recorrido = Recorrido::where('nombre', 'Recorrido Oeste')->firstOrFail();

        $this->post('/recorridos', ['nombre' => 'Recorrido Oeste'])->assertSessionHasErrors('nombre');

        $this->get("/recorridos/{$recorrido->id}/edit")->assertOk();
        $this->put("/recorridos/{$recorrido->id}", ['nombre' => 'Recorrido Oeste', 'descripcion' => 'Oeste y centro'])->assertSessionHas('ok');
        $this->assertSame('Oeste y centro', $recorrido->fresh()->descripcion);

        $this->delete("/recorridos/{$recorrido->id}")->assertSessionHas('ok');
        $this->assertModelMissing($recorrido);
    }

    public function test_no_se_elimina_un_recorrido_con_inscriptos(): void
    {
        $recorrido = Recorrido::firstOrFail();
        $this->inscribirASofia('transporte', ['recorrido_id' => $recorrido->id]);

        $this->delete("/recorridos/{$recorrido->id}")->assertSessionHas('error');
        $this->assertModelExists($recorrido);
    }
}
