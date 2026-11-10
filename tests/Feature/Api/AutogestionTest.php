<?php

namespace Tests\Feature\Api;

use App\Models\Alumno;
use App\Models\CicloLectivo;
use App\Models\Deporte;
use App\Models\InscripcionComedor;
use App\Models\Recorrido;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Autogestión del tutor por la API (RF-27): las mismas reglas que la web (RN-03 a RN-07),
 * con el formato de respuesta de docs/api.md.
 */
class AutogestionTest extends TestCase
{
    use ConToken, RefreshDatabase;

    protected $seed = true;

    protected $seeder = DatabaseSeeder::class;

    private const HORARIO = ['dia', 'dia_nombre', 'inicio', 'fin', 'descripcion'];

    private const CURSO = ['id', 'nombre', 'nivel', 'nombre_completo'];

    private const RECORRIDO = ['id', 'nombre', 'descripcion'];

    private const DEPORTE = ['id', 'nombre', 'descripcion', 'horarios' => ['*' => self::HORARIO]];

    private const ESTADO = ['cursado', 'comedor', 'transporte', 'deportes'];

    private const ALUMNO = ['id', 'legajo', 'nombre', 'apellido', 'nombre_completo', 'curso' => self::CURSO, 'estado' => self::ESTADO];

    private Alumno $sofia;

    private Alumno $ajeno;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sofia = Alumno::where('legajo', 'A-0001')->first(); // 1° año, cursa 07:30 a 13:00
        $this->ajeno = Alumno::where('legajo', 'A-0003')->first(); // no es hijo del tutor de prueba
        $this->como('tutor@educontrol.test');
    }

    private function inscribir(string $servicio, array $datos = [], ?Alumno $alumno = null): TestResponse
    {
        $alumno ??= $this->sofia;

        return $this->postJson("/api/v1/hijos/{$alumno->id}/inscripciones/{$servicio}", $datos);
    }

    private function deporte(string $nombre): array
    {
        return ['deporte_id' => Deporte::where('nombre', $nombre)->value('id')];
    }

    public function test_hijos_lista_los_alumnos_del_tutor_con_su_estado(): void
    {
        $this->inscribir('comedor')->assertCreated();

        $this->getJson('/api/v1/hijos')
            ->assertOk()
            ->assertJsonStructure(['ciclo' => ['id', 'anio'], 'hijos' => ['*' => self::ALUMNO]])
            ->assertJsonPath('ciclo.anio', 2026)
            ->assertJsonCount(2, 'hijos')
            ->assertJsonPath('hijos.0.nombre_completo', 'Pérez, Sofía')
            ->assertJsonPath('hijos.0.curso.nombre_completo', '1° año — Secundario')
            ->assertJsonPath('hijos.0.estado.comedor.id', $this->sofia->inscripcionesComedor()->value('id'))
            ->assertJsonPath('hijos.0.estado.cursado', null)
            ->assertJsonPath('hijos.0.estado.deportes', [])
            ->assertJsonPath('hijos.1.nombre', 'Tomás')
            ->assertJsonMissingPath('hijos.0.dni')
            ->assertJsonMissingPath('hijos.0.telefono')
            ->assertJsonMissingPath('hijos.0.email')
            ->assertJsonMissingPath('hijos.0.direccion');
    }

    public function test_detalle_del_hijo_incluye_horarios_de_cursado(): void
    {
        $this->getJson("/api/v1/hijos/{$this->sofia->id}")
            ->assertOk()
            ->assertJsonStructure(['ciclo' => ['id', 'anio'], 'alumno' => self::ALUMNO + ['horarios_cursado' => ['*' => self::HORARIO]]])
            ->assertJsonPath('alumno.legajo', 'A-0001')
            ->assertJsonCount(5, 'alumno.horarios_cursado')
            ->assertJsonPath('alumno.horarios_cursado.0', [
                'dia' => 1, 'dia_nombre' => 'Lunes', 'inicio' => '07:30', 'fin' => '13:00', 'descripcion' => 'Lunes 07:30 a 13:00',
            ])
            ->assertJsonMissingPath('alumno.dni');
    }

    public function test_alumno_inexistente_responde_404(): void
    {
        $this->getJson('/api/v1/hijos/9999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'No se encontró lo que buscabas.']);
    }

    public function test_catalogo_con_deportes_recorridos_y_tope(): void
    {
        $this->getJson('/api/v1/catalogo')
            ->assertOk()
            ->assertJsonStructure([
                'ciclo' => ['id', 'anio'],
                'max_deportes',
                'deportes' => ['*' => self::DEPORTE],
                'recorridos' => ['*' => self::RECORRIDO],
            ])
            ->assertJsonPath('max_deportes', 2)
            ->assertJsonCount(6, 'deportes')
            ->assertJsonPath('deportes.0.nombre', 'Atletismo') // ordenados por nombre
            ->assertJsonCount(4, 'recorridos')
            ->assertJsonPath('recorridos.0.nombre', 'Este');
    }

    public function test_inscribe_en_los_cuatro_servicios(): void
    {
        $this->inscribir('cursado')
            ->assertCreated()
            ->assertJsonStructure(['message', 'estado' => ['cursado' => ['id', 'curso' => self::CURSO], 'comedor', 'transporte', 'deportes']])
            ->assertJsonPath('message', 'Inscripción registrada.')
            ->assertJsonPath('estado.cursado.curso.nombre', '1° año');

        $this->inscribir('comedor')->assertCreated()->assertJsonStructure(['estado' => ['comedor' => ['id']]]);

        $norte = Recorrido::where('nombre', 'Norte')->first();
        $this->inscribir('transporte', ['recorrido_id' => $norte->id])
            ->assertCreated()
            ->assertJsonStructure(['estado' => ['transporte' => ['id', 'recorrido' => self::RECORRIDO]]])
            ->assertJsonPath('estado.transporte.recorrido.nombre', 'Norte');

        $this->inscribir('deporte', $this->deporte('Fútbol'))
            ->assertCreated()
            ->assertJsonStructure(['estado' => ['deportes' => ['*' => ['id', 'deporte' => self::DEPORTE]]]])
            ->assertJsonPath('estado.deportes.0.deporte.nombre', 'Fútbol')
            ->assertJsonPath('estado.cursado.curso.nombre', '1° año')
            ->assertJsonPath('estado.transporte.recorrido.nombre', 'Norte');

        $this->assertDatabaseHas('inscripciones_comedor', ['alumno_id' => $this->sofia->id]);
    }

    public function test_rn03_tope_de_dos_deportes(): void
    {
        $this->inscribir('deporte', $this->deporte('Fútbol'))->assertCreated();
        $this->inscribir('deporte', $this->deporte('Vóley'))->assertCreated();

        $this->inscribir('deporte', $this->deporte('Natación'))
            ->assertStatus(422)
            ->assertExactJson(['message' => 'Se permite un máximo de 2 deportes por alumno.', 'regla' => 'RN-03']);
    }

    public function test_rn04_deportes_superpuestos(): void
    {
        $this->inscribir('deporte', $this->deporte('Fútbol'))->assertCreated();

        $this->inscribir('deporte', $this->deporte('Básquet'))
            ->assertStatus(422)
            ->assertExactJson(['message' => 'Básquet se superpone con Fútbol (Lunes 15:00 a 16:30).', 'regla' => 'RN-04']);
    }

    public function test_rn05_deporte_superpuesto_con_el_cursado(): void
    {
        $ajedrez = Deporte::create(['nombre' => 'Ajedrez']);
        $ajedrez->horarios()->create(['dia' => 2, 'hora_inicio' => '10:00', 'hora_fin' => '11:00']);

        $this->inscribir('deporte', ['deporte_id' => $ajedrez->id])
            ->assertStatus(422)
            ->assertJsonPath('regla', 'RN-05')
            ->assertJsonPath('message', 'Ajedrez se superpone con el horario de cursado (Martes 10:00 a 11:00).');
    }

    public function test_rn06_un_unico_recorrido(): void
    {
        [$norte, $sur] = Recorrido::orderBy('id')->take(2)->get();
        $this->inscribir('transporte', ['recorrido_id' => $norte->id])->assertCreated();

        $this->inscribir('transporte', ['recorrido_id' => $sur->id])
            ->assertStatus(422)
            ->assertJsonPath('regla', 'RN-06')
            ->assertJsonPath('message', "El alumno ya tiene asignado el recorrido {$norte->nombre}. Solo se permite un recorrido: dalo de baja para elegir otro.");
    }

    public function test_comedor_duplicado_responde_422_sin_regla(): void
    {
        $this->inscribir('comedor')->assertCreated();

        $this->inscribir('comedor')
            ->assertStatus(422)
            ->assertExactJson(['message' => 'El alumno ya está inscripto al comedor.', 'regla' => null]);
    }

    public function test_valida_recorrido_y_deporte(): void
    {
        $this->inscribir('transporte')->assertStatus(422)->assertJsonValidationErrors('recorrido_id');
        $this->inscribir('deporte', ['deporte_id' => 9999])->assertStatus(422)->assertJsonValidationErrors('deporte_id');
    }

    public function test_sin_ciclo_activo(): void
    {
        CicloLectivo::query()->update(['activo' => false]);

        $this->inscribir('comedor')
            ->assertStatus(422)
            ->assertExactJson(['message' => 'No hay un ciclo lectivo abierto para inscripciones.', 'regla' => null]);

        $this->getJson('/api/v1/hijos')
            ->assertOk()
            ->assertJsonPath('ciclo', null)
            ->assertJsonPath('hijos.0.estado', null);

        $this->getJson('/api/v1/catalogo')->assertOk()->assertJsonPath('ciclo', null);
    }

    public function test_baja_de_una_inscripcion(): void
    {
        $id = $this->inscribir('comedor')->assertCreated()->json('estado.comedor.id');

        $this->deleteJson("/api/v1/hijos/{$this->sofia->id}/inscripciones/comedor/{$id}")
            ->assertOk()
            ->assertJsonPath('message', 'Se dio de baja la inscripción.')
            ->assertJsonPath('estado.comedor', null)
            ->assertJsonStructure(['message', 'estado' => self::ESTADO]);

        $this->assertDatabaseMissing('inscripciones_comedor', ['id' => $id]);
    }

    public function test_baja_de_inscripcion_ajena_responde_404(): void
    {
        $ajena = InscripcionComedor::create(['alumno_id' => $this->ajeno->id, 'ciclo_lectivo_id' => CicloLectivo::actual()->id]);

        // Su propio hijo en la URL, pero el id de la inscripción de otro alumno.
        $this->deleteJson("/api/v1/hijos/{$this->sofia->id}/inscripciones/comedor/{$ajena->id}")
            ->assertNotFound()
            ->assertExactJson(['message' => 'No se encontró lo que buscabas.']);

        $this->assertDatabaseHas('inscripciones_comedor', ['id' => $ajena->id]);
    }

    public function test_rn07_tutor_no_opera_sobre_alumno_ajeno_cambiando_el_id(): void
    {
        $rn07 = ['message' => 'Solo podés gestionar las inscripciones de los alumnos que tenés a cargo.'];
        $inscripcion = InscripcionComedor::create(['alumno_id' => $this->ajeno->id, 'ciclo_lectivo_id' => CicloLectivo::actual()->id]);

        $this->getJson("/api/v1/hijos/{$this->ajeno->id}")->assertForbidden()->assertExactJson($rn07);
        $this->inscribir('deporte', $this->deporte('Fútbol'), $this->ajeno)->assertForbidden()->assertExactJson($rn07);
        $this->deleteJson("/api/v1/hijos/{$this->ajeno->id}/inscripciones/comedor/{$inscripcion->id}")
            ->assertForbidden()
            ->assertExactJson($rn07);

        $this->assertDatabaseHas('inscripciones_comedor', ['id' => $inscripcion->id]);
        $this->assertDatabaseMissing('inscripciones_deporte', ['alumno_id' => $this->ajeno->id]);
    }

    public static function sinPermiso(): array
    {
        return [
            'docente' => ['docente@educontrol.test'],
            'administrativo' => ['administrativo@educontrol.test'],
            'administrador' => ['admin@educontrol.test'],
        ];
    }

    #[DataProvider('sinPermiso')]
    public function test_otros_perfiles_no_usan_la_autogestion(string $email): void
    {
        $mensaje = ['message' => 'Tu perfil no tiene acceso a esta sección.'];
        $this->como($email);

        $this->getJson('/api/v1/hijos')->assertForbidden()->assertExactJson($mensaje);
        $this->getJson('/api/v1/catalogo')->assertForbidden()->assertExactJson($mensaje);
        $this->inscribir('comedor')->assertForbidden()->assertExactJson($mensaje);

        $this->assertDatabaseMissing('inscripciones_comedor', ['alumno_id' => $this->sofia->id]);
    }
}
