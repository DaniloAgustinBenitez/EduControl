<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UsuarioResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Ingreso a la app mobile con token de Sanctum (RF-25, RF-26).
 * Las credenciales son las mismas de la web; no se abre sesión.
 */
class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'dispositivo' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::where('email', $datos['email'])->first();

        if (! $user || ! Hash::check($datos['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'El correo o la contraseña no son correctos.']);
        }

        // El nombre del token identifica el celular; vence según sanctum.expiration (RNF-11).
        $token = $user->createToken($datos['dispositivo'] ?? 'app')->plainTextToken;

        return response()->json(['token' => $token, 'usuario' => new UsuarioResource($user)]);
    }

    /** Revoca solo el token usado en el pedido: las otras sesiones de la app siguen activas. */
    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    public function perfil(Request $request): JsonResponse
    {
        return response()->json(['usuario' => new UsuarioResource($request->user())]);
    }
}
