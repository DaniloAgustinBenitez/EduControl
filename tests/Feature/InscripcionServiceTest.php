<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Alumno;
use App\Models\CicloLectivo;
use App\Models\Deporte;
use App\Models\Recorrido;
use App\Services\InscripcionService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InscripcionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected $seeder = DatabaseSeeder::class;

    private InscripcionService $servicio;

    private CicloLectivo $ciclo;

    private Alumno $alumno;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = app(InscripcionService::class);
        $this->ciclo = CicloLectivo::actual();
        $this->alumno = Alumno::where('legajo', 'A-0001')->first(); // 1° año, cursa 07:30 a 13:00
    }

    private function deporte(string $nombre): Deporte
    {
        return Deporte::where('nombre', $nombre)->firstOrFail();
    }

    public function test_inscribe_al_cursado_una_sola_vez_por_ciclo(): void
    {
        $inscripcion = $this->servicio->inscribirCursado($this->alumno, $this->ciclo);
        $this->assertSame($this->alumno->curso_id, $inscripcion->curso_id);

        $this->expectException(ReglaNegocioException::class);
        $this->servicio->inscribirCursado($this->alumno, $this->ciclo);
    }

    public function test_no_inscribe_al_cursado_sin_curso_asignado(): void
    {
        $sinCurso = Alumno::create(['legajo' => 'X-1', 'dni' => '1', 'nombre' => 'Sin', 'apellido' => 'Curso']);

        $this->expectException(ReglaNegocioException::class);
        $this->servicio->inscribirCursado($sinCurso, $this->ciclo);
    }

    public function test_comedor_sin_duplicados(): void
    {
        $this->servicio->inscribirComedor($this->alumno, $this->ciclo);

        $this->expectException(ReglaNegocioException::class);
        $this->servicio->inscribirComedor($this->alumno, $this->ciclo);
    }

    public function test_rn06_un_unico_recorrido(): void
    {
        [$norte, $sur] = Recorrido::orderBy('id')->take(2)->get();
        $this->servicio->inscribirTransporte($this->alumno, $this->ciclo, $norte);

        try {
            $this->servicio->inscribirTransporte($this->alumno, $this->ciclo, $sur);
            $this->fail('Debió rechazar un segundo recorrido');
        } catch (ReglaNegocioException $e) {
            $this->assertSame('RN-06', $e->regla);
        }
    }

    public function test_rn03_tope_de_dos_deportes(): void
    {
        $this->servicio->inscribirDeporte($this->alumno, $this->ciclo, $this->deporte('Fútbol'));
        $this->servicio->inscribirDeporte($this->alumno, $this->ciclo, $this->deporte('Vóley'));

        try {
            $this->servicio->inscribirDeporte($this->alumno, $this->ciclo, $this->deporte('Natación'));
            $this->fail('Debió rechazar un tercer deporte');
        } catch (ReglaNegocioException $e) {
            $this->assertSame('RN-03', $e->regla);
        }
    }

    public function test_rn04_rechaza_deportes_superpuestos(): void
    {
        $this->servicio->inscribirDeporte($this->alumno, $this->ciclo, $this->deporte('Fútbol'));

        try {
            $this->servicio->inscribirDeporte($this->alumno, $this->ciclo, $this->deporte('Básquet'));
            $this->fail('Básquet se superpone con Fútbol');
        } catch (ReglaNegocioException $e) {
            $this->assertSame('RN-04', $e->regla);
            $this->assertStringContainsString('Fútbol', $e->getMessage());
        }
    }

    public function test_rn05_rechaza_deporte_superpuesto_con_el_cursado(): void
    {
        $matutino = Deporte::create(['nombre' => 'Ajedrez']);
        $matutino->horarios()->create(['dia' => 2, 'hora_inicio' => '10:00', 'hora_fin' => '11:00']);

        try {
            $this->servicio->inscribirDeporte($this->alumno, $this->ciclo, $matutino);
            $this->fail('Ajedrez se superpone con el cursado');
        } catch (ReglaNegocioException $e) {
            $this->assertSame('RN-05', $e->regla);
        }
    }

    public function test_no_duplica_el_mismo_deporte(): void
    {
        $this->servicio->inscribirDeporte($this->alumno, $this->ciclo, $this->deporte('Natación'));

        $this->expectException(ReglaNegocioException::class);
        $this->servicio->inscribirDeporte($this->alumno, $this->ciclo, $this->deporte('Natación'));
    }

    public function test_baja_libera_el_cupo(): void
    {
        $futbol = $this->servicio->inscribirDeporte($this->alumno, $this->ciclo, $this->deporte('Fútbol'));

        $this->assertTrue($this->servicio->baja($this->alumno, 'deporte', $futbol->id));
        $this->servicio->inscribirDeporte($this->alumno, $this->ciclo, $this->deporte('Básquet'));

        $this->assertCount(1, $this->servicio->estado($this->alumno, $this->ciclo)['deportes']);
    }

    public function test_baja_no_afecta_inscripciones_de_otro_alumno(): void
    {
        $otro = Alumno::where('legajo', 'A-0003')->first();
        $comedor = $this->servicio->inscribirComedor($otro, $this->ciclo);

        $this->assertFalse($this->servicio->baja($this->alumno, 'comedor', $comedor->id));
        $this->assertNotNull($this->servicio->estado($otro, $this->ciclo)['comedor']);
    }
}
