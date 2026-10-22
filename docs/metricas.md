# Métricas de productividad

Punto 4 de la Parte 2 del TP de la Unidad 4. Medido el 21/10/2026.

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
| Sin versión empaquetada | Generar un `.zip` desplegable por cada merge a `main` (gestión de artefactos) | Aplicada |
| PR enormes y sin revisión | Regla de PR chicos y "CI en verde para mergear" en CONTRIBUTING y en la plantilla de PR | Aplicada |
| Despliegue pendiente | Conectar Railway a `main` con *Wait for CI* | Pendiente: requiere la cuenta de Railway |
| Tablero desactualizado | Activar en el Project el workflow *Item closed → Hecho* y mover las 49 tarjetas | Pendiente: se hace desde la configuración del Project |

## Comparativa antes / después

| Indicador | Antes | Después |
|---|---|---|
| Verificación de cada cambio | Manual, si alguien se acordaba de correr los tests | Automática en cada push y PR |
| Etapas automatizadas | 0 | 4 (build, lint, test, package) |
| Lint | Manual | Automático, bloquea el CI si falla |
| Cobertura de código | No medida | _completar con el resumen de la primera corrida_ |
| Duración del pipeline | — | _completar con la primera corrida_ |
| Artefactos por versión | Ninguno | Reporte de tests + cobertura (14 días) y paquete `.zip` (30 días) |
| Aviso de fallos | Ninguno | Correo de GitHub y cruz roja en el PR |
| Requisito para mergear | Solo los tests locales | Tests locales + CI en verde + PR chico |
