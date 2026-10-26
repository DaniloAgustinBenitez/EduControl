# Métricas de productividad

Punto 4 de la Parte 2 del TP de la Unidad 4. Medido el 21/10/2026 y actualizado el 26/10/2026.

## Herramientas usadas

| Herramienta | Qué mide | Cómo se obtiene |
|---|---|---|
| **GitHub CLI (`gh`) + API** | Tamaño y tiempo de cada PR, revisiones, issues abiertos y cerrados, estado del tablero | `gh pr view`, `gh issue list`, consulta GraphQL al Project |
| **`git log`** | Frecuencia de commits e integraciones a `main` | `git log --format='%ad' --date=short` |
| **PHPUnit (`--log-junit`)** | Cantidad de tests, aserciones, fallos y tiempo de cada test | `vendor/bin/phpunit --log-junit junit.xml` |
| **PCOV (en el CI)** | Porcentaje de código cubierto por tests | Reporte de cobertura del pipeline |
| **GitHub Actions** | Duración del pipeline y tasa de corridas en verde | Pestaña *Actions* |
| **`php artisan reportes:medir`** | Tiempo de generación de cada reporte (RNF-07) | Ver [pruebas.md](pruebas.md) |

## Resultados antes de las mejoras

### Velocidad de entrega

| Métrica | Valor | Lectura |
|---|---|---|
| Integraciones a `main` | 2 PR en 2 días (#59 y #60) | Poca frecuencia: se integró por etapas completas |
| Tamaño del PR #59 | 153 archivos, +16.517 líneas, 49 issues cerrados de una vez | Lote muy grande: imposible de revisar a fondo |
| Tiempo abierto → mergeado | #59: 1 min 38 s · #60: 1 h 37 min | El #59 se mergeó sin revisar |
| Revisiones formales en PR | 0 de 2 | La revisión cruzada de CONTRIBUTING no quedó registrada |
| Corridas de CI | 0 | Toda la verificación dependía de correr los tests a mano |

### Cuellos de botella

| Métrica | Valor | Lectura |
|---|---|---|
| Issues abiertos | 8 de 58 | Casi todos son de **despliegue y cierre** (0.6, 7.1, 7.2, 7.4, 7.5, 7.6) |
| Issue bloqueante | #6 / #53, publicar en Railway | Sin producción no se pueden cerrar 7.2 (HTTPS) ni 7.6 (README con URL) |
| Tablero Kanban | 49 tarjetas en *En revisión* con el issue ya cerrado | El tablero no refleja el avance real: falta la automatización *cerrado → Hecho* |

### Calidad

| Métrica | Valor |
|---|---|
| Tests | 50 (10 unitarios, 40 de integración), 250 aserciones, 0 fallos |
| Duración de la suite | 6,4 s (medido por PHPUnit) |
| Test más lento | `test_reportes_se_exportan_a_pdf_y_planilla`: 2,2 s (34 % de la suite) |
| Estilo de código (Pint) | Sin errores, pero se verificaba solo a mano |
| Cobertura | No se medía |

## Estrategias de mejora

| Problema detectado | Estrategia | Estado |
|---|---|---|
| Sin verificación automática | Pipeline de CI en GitHub Actions: build, lint, tests y cobertura en cada push | Aplicada ([ci-cd.md](ci-cd.md)) |
| Cobertura desconocida | Medir cobertura con PCOV en el CI y publicarla como artefacto | Aplicada |
| ABM de configuración sin tests | Tests de alta, edición, duplicados y baja para niveles, materias, deportes y recorridos | Aplicada |
| Sin versión empaquetada | Generar un `.zip` desplegable por cada merge a `main` (gestión de artefactos) | Aplicada |
| PR enormes y sin revisión | Regla de PR chicos y "CI en verde para mergear" en CONTRIBUTING y en la plantilla de PR | Aplicada a medias: el tamaño bajó (ver la comparativa), pero sigue sin haber revisiones registradas (NC-01). Se sumaron `CODEOWNERS` y un checklist para quien revisa |
| Despliegue pendiente | Conectar Railway a `main` con *Wait for CI* | Pendiente: requiere la cuenta de Railway. Ya está lista la verificación de producción (`/up`), que se activa al cargar `URL_PRODUCCION` |
| Lock de Composer desfasado y sin chequeo de vulnerabilidades | `composer validate` y `composer audit` en el CI, más Dependabot semanal | Aplicada: pasó en el CI de `main` tras el merge del PR #64 (25/10) |
| Paquete `.zip` sin verificar | Etapa *Verify*: comprueba que el paquete tenga lo necesario y no incluya `.env`, tests ni dependencias de desarrollo | Aplicada: en `main` el 25/10 verificó un paquete de 8877 entradas sin `.env`, tests ni dependencias de desarrollo |
| Fallos que nadie ve | Job *Notify*: abre un issue `no-conformidad` si falla `main` | Aplicada: el ensayo del 26/10 con `simular_fallo` abrió solo el issue #68 |
| Tablero desactualizado | Mover las tarjetas de issues cerrados a *Hecho* y activar en el Project el workflow *Item closed → Hecho* | Tarjetas movidas (50 cerrados en *Hecho*, 8 abiertos en *Por hacer*). El workflow se activa desde la configuración del Project |

## Resultados del CI y segunda mejora

**Primera corrida (PR #61): falló.** Los 50 tests pasaban, pero PHPUnit 12 reportaba un warning en `AccesoPorRolTest`: el data provider le pasaba dos argumentos a un test que recibía uno. Con ese warning, PHPUnit termina con código 1. En local nadie lo había notado. Se corrigió con un data provider propio (`usuarios()`), y el CI pasó a verde.

**Cobertura medida por el CI:** 76,6 % de líneas. Las reglas de negocio estaban muy cubiertas (`InscripcionService` 97 %, `SuperposicionHoraria` y `ReporteService` 100 %), pero los ABM de configuración casi no tenían tests. Como estrategia se agregó `tests/Feature/ConfiguracionTest.php` (6 tests: alta, edición, duplicados, baja y baja rechazada con inscriptos).

| Controlador | Cobertura antes | Cobertura después |
|---|---|---|
| `RecorridoController` | 8,3 % | 100 % |
| `MateriaController` | 16,7 % | 100 % |
| `NivelController` | 18,2 % | 100 % |
| `DeporteController` | 20,0 % | 100 % |

## Comparativa antes / después

| Indicador | Antes | Después |
|---|---|---|
| Verificación de cada cambio | Manual, si alguien se acordaba de correr los tests | Automática en cada push y PR |
| Corridas de CI | 0 | 21 al 26/10: 18 verdes y 3 rojas. Dos son el mismo commit del 22/10 (una por `push` y otra por `pull_request`) y una es el ensayo de *Notify* |
| Etapas automatizadas | 0 | 4 medidas el 22/10 (build, lint, test, package). Desde el 25/10 en `main`, 7: se suman la auditoría de dependencias, la verificación del paquete y la notificación (y `composer validate` dentro del build) |
| Lint | Manual | Automático, bloquea el CI si falla |
| Errores ocultos detectados | 0 (el warning de PHPUnit pasaba desapercibido) | 2: el warning de PHPUnit (corregido en la primera corrida) y un `composer.lock` desfasado desde el 22/09, que el log del CI avisaba y nadie leyó (corregido el 24/10) |
| Tamaño del PR | 153 archivos, +16.517 líneas (#59) | 10 archivos, +434 líneas (#61) |
| Tiempo abierto → mergeado | #59: 1 min 38 s, sin ninguna verificación | #61: 18 min 6 s, con el CI en verde antes de mergear |
| Revisiones registradas en PR | 0 de 2 | 0 de 4 (sin mejora: NC-01 en [calidad.md](calidad.md)) |
| Tests | 50 tests, 250 verificaciones | 56 tests, 302 verificaciones |
| Cobertura de líneas | No medida (76,6 % al medirla) | 83,6 % |
| Cobertura de métodos | No medida (61,3 % al medirla) | 75,7 % |
| Duración del pipeline | No existía | 26 a 51 s por corrida, promedio de 33 s sobre las 9 corridas verdes (la de 51 s incluye el paquete en `main`). Fuente: `gh run list` |
| Artefactos por versión | Ninguno | Reporte de tests + cobertura (14 días) y paquete `.zip` (30 días) |
| Aviso de fallos | Ninguno | Correo de GitHub y cruz roja en el PR |
| Requisito para mergear | Solo los tests locales | Tests locales + CI en verde + PR chico, como convención de CONTRIBUTING. `main` sigue sin protección de rama por el plan de GitHub (NC-03), así que nada lo obliga |
| Tablero Kanban | 49 de 50 issues cerrados fuera de *Hecho* | 50 de 50 en *Hecho* |
| Dependencias | Sin control | `composer audit` sin vulnerabilidades y Dependabot activo: 3 PR abiertos el 25/10, los tres con el CI en verde |
| Pruebas por rol y responsivas | Checklist sin ejecutar | 53 de 53 pasos OK (26/10), con 1 defecto de usabilidad encontrado y corregido (NC-09) |
