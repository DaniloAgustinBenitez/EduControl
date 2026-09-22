<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\CicloLectivo;
use App\Models\Curso;
use App\Models\Deporte;
use App\Models\Nivel;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Flujos de las etapas 3 a 6 recorridos por HTTP, como lo haría un usuario. */
class GestionTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected $seeder = DatabaseSeeder::class;

    private function como(string $email): static
    {
        return $this->actingAs(User::where('email', $email)->first());
    }

    public function test_rn02_curso_sin_nivel_es_rechazado(): void
    {
        $this->como('admin@educacion.test')
            ->post('/cursos', ['nombre' => 'Sin nivel'])
            ->assertSessionHasErrors('nivel_id');
    }

    public function test_alta_de_curso_y_su_horario_de_cursado(): void
    {
        $this->como('admin@educacion.test')
            ->post('/cursos', ['nivel_id' => Nivel::first()->id, 'nombre' => 'Sala de 3'])
            ->assertSessionHasNoErrors();

        $curso = Curso::where('nombre', 'Sala de 3')->first();
        $this->post("/horarios/curso/{$curso->id}", ['dia' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '12:00'])->assertSessionHasNoErrors();
        $this->post("/horarios/curso/{$curso->id}", ['dia' => 1, 'hora_inicio' => '11:00', 'hora_fin' => '13:00'])->assertSessionHas('error');
        $this->post("/horarios/curso/{$curso->id}", ['dia' => 2, 'hora_inicio' => '12:00', 'hora_fin' => '08:00'])->assertSessionHasErrors('hora_fin');

        $this->assertSame(1, $curso->horarios()->count());
    }

    public function test_no_se_elimina_un_nivel_con_cursos(): void
    {
        $nivel = Nivel::first();
        $this->como('admin@educacion.test')->delete("/niveles/{$nivel->id}")->assertSessionHas('error');
        $this->assertModelExists($nivel);
    }

    public function test_alumno_con_legajo_o_dni_repetido_es_rechazado(): void
    {
        $existente = Alumno::first();

        $this->como('administrativo@educacion.test')
            ->post('/alumnos', ['legajo' => $existente->legajo, 'dni' => $existente->dni, 'nombre' => 'X', 'apellido' => 'Y'])
            ->assertSessionHasErrors(['legajo', 'dni']);
    }

    public function test_rn01_no_asigna_un_segundo_curso(): void
    {
        $alumno = Alumno::where('legajo', 'A-0001')->first();
        $otro = Curso::where('id', '!=', $alumno->curso_id)->first();

        $this->como('administrativo@educacion.test')
            ->put("/alumnos/{$alumno->id}/curso", ['curso_id' => $otro->id])
            ->assertSessionHas('error');
        $this->assertNotSame($otro->id, $alumno->fresh()->curso_id);

        // Quitando primero el curso actual, sí se puede.
        $this->put("/alumnos/{$alumno->id}/curso", ['curso_id' => ''])->assertSessionHas('ok');
        $this->put("/alumnos/{$alumno->id}/curso", ['curso_id' => $otro->id])->assertSessionHas('ok');
        $this->assertSame($otro->id, $alumno->fresh()->curso_id);
    }

    public function test_editar_alumno_no_cambia_su_curso(): void
    {
        $alumno = Alumno::where('legajo', 'A-0001')->first();
        $otro = Curso::where('id', '!=', $alumno->curso_id)->first();

        $this->como('administrativo@educacion.test')
            ->put("/alumnos/{$alumno->id}", $alumno->only('legajo', 'dni', 'nombre', 'apellido') + ['curso_id' => $otro->id]);

        $this->assertNotSame($otro->id, $alumno->fresh()->curso_id);
    }

    public function test_alta_de_tutor_crea_su_cuenta_y_lo_vincula_con_sus_hijos(): void
    {
        $hijo = Alumno::where('legajo', 'A-0003')->first();

        $this->como('administrativo@educacion.test')->post('/tutores', [
            'nombre' => 'Marta', 'apellido' => 'Fernández', 'dni' => '30999888',
            'email' => 'marta@example.com', 'password' => 'secreta123',
            'alumnos' => [$hijo->id], 'parentesco' => 'Madre',
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'marta@example.com')->first();
        $this->assertTrue($user->hasRole('tutor'));
        $this->assertTrue($user->tutor->alumnos->contains($hijo));

        // Y con esa cuenta ya puede operar sobre su hija.
        $this->actingAs($user)->post("/autogestion/alumnos/{$hijo->id}/comedor")->assertSessionHas('ok');
    }

    public function test_alta_de_profesor_y_asignaciones(): void
    {
        $this->como('administrativo@educacion.test')->post('/profesores', [
            'nombre' => 'Juan', 'apellido' => 'Suárez', 'dni' => '27000111',
            'email' => 'juan@example.com', 'password' => 'secreta123',
        ])->assertSessionHasNoErrors();

        $profesor = User::where('email', 'juan@example.com')->first()->profesor;
        $materia = Curso::where('nombre', '3° año')->first()->materias()->first();

        $this->put("/profesores/{$profesor->id}/asignaciones", ['materias' => [$materia->id]])->assertSessionHas('ok');

        // La materia arrastra su curso y su nivel.
        $this->assertTrue($profesor->cursos->contains($materia->curso_id));
        $this->assertTrue($profesor->niveles->contains($materia->curso->nivel_id));
    }

    public function test_autogestion_completa_del_tutor(): void
    {
        $sofia = Alumno::where('legajo', 'A-0001')->first();
        $url = "/autogestion/alumnos/{$sofia->id}";
        $this->como('tutor@educacion.test');

        $this->post("$url/cursado")->assertSessionHas('ok');
        $this->post("$url/comedor")->assertSessionHas('ok');
        $this->post("$url/transporte", ['recorrido_id' => 1])->assertSessionHas('ok');
        $this->post("$url/transporte", ['recorrido_id' => 2])->assertSessionHas('error');
        $this->post("$url/deporte", ['deporte_id' => Deporte::where('nombre', 'Fútbol')->value('id')])->assertSessionHas('ok');
        $this->post("$url/deporte", ['deporte_id' => Deporte::where('nombre', 'Básquet')->value('id')])->assertSessionHas('error');

        $this->get($url)->assertOk()->assertSee('Fútbol')->assertSee('Recorrido Norte');

        $comedor = $sofia->inscripcionesComedor()->first();
        $this->delete("$url/comedor/{$comedor->id}")->assertSessionHas('ok');
        $this->assertModelMissing($comedor);
    }

    public function test_reportes_se_exportan_a_pdf_y_planilla(): void
    {
        $this->como('administrativo@educacion.test');

        foreach (['alumno', 'docente', 'curso', 'materia', 'deporte', 'recorrido'] as $tipo) {
            $this->get("/reportes/{$tipo}?formato=pdf")->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->get("/reportes/{$tipo}?formato=xlsx")->assertOk()->assertDownload();
        }
    }

    public function test_solo_un_ciclo_lectivo_activo(): void
    {
        $this->como('admin@educacion.test')->post('/ciclos', ['anio' => 2027, 'activo' => '1'])->assertSessionHasNoErrors();

        $this->assertSame(1, CicloLectivo::where('activo', true)->count());
        $this->assertSame(2027, CicloLectivo::actual()->anio);
    }
}
