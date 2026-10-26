# Calidad del software

Cómo el proyecto aplica el material de *Aseguramiento de la calidad del software* (capítulo 3). La definición que usamos es la del glosario IEEE: **el grado en que un sistema, componente o proceso cumple los requerimientos y las necesidades y expectativas del usuario**. Y como dice De Marco, *no se puede controlar lo que no se puede medir*, por eso cada atributo de abajo tiene su evidencia o se marca como pendiente.

## Atributos de calidad (modelo FURPS+)

| Atributo | Qué exige el sistema | Evidencia | Estado |
|---|---|---|---|
| **F**uncionalidad | 24 requerimientos funcionales, 7 reglas de negocio, 9 historias de usuario | [trazabilidad.md](trazabilidad.md): cada RF/RN con su código y su test. 56 tests, 302 verificaciones | Cumplido |
| **U**sabilidad | Interfaz clara, errores en español, uso en celular (RNF-05, RNF-06) | Mensajes en `lang/es`, Bootstrap 5 responsive. Pruebas por rol y de diseño responsivo ejecutadas el 26/10: 53 de 53 pasos OK ([pruebas.md](pruebas.md)). Falta probar en Firefox y Safari | Cumplido, salvo multinavegador |
| **R**eliability (fiabilidad) | Sin duplicados, respaldo y restauración (RNF-04, RNF-09) | Índices únicos en las inscripciones, `db:respaldar` y `db:restaurar` ([respaldos.md](respaldos.md)), CI en cada cambio | Cumplido |
| **P**erformance | Reportes en menos de 3 segundos (RNF-07) | Medido con 3003 alumnos: el peor caso, 0,94 s ([pruebas.md](pruebas.md)) | Cumplido |
| **S**oporte | Mantenible y configurable | Capas separadas (RNF-10), documentación en `docs/`, convenciones en [CONTRIBUTING](../CONTRIBUTING.md), Dependabot para dependencias | Cumplido |
| **+** Implementación | Laravel 13, PHP 8.3, MySQL 8 | `composer.json`, README | Cumplido |
| **+** Interfaz | Sin sistemas externos; formularios Blade (ver [correcciones-plan.md](correcciones-plan.md)) | — | No aplica |
| **+** Operaciones | Puesta en marcha y administración | [despliegue.md](despliegue.md), [respaldos.md](respaldos.md). Falta publicar en producción (RNF-03, RNF-08) | Parcial |
| **+** Empaquetamiento | Forma de distribución | `.zip` verificado en cada merge a `main` ([ci-cd.md](ci-cd.md)) | Cumplido |
| **+** Legales | Licencia | [LICENSE](../LICENSE) (MIT, igual que `composer.json`) | Cumplido |

## Control de calidad (SQC) y aseguramiento de la calidad (SQA)

El material distingue los dos: el **control** detecta problemas en el producto; el **aseguramiento** revisa que se siga el proceso.

| | Control de calidad (producto) | Aseguramiento de la calidad (proceso) |
|---|---|---|
| Qué revisa | El código y su comportamiento | Que el equipo cumpla CONTRIBUTING, la plantilla de PR y el CI |
| Cómo | Pint, PHPUnit con cobertura (83,6 % de líneas), `composer audit`, verificación del paquete | Plantilla de PR con checklist de autor y de revisor, `CODEOWNERS` para la revisión cruzada, registro de no conformidades |
| Cuándo | Automático, en cada push | En cada PR y al revisar este documento |
| Evidencia | Corridas en la pestaña Actions y artefactos | La tabla de no conformidades de más abajo |

El CI cubre bien el control. El aseguramiento depende de las personas: por eso las no conformidades de proceso se registran acá y como issues.

## Costo de corregir temprano

El apunte muestra que corregir un error cuesta de 15 a 40 veces más en las pruebas de desarrollo y de 40 a 1000 veces más en producción que en la etapa de requisitos. El pipeline mueve la detección al momento del `git push`. Ejemplo real: el primer push del pipeline falló porque PHPUnit 12 daba un warning en `AccesoPorRolTest` que en local nadie veía. Se corrigió en el mismo día y nunca llegó a `main`.

## Registro de no conformidades

Una **no conformidad** es el incumplimiento de un estándar o proceso del proyecto. Se registran como issues con la etiqueta `no-conformidad` (plantilla en `.github/ISSUE_TEMPLATE`), y el CI abre uno solo si falla `main`. Se comprobó el 26/10 con una falla simulada: el CI abrió el issue #68, que se cerró por ser un ensayo. Esta tabla resume las detectadas en la auditoría del 24/10/2026, actualizada el 26/10/2026.

| Id | No conformidad | Dónde se detectó | Estado | Acción |
|---|---|---|---|---|
| NC-01 | Los PR #59, #60, #61 y #64 se mergearon sin revisión cruzada | CONTRIBUTING, PR punto 4. `gh pr view`: 0 revisiones | **Abierta** | `CODEOWNERS` y checklist de revisor activos desde el #64. Los PR de Dependabot (#65, #66 y #67) esperan la revisión del otro integrante |
| NC-02 | Los PR se mergearon con *Merge commit*, no con *Squash and merge* (también el #64) | CONTRIBUTING, PR punto 5 | **Abierta** | En *Settings → General → Pull Requests* dejar habilitado solo *Squash merging* |
| NC-03 | `main` sin protección de rama | Issue #3 | **Abierta** | Limitación del plan (GitHub pide Pro o repo público). Mitigación: CI, plantilla y `CODEOWNERS` |
| NC-04 | `composer.lock` desfasado de `composer.json` desde el renombrado del 22/09 | Aviso en el log del CI del 22/10 que nadie atendió | Cerrada 24/10 | `composer update --lock` (sin cambiar versiones). Ahora `composer validate` frena el build si vuelve a pasar |
| NC-05 | Warning de PHPUnit en `AccesoPorRolTest` | Primera corrida del CI, 22/10 | Cerrada 22/10 | Data provider propio (commit `343ba93`) |
| NC-06 | La documentación mostraba como aplicados el despliegue y la regla de merge, sin estarlo | Auditoría del 24/10 | Cerrada 24/10 | Textos corregidos en [ci-cd.md](ci-cd.md) y [metricas.md](metricas.md) |
| NC-07 | Pruebas por rol y de diseño responsivo sin ejecutar | Issues #56 y #57; checklists vacías en [pruebas.md](pruebas.md) | Cerrada 26/10, salvo multinavegador | Ejecutadas con Playwright: 53 de 53 OK. Quedan pendientes Firefox, Safari, el horario de un deporte y los filtros de reportes |
| NC-08 | Sin archivo `LICENSE` aunque `composer.json` declara MIT | Auditoría del 24/10 | Cerrada 24/10 | Se agregó `LICENSE` |
| NC-09 | El selector de deportes de la autogestión medía 14 px: en iPhone la pantalla se agranda al tocarlo (RNF-06) | Pruebas responsivas del 26/10 | Cerrada 26/10 | Regla en `public/css/educontrol.css` que lleva a 16 px los campos chicos en celulares |
