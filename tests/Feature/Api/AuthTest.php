<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Ingreso por token de la app mobile (RF-25, RF-26, RNF-11) y riesgo R1 del plan mobile:
 * los permisos de Spatie (guard web) tienen que funcionar autenticando con Sanctum.
 */
class AuthTest extends TestCase
{
    use ConToken, RefreshDatabase;

    protected $seed = true;

    protected $seeder = DatabaseSeeder::class;

    private const USUARIO = ['id', 'nombre', 'email', 'rol', 'rol_etiqueta', 'permisos', 'usa_app'];

    public function test_login_correcto_devuelve_token_y_usuario(): void
    {
        $respuesta = $this->postJson('/api/v1/login', [
            'email' => 'tutor@educontrol.test', 'password' => 'password', 'dispositivo' => 'Pixel 7',
        ]);

        $respuesta->assertOk()
            ->assertJsonStructure(['token', 'usuario' => self::USUARIO])
            ->assertJsonPath('usuario.email', 'tutor@educontrol.test')
            ->assertJsonPath('usuario.nombre', 'Carlos Pérez')
            ->assertJsonPath('usuario.rol', 'tutor')
            ->assertJsonPath('usuario.rol_etiqueta', 'Tutor / Padre')
            ->assertJsonPath('usuario.permisos', ['inscripciones.autogestion'])
            ->assertJsonPath('usuario.usa_app', true);

        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'Pixel 7']);

        // El token sirve para los pedidos siguientes.
        $this->conToken($respuesta->json('token'))->getJson('/api/v1/perfil')
            ->assertOk()
            ->assertJsonPath('usuario.email', 'tutor@educontrol.test');
    }

    public function test_login_sin_dispositivo_nombra_el_token_app(): void
    {
        $this->postJson('/api/v1/login', ['email' => 'docente@educontrol.test', 'password' => 'password'])->assertOk();

        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'app']);
    }

    public function test_login_incorrecto_responde_422(): void
    {
        $this->postJson('/api/v1/login', ['email' => 'tutor@educontrol.test', 'password' => 'otra'])
            ->assertStatus(422)
            ->assertExactJson([
                'message' => 'El correo o la contraseña no son correctos.',
                'errors' => ['email' => ['El correo o la contraseña no son correctos.']],
            ]);

        $this->postJson('/api/v1/login', ['email' => 'nadie@educontrol.test', 'password' => 'password'])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'El correo o la contraseña no son correctos.');

        $this->postJson('/api/v1/login', ['email' => 'tutor@educontrol.test', 'password' => 'password', 'dispositivo' => str_repeat('x', 101)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('dispositivo');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_septimo_intento_en_un_minuto_responde_429(): void
    {
        foreach (range(1, 6) as $intento) {
            $this->postJson('/api/v1/login', ['email' => 'tutor@educontrol.test', 'password' => 'otra'])->assertStatus(422);
        }

        $this->postJson('/api/v1/login', ['email' => 'tutor@educontrol.test', 'password' => 'password'])
            ->assertStatus(429)
            ->assertExactJson(['message' => 'Demasiados intentos. Esperá un minuto y volvé a intentar.'])
            ->assertHeader('Retry-After');
    }

    public function test_logout_revoca_solo_el_token_usado(): void
    {
        $token = $this->tokenDe('tutor@educontrol.test');
        $otro = $this->tokenDe('tutor@educontrol.test'); // la misma cuenta en otro celular

        $this->conToken($token)->postJson('/api/v1/logout')->assertNoContent();
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->conToken($token)->getJson('/api/v1/perfil')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Tu sesión venció. Volvé a ingresar.']);

        $this->conToken($otro)->getJson('/api/v1/perfil')->assertOk();
    }

    public function test_sin_token_o_con_token_invalido_responde_401(): void
    {
        $this->getJson('/api/v1/perfil')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Tu sesión venció. Volvé a ingresar.']);

        $this->conToken('1|token-inventado')->getJson('/api/v1/hijos')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Tu sesión venció. Volvé a ingresar.']);
    }

    public function test_el_401_no_redirige_al_login_aunque_el_pedido_no_pida_json(): void
    {
        // Sin "Accept: application/json": la web redirigiría a /login, la API no.
        $this->get('/api/v1/perfil')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Tu sesión venció. Volvé a ingresar.']);
    }

    public function test_el_token_vence_a_los_30_dias(): void
    {
        $token = $this->tokenDe('tutor@educontrol.test');

        $this->travel(29)->days();
        $this->conToken($token)->getJson('/api/v1/perfil')->assertOk();

        $this->travel(2)->days();
        $this->conToken($token)->getJson('/api/v1/perfil')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Tu sesión venció. Volvé a ingresar.']);
    }

    public static function roles(): array
    {
        return [
            'administrador' => ['admin@educontrol.test', 'administrador', 'Administrador', false],
            'administrativo' => ['administrativo@educontrol.test', 'administrativo', 'Administrativo', false],
            'docente' => ['docente@educontrol.test', 'docente', 'Docente', true],
            'tutor' => ['tutor@educontrol.test', 'tutor', 'Tutor / Padre', true],
        ];
    }

    #[DataProvider('roles')]
    public function test_perfil_indica_si_el_rol_usa_la_app(string $email, string $rol, string $etiqueta, bool $usaApp): void
    {
        $this->como($email)->getJson('/api/v1/perfil')
            ->assertOk()
            ->assertJsonStructure(['usuario' => self::USUARIO])
            ->assertJsonPath('usuario.rol', $rol)
            ->assertJsonPath('usuario.rol_etiqueta', $etiqueta)
            ->assertJsonPath('usuario.usa_app', $usaApp)
            ->assertJsonPath('usuario.permisos', User::where('email', $email)->first()->getAllPermissions()->pluck('name')->all());
    }

    public function test_r1_los_permisos_de_spatie_funcionan_con_el_token_de_sanctum(): void
    {
        $sinPermiso = ['message' => 'Tu perfil no tiene acceso a esta sección.'];

        $this->como('tutor@educontrol.test')->getJson('/api/v1/hijos')->assertOk();
        $this->como('docente@educontrol.test')->getJson('/api/v1/hijos')->assertForbidden()->assertExactJson($sinPermiso);

        $this->como('docente@educontrol.test')->getJson('/api/v1/docente/cursos')->assertOk();
        $this->como('tutor@educontrol.test')->getJson('/api/v1/docente/cursos')->assertForbidden()->assertExactJson($sinPermiso);
    }

    public function test_ruta_inexistente_responde_404_en_json(): void
    {
        $this->getJson('/api/v1/no-existe')
            ->assertNotFound()
            ->assertExactJson(['message' => 'No se encontró lo que buscabas.']);

        $this->como('tutor@educontrol.test')->getJson('/api/v1/hijos/1/inscripciones/natacion')
            ->assertNotFound()
            ->assertExactJson(['message' => 'No se encontró lo que buscabas.']);
    }

    public function test_la_sesion_web_no_habilita_la_api(): void
    {
        $this->actingAs(User::where('email', 'tutor@educontrol.test')->first())
            ->getJson('/api/v1/perfil')
            ->assertUnauthorized();
    }
}
