<?php

namespace Tests\Feature\Api;

use App\Models\User;

/**
 * Autentica los pedidos de la API con un token real de Sanctum, como lo hace la app.
 */
trait ConToken
{
    protected function tokenDe(string $email): string
    {
        return User::where('email', $email)->firstOrFail()->createToken('test')->plainTextToken;
    }

    protected function conToken(string $token): static
    {
        // Dentro de un mismo test la app es la misma: se olvida el usuario que resolvió
        // el guard en el pedido anterior para que cada pedido se autentique de cero.
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }

    protected function como(string $email): static
    {
        return $this->conToken($this->tokenDe($email));
    }
}
