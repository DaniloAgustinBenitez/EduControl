# Integración y entrega continua (CI/CD)

El pipeline está en [`.github/workflows/ci.yml`](../.github/workflows/ci.yml) y corre en **GitHub Actions**. Los resultados se ven en la pestaña [Actions](https://github.com/DaniloAgustinBenitez/EduControl/actions) del repositorio.

## Cuándo corre

| Evento | Qué se ejecuta |
|---|---|
| `push` a cualquier rama | Build, lint y tests |
| Pull request hacia `main` | Build, lint y tests (el resultado aparece en el PR) |
| `push` a `main` (al mergear un PR) | Lo anterior **más** el paquete desplegable |
| Manual (*Run workflow*) | Build, lint y tests |

Si llega un push nuevo a la misma rama mientras corre el anterior, la corrida vieja se cancela.

## Etapas

```
Commit → Build → Lint → Test → Package → (Deploy en Railway) → Notify
```

| Etapa | Paso del workflow | Herramienta |
|---|---|---|
| **Build** | `composer install` (con caché de Composer entre corridas) | Composer 2, PHP 8.3 |
| **Lint** | `vendor/bin/pint --test`: falla si el código no respeta el estilo de Laravel | Laravel Pint |
| **Test** | 56 tests (unitarios y de integración) sobre SQLite en memoria, con cobertura | PHPUnit 12 + PCOV |
| **Package** | Arma `educontrol-<commit>.zip` sin tests ni dependencias de desarrollo. Solo en `main` | `git archive`, Composer `--no-dev` |
| **Deploy** | Railway despliega `main` cuando el CI pasa (ver más abajo) | Railway |
| **Notify** | GitHub avisa por correo al autor del commit si el pipeline falla, y el PR muestra la tilde verde o la cruz roja | GitHub |

## Artefactos

Cada corrida guarda archivos descargables desde la página de la corrida, sección **Artifacts**:

| Artefacto | Contenido | Se conserva |
|---|---|---|
| `reportes-tests-<commit>` | `junit.xml` (resultado de cada test), `clover.xml` y `cobertura/` (reporte HTML navegable: abrir `index.html`) | 14 días |
| `educontrol-<commit>` | Paquete `.zip` listo para desplegar (solo en `main`) | 30 días |

Además, la página de cada corrida muestra un **resumen** con la cantidad de tests y el porcentaje de cobertura.

## Variables de entorno en el pipeline

El CI no usa credenciales. Copia `.env.example` a `.env` y genera una `APP_KEY` nueva en cada corrida. La base de los tests es SQLite en memoria, configurada en `phpunit.xml`. Las variables de producción se cargan en Railway (ver [despliegue.md](despliegue.md)).

## Conectar el despliegue continuo (Railway)

Para que Railway publique **solo** las versiones que pasaron el CI:

1. En Railway, abrí el servicio de la app → **Settings → Source**.
2. Verificá que la rama conectada sea `main`.
3. Activá **Wait for CI**. Desde ahí, Railway espera a que GitHub Actions termine en verde antes de desplegar, y si el CI falla no despliega.

Así queda el flujo completo: *merge del PR → CI en verde → Railway despliega → la nueva versión queda en línea*.

## Cómo reproducir el pipeline en tu máquina

```bash
composer install
vendor/bin/pint --test   # lint (sin --test, corrige el formato)
php artisan test         # tests
```

Para la cobertura local hace falta la extensión `pcov` o `xdebug`. En el CI ya está instalada.
