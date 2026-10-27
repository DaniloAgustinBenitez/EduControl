# Despliegue

Tareas 0.6 y 7.1. La opción gratis y sin vencimiento es **Render** para la aplicación y **Aiven** para la base MySQL. Railway queda como alternativa al final.

| | Render + Aiven | Railway |
|---|---|---|
| Costo | Gratis, sin vencimiento | Prueba de US$ 5 por 30 días; después US$ 1 de crédito por mes, que no alcanza para la app y la base |
| Contra | La app se apaga a los 15 minutos sin visitas y tarda hasta un minuto en volver | Hay que pagar el plan Hobby (US$ 5 por mes) después de la prueba |

## Opción recomendada: Render + Aiven

La configuración ya está en el repositorio:

| Archivo | Qué hace |
|---|---|
| `Dockerfile` | Imagen con PHP 8.3 y Apache, las extensiones de la app y las dependencias sin las de desarrollo |
| `docker/inicio.sh` | En cada arranque: guarda el certificado SSL de la base, optimiza Laravel, corre las migraciones y, si `SEMBRAR_DATOS=true`, carga los datos de prueba (los seeders no duplican) |
| `render.yaml` | El servicio en el plan gratis, desde `main`, con la ruta de salud `/up` y despliegue solo cuando el CI pasa |

### 1. Base de datos en Aiven

1. Entrá a [aiven.io](https://aiven.io) y creá una cuenta (sirve la de GitHub o Google).
2. **Create service → MySQL → plan Free**. Anotá la región: la de Render tiene que ser la más cercana (la nuestra quedó en California, así que en Render va Oregon).
3. Esperá a que el servicio quede en **Running** y, en **Overview → Connection information**, copiá *Host*, *Port*, *User*, *Password* y *Database name* (`defaultdb`).
4. En la misma pantalla, en **CA certificate**, tocá **Show** y copiá todo el texto, desde `-----BEGIN CERTIFICATE-----` hasta `-----END CERTIFICATE-----`.

### 2. Clave de la aplicación

En tu máquina, dentro del proyecto:

```bash
php artisan key:generate --show
```

Copiá el resultado completo (empieza con `base64:`). No lo subas al repositorio.

### 3. Aplicación en Render

1. Entrá a [render.com](https://render.com) con tu cuenta de GitHub y dale acceso al repositorio `EduControl` (es privado).
2. **New → Blueprint**, elegí el repositorio y la rama `main`. Render lee `render.yaml` y te pide los valores que faltan:

| Variable | Valor |
|---|---|
| `APP_KEY` | La clave del paso 2 |
| `APP_URL` | `https://educontrol.onrender.com` (si Render te da otro nombre, corregilo después) |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Los datos de Aiven del paso 1 |
| `DB_SSL_CA_PEM` | El texto completo del certificado de Aiven |

3. **Apply**. El primer despliegue tarda unos minutos: en **Logs** se ve la construcción de la imagen, las migraciones y el arranque de Apache.

**Sin Blueprint (New → Web Service):** se llega a lo mismo completando el formulario a mano. *Language*: **Docker** (no Node: Render se confunde por el `package.json`); *Branch*: `main`; *Region*: la más cercana a Aiven; *Instance Type*: **Free**; *Root Directory*, *Dockerfile Path* y *Docker Command*: por defecto. En **Environment Variables**, **Add from .env** acepta pegar todas las variables juntas; `DB_SSL_CA_PEM` conviene cargarla aparte con **Add Environment Variable**, porque ocupa varias líneas. En **Advanced**: *Health Check Path* `/up` y *Auto-Deploy* **After CI Checks Pass**.
4. Abrí la URL del servicio y entrá con `admin@educontrol.test` / `password`.

### 4. Después del primer despliegue

- Si la URL final no es la que pusiste en `APP_URL`, corregila en **Environment** (Render vuelve a desplegar solo).
- Cargá la URL en GitHub como variable `URL_PRODUCCION` (*Settings → Secrets and variables → Actions → Variables*). Desde ahí, el job *Verify - la aplicación en producción responde* del CI comprueba `/up` después de cada merge a `main`.
- Cada merge a `main` con el CI en verde se publica solo (`autoDeployTrigger: checksPass`).

### Probar la imagen en local

```bash
docker network create educontrol-prueba
docker run -d --name educontrol-mysql --network educontrol-prueba -e MYSQL_ROOT_PASSWORD=prueba -e MYSQL_DATABASE=educacion mysql:8.4
docker build -t educontrol .
docker run --rm -p 8080:8080 --network educontrol-prueba -e APP_KEY=<clave> -e APP_ENV=local -e DB_HOST=educontrol-mysql -e DB_USERNAME=root -e DB_PASSWORD=prueba -e SEMBRAR_DATOS=true educontrol
```

Con `APP_ENV=local` se puede entrar por `http://localhost:8080`. En producción la app exige `https`.

## Seguridad (tarea 7.2)

- **HTTPS:** en producción la app fuerza `https` (`AppServiceProvider`) y confía en el proxy del servicio, que es el que termina el TLS (`trustProxies` en `bootstrap/app.php`). Verificalo entrando por `http://`: tiene que redirigir o cargar como `https://`.
- **Base de datos:** la conexión con Aiven va cifrada y se verifica con su certificado (`DB_SSL_CA_PEM`).
- **Credenciales:** no hay credenciales en el repo. `.env` está en `.gitignore` y en `.dockerignore`, y en el servicio todo va como variable. Para comprobarlo:
  ```bash
  git ls-files | grep -E "\.env$"          # no debe mostrar nada
  git log -p | grep -iE "DB_PASSWORD=.+"   # no debe mostrar contraseñas reales
  ```
- **Datos de prueba:** con `SEMBRAR_DATOS=true` existen los usuarios de prueba con la clave `password`. Antes de usar el sistema con datos reales, cambiá esas contraseñas o borrá los usuarios, y poné `SEMBRAR_DATOS=false`.

## Alternativa: Railway

Railway detecta Laravel solo y tiene MySQL propio, pero solo es gratis durante la prueba de 30 días.

1. Entrá a [railway.com](https://railway.com) con tu cuenta de GitHub.
2. **New Project → Deploy from GitHub repo**, elegí `DaniloAgustinBenitez/EduControl`, rama `main`.
3. En el mismo proyecto: **New → Database → MySQL**.
4. En el servicio de la app, pestaña **Variables**:

| Variable | Valor |
|---|---|
| `APP_NAME` | `EduControl` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | La salida de `php artisan key:generate --show` |
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
| `SEMBRAR_DATOS` | `true` |

5. Railway usa el `Dockerfile` del repositorio, así que las migraciones y los datos de prueba se cargan al arrancar.
6. En **Settings → Networking → Generate Domain**, copiá la URL en `APP_URL` y en el [README](../README.md).
