<?php

use App\Exceptions\ReglaNegocioException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Railway y Render terminan el HTTPS en su proxy (RNF-03).
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
        ]);

        $middleware->redirectUsersTo(fn () => route('inicio'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // API (RF-25): errores en JSON con el formato de docs/api.md. La web sigue igual.
        $esApi = fn (Request $request) => $request->is('api/*');

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($esApi) {
            if ($esApi($request)) {
                return response()->json(['message' => 'Tu sesión venció. Volvé a ingresar.'], 401);
            }
        });

        $exceptions->render(function (ReglaNegocioException $e, Request $request) use ($esApi) {
            if ($esApi($request)) {
                return response()->json(['message' => $e->getMessage(), 'regla' => $e->regla], 422);
            }
        });

        // Spatie avisa en inglés cuando falta un permiso; mostramos nuestro mensaje.
        $exceptions->render(function (UnauthorizedException $e, Request $request) use ($esApi) {
            if ($esApi($request)) {
                return response()->json(['message' => 'Tu perfil no tiene acceso a esta sección.'], 403);
            }

            abort(403, 'Tu perfil no tiene acceso a esta sección.');
        });

        // Incluye modelos inexistentes ({alumno}, {curso}) y rutas que no existen bajo /api.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($esApi) {
            if ($esApi($request)) {
                return response()->json(['message' => 'No se encontró lo que buscabas.'], 404);
            }
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) use ($esApi) {
            if ($esApi($request)) {
                return response()->json(
                    ['message' => 'Demasiados intentos. Esperá un minuto y volvé a intentar.'], 429, $e->getHeaders()
                );
            }
        });

        // El resto (403 de AlumnoPolicy o de un curso no asignado, etc.): solo el mensaje, sin traza.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($esApi) {
            if ($esApi($request)) {
                return response()->json(
                    ['message' => $e->getMessage() ?: 'No se pudo completar el pedido.'], $e->getStatusCode(), $e->getHeaders()
                );
            }
        });
    })->create();
