# Unidad 4: optimización del ciclo de desarrollo

Parte 2 del trabajo práctico (coevaluación del 27/10/2026). Estado al 26/10/2026.

> Todo lo de esta página está activo en `main` desde el merge del PR #64 (25/10/2026).

**Tipo de optimización aplicada:** integración continua con gestión de artefactos, sobre **GitHub Actions**. Se eligió porque el código ya vive en GitHub, no hace falta montar ni mantener un servidor de CI, el pipeline queda versionado junto al código (`.github/workflows/ci.yml`) y los resultados aparecen en cada pull request. El detalle técnico está en [ci-cd.md](ci-cd.md).

## 1. Funcionalidad seleccionada

**Inscripción a deportes (RF-09, HU6)**, con sus reglas de negocio: máximo dos deportes por alumno (RN-03), sin superposición entre deportes (RN-04) y sin superposición con el cursado (RN-05).

| Qué | Dónde |
|---|---|
| Regla de negocio | `app/Services/InscripcionService.php` (`inscribirDeporte`) y `app/Services/SuperposicionHoraria.php` |
| Pantalla | `/autogestion/alumnos/{id}`, entrando como `tutor@educontrol.test` |
| Tests | `InscripcionServiceTest` (`test_rn03_*`, `test_rn04_*`, `test_rn05_*`, `test_no_duplica_el_mismo_deporte`, `test_baja_libera_el_cupo`) y `SuperposicionHorariaTest` (casos límite de horarios) |

**Cómo mostrarla:** con Sofía Pérez, inscribir en Fútbol (se acepta), intentar Básquet (se rechaza por superponerse con Fútbol, RN-04), inscribir en Vóley (se acepta) e intentar un tercer deporte (se rechaza, RN-03). Los pasos completos están en [pruebas.md](pruebas.md).

## 2. Contenidos de la unidad aplicados y no aplicados

| Contenido | Estado | Herramientas y estrategias | Ejemplo de flujo |
|---|---|---|---|
| Integración Continua | **Aplicado** | GitHub Actions, Git con una rama por tarea, Pint, PHPUnit con PCOV | `git push` en la rama `Nilo` → el CI corre build, seguridad, lint y tests → se abre el PR a `main` → con el CI en verde se mergea |
| Despliegue Continuo | **No aplicado** | Preparado: paquete `.zip` verificado, ruta de salud `/up`, job de verificación de producción y guía de Railway con *Wait for CI* ([ci-cd.md](ci-cd.md), [despliegue.md](despliegue.md)). Falta la cuenta de Railway (issues #6 y #53) | Previsto: merge a `main` → CI en verde → Railway despliega → el CI consulta `/up` y confirma que responde |
| Build | **Aplicado** | `composer validate`, `composer install` con caché de Composer (`actions/cache`, clave por hash de `composer.lock`), PHP 8.3 con `setup-php` | Push → se restaura la caché → se validan e instalan las dependencias |
| Pipelines | **Aplicado** | Un workflow con cuatro jobs: `pruebas` → `paquete` → `verificar-produccion` y `notificar`. Se usan `needs`, condiciones por rama, `concurrency` y permisos mínimos del token | Commit → Build → Seguridad → Lint → Test → Package → Verify → Notify |
| Variables de entorno | **Aplicado** | `.env.example` copiado a `.env` con `APP_KEY` nueva en cada corrida, `.env` fuera del repo, `env:` del workflow, `phpunit.xml` con SQLite en memoria, variable de repositorio `URL_PRODUCCION`. Las de producción (Railway) están documentadas pero sin cargar | El CI arma su propio entorno en cada corrida, sin credenciales reales |
| Gestión de artefactos | **Aplicado** | `upload-artifact`: reportes de tests y cobertura (14 días) y paquete `.zip` por commit (30 días). El paquete se verifica antes de guardarse | Merge a `main` → job `paquete` → `.zip` sin tests ni `.env` → Verify del contenido → artefacto descargable |
| Integración de repositorios con herramientas CI/CD | **Aplicado** | GitHub ↔ GitHub Actions, insignia del CI en el README, Dependabot | Cada push y cada PR dispara el pipeline sin configurar nada a mano |
| Pull Requests y Merge Requests | **Aplicado** | PR hacia `main` con plantilla (checklist del autor y de quien revisa), `CODEOWNERS` para la revisión cruzada, CI en verde como requisito | Rama → PR → CI → revisión del otro integrante → *Squash and merge*. Pendiente: que las revisiones queden registradas (NC-01 en [calidad.md](calidad.md)) |
| Issues | **Aplicado** | 58 issues con etiquetas por integrante, `Closes #N` en los PR, plantillas de *Tarea* y de *No conformidad*, issue automático si falla `main` | Falla el CI en `main` → se abre un issue `no-conformidad` con el link a la corrida |
| Wikis | **No aplicado** | La wiki de GitHub está deshabilitada (probablemente por el plan: ya pasó con la protección de `main`). Se la reemplaza con la carpeta `docs/`, que viaja con el código y se revisa en los PR | Se cambia un doc en una rama y se revisa en el PR como cualquier código |
| Tablero Kanban | **Aplicado** | GitHub Projects: *Por hacer → En progreso → En revisión → Hecho*. 50 tarjetas en *Hecho* y 8 en *Por hacer* | Se abre el PR → la tarjeta pasa a *En revisión* → al mergear, `Closes #N` cierra el issue |
| Otro/s | **Aplicado** | `composer audit` (vulnerabilidades), Dependabot, Pint (estilo), cobertura con PCOV, registro de no conformidades ([calidad.md](calidad.md)) | Ver las dos opciones del punto 3 |

## 3. Contenidos no aplicados: opciones elegidas

Se evaluaron cuatro mejoras posibles:

| Opción | Decisión | Motivo |
|---|---|---|
| Despliegue Continuo | Preparada, no aplicable todavía | Depende de crear la cuenta de Railway |
| Wikis | Descartada | Depende del plan de GitHub; `docs/` cumple la misma función |
| **Seguridad de dependencias** | **Elegida** | Se puede aplicar completa ahora y la unidad pide análisis de vulnerabilidades en el CI |
| **Notificación y registro de fallos** | **Elegida** | Se puede aplicar completa ahora y cierra el flujo del pipeline (Notify) |

### Opción 1: seguridad y estado de las dependencias

- **Qué se hizo:** el CI corre `composer validate` (que el lock coincida con `composer.json`) y `composer audit` (falla si una dependencia tiene una vulnerabilidad publicada). [Dependabot](../.github/dependabot.yml) abre cada lunes un PR con las actualizaciones menores agrupadas.
- **Por qué:** una dependencia vulnerable o un lock desfasado se descubrían tarde. De hecho, el log del CI ya mostraba un `composer.lock` desactualizado desde el renombrado del proyecto (NC-04), que se corrigió con esta mejora.
- **Resultado:** `composer validate` y `composer audit` pasan en el CI de `main`. El 25/10 Dependabot abrió sus primeros 3 PR (Laravel 13.38, `actions/cache` v6 y `actions/upload-artifact` v7), los tres en verde.

### Opción 2: notificación y registro automático de fallos

- **Qué se hizo:** si el CI falla en `main`, el job `notificar` abre un issue con la etiqueta `no-conformidad`, el commit, el autor y el link a la corrida. Hay plantillas de issue para registrar a mano otras no conformidades, y la tabla resumen está en [calidad.md](calidad.md).
- **Por qué:** hasta ahora el aviso dependía del correo de cada persona. Con el issue, el fallo queda en el tablero hasta que alguien lo cierre, que es lo que pide el material de calidad (registrar las no conformidades en el sistema de incidencias).
- **Cómo demostrarlo:** *Actions → CI → Run workflow* con `simular_fallo` tildado. El pipeline falla a propósito y se abre el issue.
- **Resultado:** el ensayo del 26/10 abrió solo el issue [#68](https://github.com/DaniloAgustinBenitez/EduControl/issues/68) con la rama, el commit, el autor y el link a la corrida. Se cerró por ser una prueba.

## 4. Métricas de productividad

Herramientas, mediciones antes y después, estrategias y la tabla comparativa están en [metricas.md](metricas.md). Resumen:

| Indicador | Antes | Después |
|---|---|---|
| Tamaño del PR | 153 archivos, +16.517 líneas (#59) | 10 archivos, +434 líneas (#61) |
| Verificación de cada cambio | Manual | Automática en cada push y PR |
| Cobertura de líneas | No medida | 83,6 % |
| Corridas de CI | 0 | 21 al 26/10: 18 verdes y 3 rojas (dos por el mismo commit y una del ensayo de *Notify*) |
| Pruebas por rol y responsivas | Sin ejecutar | 53 de 53 pasos OK |

## Qué falta

| Pendiente | Qué hace falta |
|---|---|
| Despliegue Continuo | Crear la cuenta de Railway, conectar `main` con *Wait for CI* y cargar `URL_PRODUCCION` |
| Wiki | Decidir si se pasa el repositorio a público o se mantiene `docs/` |
| Revisión cruzada de PR (NC-01) | Que el otro integrante revise los PR de Dependabot (#65, #66 y #67) antes de mergearlos |
| Pruebas en Firefox y Safari | Repetir la checklist de [pruebas.md](pruebas.md) en esos navegadores |
