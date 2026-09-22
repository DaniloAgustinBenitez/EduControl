# Despliegue en Railway

Tareas 0.6 y 7.1. Railway detecta Laravel automáticamente, así que no hace falta un Dockerfile.

## 1. Crear el proyecto

1. Entrá a [railway.com](https://railway.com) con tu cuenta de GitHub.
2. **New Project → Deploy from GitHub repo** y elegí `DaniloAgustinBenitez/EducacionDos`, rama `main`. Como el repo es privado, Railway te va a pedir permiso para leerlo.
3. En el mismo proyecto: **New → Database → MySQL**.

## 2. Variables de entorno

En el servicio de la app, pestaña **Variables**:

| Variable | Valor |
|---|---|
| `APP_NAME` | `EducacionDos` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | La salida de `php artisan key:generate --show` (generala en tu máquina) |
| `APP_URL` | La URL pública que te da Railway, con `https://` |
| `APP_LOCALE` | `es` |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | `${{MySQL.MYSQLHOST}}` |
| `DB_PORT` | `${{MySQL.MYSQLPORT}}` |
| `DB_DATABASE` | `${{MySQL.MYSQLDATABASE}}` |
| `DB_USERNAME` | `${{MySQL.MYSQLUSER}}` |
| `DB_PASSWORD` | `${{MySQL.MYSQLPASSWORD}}` |
| `SESSION_SECURE_COOKIE` | `true` |
| `LOG_CHANNEL` | `stderr` |

Las variables con `${{MySQL...}}` toman los datos de la base que creaste en el paso 1, sin copiarlos a mano.

## 3. Migraciones

En **Settings → Deploy → Pre-deploy command**:

```
php artisan migrate --force
```

La **primera vez**, para tener los usuarios de prueba, usá en cambio:

```
php artisan migrate --force && php artisan db:seed --force
```

Después del primer despliegue, volvé al comando sin el seed.

## 4. Dominio

En **Settings → Networking → Generate Domain**, copiá la URL en `APP_URL` y en el [README](../README.md).

## Seguridad (tarea 7.2)

- **HTTPS:** en producción la app fuerza `https` (`AppServiceProvider`) y confía en el proxy de Railway, que es el que termina el TLS (`trustProxies` en `bootstrap/app.php`). Verificalo entrando por `http://`: tiene que redirigir o cargar como `https://`.
- **Credenciales:** no hay credenciales en el repo. `.env` está en `.gitignore`, y en Railway todo va como variable. Para comprobarlo:
  ```bash
  git ls-files | grep -E "\.env$"          # no debe mostrar nada
  git log -p | grep -iE "DB_PASSWORD=.+"   # no debe mostrar contraseñas reales
  ```
- **Datos de prueba:** cambiá las contraseñas de los usuarios de prueba en producción, o borralos antes de usar el sistema con datos reales.

## Alternativa: Render

Render necesita un Dockerfile para PHP y no ofrece MySQL gestionado. Si se elige Render, hay que usar una base MySQL externa (por ejemplo Aiven, que tiene plan gratuito) y agregar un Dockerfile.
