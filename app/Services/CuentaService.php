<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Da de alta o actualiza la persona (profesor, tutor, administrativo) junto con su cuenta
 * de usuario y su rol, para que cada cuenta quede vinculada con su entidad real (tarea 2.3).
 */
class CuentaService
{
    /**
     * @param  bool  $conLegajo  RF-02: los profesores llevan legajo, igual que los alumnos.
     */
    public static function reglas(?Model $persona, string $tabla, bool $conLegajo = false): array
    {
        return array_filter([
            'legajo' => $conLegajo ? ['required', 'string', 'max:20', Rule::unique($tabla)->ignore($persona)] : null,
            'dni' => ['required', 'string', 'max:15', Rule::unique($tabla)->ignore($persona)],
            'nombre' => ['required', 'string', 'max:60'],
            'apellido' => ['required', 'string', 'max:60'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($persona?->user_id)],
            'password' => [$persona?->exists ? 'nullable' : 'required', 'string', 'min:8'],
        ]);
    }

    public function guardar(Model $persona, array $datos, string $rol): Model
    {
        return DB::transaction(function () use ($persona, $datos, $rol) {
            $user = $persona->user ?? new User;
            $user->name = $datos['nombre'].' '.$datos['apellido'];
            $user->email = $datos['email'];
            if (! empty($datos['password'])) {
                $user->password = $datos['password']; // el cast "hashed" la guarda con bcrypt (RNF-02)
            }
            $user->save();
            $user->syncRoles([$rol]);

            $persona->fill(collect($datos)->only(['legajo', 'dni', 'nombre', 'apellido', 'telefono'])->all());
            $persona->user()->associate($user);
            $persona->save();

            return $persona;
        });
    }

    public function eliminar(Model $persona): void
    {
        DB::transaction(function () use ($persona) {
            $user = $persona->user;
            $persona->delete();
            $user?->delete();
        });
    }
}
