# Integración y entrega continua (CI/CD)

El pipeline está en [`.github/workflows/ci.yml`](../.github/workflows/ci.yml) y corre en **GitHub Actions**. Los resultados se ven en la pestaña [Actions](https://github.com/DaniloAgustinBenitez/EduControl/actions) del repositorio.

**Estado:** la integración continua y el empaquetado están en funcionamiento. El **despliegue continuo todavía no**: la configuración para Render ya está en el repositorio (`Dockerfile`, `render.yaml`), pero falta crear las cuentas de Render y Aiven (issues #6 y #53). Lo que ya está listo para cuando se conecte se explica más abajo.

## Cuándo corre

| Evento | Qué se ejecuta |
|---|---|
| `push` a cualquier rama | Build, seguridad, lint y tests |
| Pull request hacia `main` | Lo mismo (el resultado aparece en el PR) |
| `push` a `main` (al mergear un PR) | Lo anterior **más** el paquete desplegable con su verificación |
| Manual (*Run workflow*) | Build, seguridad, lint y tests. Con la casilla `simular_fallo` fuerza un error para demostrar la notificación |

Si llega un push nuevo a la misma rama mientras corre el anterior, la corrida vieja se cancela.

## Etapas

```
Commit → Build → Seguridad → Lint → Test (+ E2E en paralelo) → Package → Verify → (Deploy) → Notify
```

| Etapa | Paso del workflow | Herramienta | Estado |
|---|---|---|---|
| **Build** | `composer validate` (que `composer.json` y `composer.lock` coincidan) y `composer install` con caché entre corridas | Composer 2, PHP 8.3 | Activa |
| **Seguridad** | `composer audit`: falla si alguna dependencia tiene una vulnerabilidad publicada | Composer | Activa |
| **Lint** | `vendor/bin/pint --test`: falla si el código no respeta el estilo de Laravel | Laravel Pint | Activa |
| **Test** | 56 tests (unitarios y de integración) sobre SQLite en memoria, con cobertura | PHPUnit 12 + PCOV | Activa |
| **E2E** | Levanta la app con los datos de prueba y corre las pruebas de navegador (caso CP-TR-01 y regresiones). En paralelo con el job de tests | Selenium WebDriver + Chrome | Activa |
| **Package** | Arma `educontrol-<commit>.zip` sin tests ni dependencias de desarrollo. Solo en `main` | `git archive`, Composer `--no-dev` | Activa |
| **Verify (paquete)** | Revisa el contenido del `.zip`: tiene `artisan`, `public/index.php` y `vendor/autoload.php`, y **no** tiene `.env`, `tests/` ni PHPUnit | Script del workflow | Activa |
| **Deploy** | Render publica `main` cuando el CI pasa (ver "Conectar el despliegue") | Render + `Dockerfile` | **Pendiente** |
| **Verify (producción)** | Consulta `/up` de la aplicación publicada hasta recibir 200 | `curl` | Lista; se activa al cargar `URL_PRODUCCION` |
| **Notify** | Si falla en `main`, abre un issue con la etiqueta `no-conformidad`. Además GitHub manda correo al autor y el PR muestra la cruz roja | `gh issue create` | Activa |

## Artefactos

Cada corrida guarda archivos descargables desde la página de la corrida, sección **Artifacts**:

| Artefacto | Contenido | Se conserva |
|---|---|---|
| `reportes-tests-<commit>` | `junit.xml` (resultado de cada test), `clover.xml` y `cobertura/` (reporte HTML navegable: abrir `index.html`) | 14 días |
| `educontrol-<commit>` | Paquete `.zip` listo para desplegar (solo en `main`) | 30 días |

Además, la página de cada corrida muestra un **resumen** con la cantidad de tests y el porcentaje de cobertura.

## Variables de entorno y permisos

| Variable | Dónde vive | Para qué |
|---|---|---|
| `PHP_VERSION`, `PHP_EXTENSIONS` | Bloque `env:` del workflow | Misma versión de PHP en todas las etapas |
| `.env` y `APP_KEY` | Se crean en cada corrida con `cp .env.example .env` y `php artisan key:generate` | El CI no usa credenciales reales |
| `DB_CONNECTION=sqlite`, `:memory:` | `phpunit.xml` | La base de los tests no toca MySQL |
| `URL_PRODUCCION` | Variable del repositorio (*Settings → Secrets and variables → Actions → Variables*) | Activa la verificación de producción. Sin ella, ese job se omite |
| `GITHUB_TOKEN` | Lo entrega GitHub en cada corrida | El workflow le deja solo `contents: read`. El job *Notify* suma `issues: write` |
| Variables de producción (`APP_KEY`, `DB_*`, `DB_SSL_CA_PEM`, etc.) | Render (ver [despliegue.md](despliegue.md)) | Todavía sin cargar |

`.env` está en `.gitignore`: ninguna credencial entra al repositorio.

## Notificación de fallos (Notify)

Si el CI falla en `main`, el job `notificar` abre un issue titulado `CI fallido en main (<commit>)` con la etiqueta `no-conformidad`, el link a la corrida y el autor. Eso avisa a todos los que siguen el repositorio y deja el fallo registrado en el tablero, como pide el material de calidad (las no conformidades se registran en el sistema de incidencias; ver [calidad.md](calidad.md)).

**Cómo demostrarlo:** *Actions → CI → Run workflow*, elegir la rama, tildar `simular_fallo` y ejecutar. El pipeline falla a propósito y aparece el issue. Después se cierra a mano.

## Dependencias automáticas

[`.github/dependabot.yml`](../.github/dependabot.yml) hace que Dependabot abra, una vez por semana, un PR con las actualizaciones menores de Composer agrupadas y otro para las acciones de GitHub. Cada uno pasa por el mismo CI: si queda en verde y el diff es razonable, se revisa y se mergea.

## Conectar el despliegue continuo (Render)

Para que Render publique **solo** las versiones que pasaron el CI:

1. Creá el servicio desde el Blueprint `render.yaml` (pasos en [despliegue.md](despliegue.md)). El archivo ya pide la rama `main` y `autoDeployTrigger: checksPass`: Render espera a que GitHub Actions termine en verde antes de desplegar, y si el CI falla no despliega.
2. Verificá en **Settings → Build & Deploy** que *Auto-Deploy* diga *After CI Checks Pass*.
3. Cargá en GitHub la variable `URL_PRODUCCION` con la URL pública (por ejemplo `https://educontrol.onrender.com`). Desde el próximo merge a `main`, el CI verifica que `/up` responda 200 y, si no responde, avisa por *Notify*. La verificación reintenta durante 5 minutos, así que alcanza aunque el plan gratis tarde un minuto en despertar.

Así queda el flujo completo: *merge del PR → CI en verde → Render despliega → el CI confirma que la nueva versión responde*.

`/up` es la ruta de salud que trae Laravel (`bootstrap/app.php`). La verificación confirma que la aplicación está viva, no que sea exactamente la versión recién publicada.

## Cómo reproducir el pipeline en tu máquina

```bash
composer validate        # composer.json y composer.lock coinciden
composer install
composer audit           # vulnerabilidades conocidas
vendor/bin/pint --test   # lint (sin --test, corrige el formato)
php artisan test         # tests
```

Para la cobertura local hace falta la extensión `pcov` o `xdebug`. En el CI ya está instalada.
