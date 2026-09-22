<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\CicloLectivo;
use App\Models\Curso;
use App\Models\Deporte;
use App\Models\InscripcionComedor;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Tareas 2.7 y 5.9: ningún perfil alcanza pantallas ajenas, ni siquiera escribiendo la URL.
 */
class AccesoPorRolTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected $seeder = DatabaseSeeder::class;

    private const PANTALLAS = [
        'configuracion' => ['/niveles', '/cursos', '/materias', '/deportes', '/recorridos', '/ciclos', '/cursos/create', '/horarios/deporte/1'],
        'usuarios' => ['/alumnos', '/profesores', '/tutores', '/alumnos/create', '/profesores/create', '/tutores/create', '/profesores/1/asignaciones'],
        'reportes' => ['/reportes', '/reportes/alumno', '/reportes/docente', '/reportes/curso', '/reportes/materia', '/reportes/deporte', '/reportes/recorrido'],
        'docente' => ['/docente'],
        'autogestion' => ['/autogestion'],
    ];

    /** Qué grupos de pantallas puede ver cada rol. */
    public static function roles(): array
    {
        return [
            'administrador' => ['admin@educacion.test', ['configuracion', 'usuarios', 'reportes']],
            'administrativo' => ['administrativo@educacion.test', ['usuarios', 'reportes']],
            'docente' => ['docente@educacion.test', ['docente']],
            'tutor' => ['tutor@educacion.test', ['autogestion']],
        ];
    }

    #[DataProvider('roles')]
    public function test_cada_rol_ve_solo_lo_suyo(string $email, array $permitidos): void
    {
        $this->actingAs(User::where('email', $email)->first());

        foreach (self::PANTALLAS as $grupo => $urls) {
            foreach ($urls as $url) {
                $respuesta = $this->get($url);
                in_array($grupo, $permitidos)
                    ? $respuesta->assertOk()
                    : $respuesta->assertForbidden();
            }
        }
    }

    #[DataProvider('roles')]
    public function test_inicio_redirige_o_muestra_el_panel(string $email): void
    {
        $this->actingAs(User::where('email', $email)->first())
            ->followingRedirects()
            ->get('/')
            ->assertOk();
    }

    public function test_invitado_es_enviado_al_login(): void
    {
        $this->get('/alumnos')->assertRedirect('/login');
        $this->get('/autogestion')->assertRedirect('/login');
    }

    public function test_login_con_credenciales_correctas_e_incorrectas(): void
    {
        $this->post('/login', ['email' => 'tutor@educacion.test', 'password' => 'mala'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => 'tutor@educacion.test', 'password' => 'password'])->assertRedirect('/');
        $this->assertAuthenticated();
    }

    public function test_las_contrasenas_se_guardan_hasheadas(): void
    {
        $this->assertNotSame('password', User::first()->getRawOriginal('password'));
        $this->assertStringStartsWith('$2y$', User::first()->getRawOriginal('password'));
    }

    public function test_rn07_tutor_no_opera_sobre_alumno_ajeno_por_url(): void
    {
        $tutor = User::where('email', 'tutor@educacion.test')->first();
        $ajeno = Alumno::where('legajo', 'A-0003')->first();
        $propio = Alumno::where('legajo', 'A-0001')->first();

        $this->actingAs($tutor);
        $this->get("/autogestion/alumnos/{$propio->id}")->assertOk();
        $this->get("/autogestion/alumnos/{$ajeno->id}")->assertForbidden();
        $this->post("/autogestion/alumnos/{$ajeno->id}/comedor")->assertForbidden();
        $this->post("/autogestion/alumnos/{$ajeno->id}/deporte", ['deporte_id' => Deporte::first()->id])->assertForbidden();

        $this->assertDatabaseMissing('inscripciones_comedor', ['alumno_id' => $ajeno->id]);
    }

    public function test_rn07_tutor_no_da_de_baja_inscripcion_de_otro_alumno(): void
    {
        $tutor = User::where('email', 'tutor@educacion.test')->first();
        $propio = Alumno::where('legajo', 'A-0001')->first();
        $ajeno = Alumno::where('legajo', 'A-0003')->first();

        $this->actingAs(User::where('email', 'admin@educacion.test')->first());
        $this->post("/autogestion/alumnos/{$ajeno->id}/comedor")->assertForbidden(); // el admin no usa la autogestión

        $inscripcion = InscripcionComedor::create(['alumno_id' => $ajeno->id, 'ciclo_lectivo_id' => CicloLectivo::actual()->id]);

        // Usa su propio alumno en la URL pero el id de la inscripción ajena.
        $this->actingAs($tutor)->delete("/autogestion/alumnos/{$propio->id}/comedor/{$inscripcion->id}")->assertNotFound();
        $this->assertDatabaseHas('inscripciones_comedor', ['id' => $inscripcion->id]);
    }

    public function test_docente_no_ve_cursos_que_no_tiene_asignados(): void
    {
        $this->actingAs(User::where('email', 'docente@educacion.test')->first());

        $this->get('/docente/cursos/'.Curso::where('nombre', '1° año')->value('id'))->assertOk()->assertSee('Fernández');
        $this->get('/docente/cursos/'.Curso::where('nombre', '2° año')->value('id'))->assertForbidden();
    }
}
