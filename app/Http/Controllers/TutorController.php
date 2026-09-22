<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Tutor;
use App\Services\CuentaService;
use App\Support\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** RF-03: ABM de tutores y su vínculo con uno o más alumnos a cargo. */
class TutorController extends Controller
{
    public function __construct(private CuentaService $cuentas) {}

    public function index()
    {
        return view('tutores.index', ['tutores' => Tutor::with(['user', 'alumnos'])->orderBy('apellido')->get()]);
    }

    public function create()
    {
        return view('tutores.form', ['tutor' => new Tutor, 'alumnos' => $this->alumnos()]);
    }

    public function store(Request $request)
    {
        $this->guardar($request, new Tutor);

        return redirect()->route('tutores.index')->with('ok', 'Tutor creado.');
    }

    public function edit(Tutor $tutor)
    {
        return view('tutores.form', ['tutor' => $tutor->load('alumnos'), 'alumnos' => $this->alumnos()]);
    }

    public function update(Request $request, Tutor $tutor)
    {
        $this->guardar($request, $tutor);

        return redirect()->route('tutores.index')->with('ok', 'Tutor actualizado.');
    }

    public function destroy(Tutor $tutor)
    {
        $this->cuentas->eliminar($tutor);

        return redirect()->route('tutores.index')->with('ok', 'Tutor eliminado.');
    }

    private function guardar(Request $request, Tutor $tutor): void
    {
        $datos = $request->validate(CuentaService::reglas($tutor->exists ? $tutor : null, 'tutores') + [
            'alumnos' => ['array'],
            'alumnos.*' => ['exists:alumnos,id'],
            'parentesco' => ['nullable', 'string', 'max:30'],
        ]);

        DB::transaction(function () use ($tutor, $datos) {
            $this->cuentas->guardar($tutor, $datos, Rol::TUTOR);
            $tutor->alumnos()->sync(
                collect($datos['alumnos'] ?? [])->mapWithKeys(fn ($id) => [$id => ['parentesco' => $datos['parentesco'] ?? null]])
            );
        });
    }

    private function alumnos()
    {
        return Alumno::with('curso')->orderBy('apellido')->orderBy('nombre')->get();
    }
}
