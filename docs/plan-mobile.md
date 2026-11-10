# Plan de trabajo: EduControl Mobile

Plan para que EduControl funcione en celulares con una app propia para el **Tutor / Padre** y el **Docente**. Redactado el 10/11/2026. El apartado de la app está en [mobile/README.md](../mobile/README.md).

**Decisiones tomadas:**
- **Tecnología:** API REST en Laravel con Sanctum más una app hecha con Expo (React Native).
- **Perfiles de la app:** Tutor y Docente. La administración y los reportes siguen en la web.

## 1. Objetivo

Que un padre pueda inscribir o dar de baja a sus hijos en los servicios desde el celular, y que un docente pueda consultar sus cursos y alumnos, con las **mismas reglas de negocio** y la **misma base de datos** que la web.

Como efecto secundario, se cumple lo que el Plan de Trabajo original prometía en la sección 9.5 (intercambio de datos por **API REST en JSON**). Hoy figura como pendiente en [trazabilidad.md](trazabilidad.md) y en [correcciones-plan.md](correcciones-plan.md).

## 2. Punto de partida

Análisis del sistema al 10/11/2026 (rama `main`, commit `3c16a50`).

| Qué hay | Cómo lo aprovecha la app |
|---|---|
| Reglas RN-01 a RN-06 concentradas en `InscripcionService`, con interfaz documentada en [interfaz-inscripciones.md](interfaz-inscripciones.md) | La API llama a los mismos métodos. No se reescribe ninguna regla |
| `ReglaNegocioException` con mensaje listo para el usuario y código de regla | La API la devuelve como JSON (`422`) y la app muestra el mensaje tal cual |
| RN-07 en `AlumnoPolicy::operar` | Se aplica igual en cada endpoint del tutor |
| Permisos por rol con Spatie (`Support\Rol`) | Los endpoints usan los mismos middlewares `permission:*` |
| `bootstrap/app.php` ya responde JSON para `api/*` | Los errores de la API salen en JSON sin configurar nada |
| Web responsive (RNF-06) y producción en Render con HTTPS (RNF-03) | La app apunta a la misma URL de producción |
| CI con PHPUnit, Pint, Selenium y SonarQube; 56 tests y 83,6 % de cobertura | Se suman los tests de la API y un pipeline para la app |

| Qué falta | Dónde se resuelve |
|---|---|
| No hay `routes/api.php` ni autenticación por token: la web usa sesión y cookies | Etapa M1 |
| No hay ninguna app ni código JavaScript de cliente más allá de Vite | Etapas M2 a M4 |
| El Docker de Render, SonarQube y Husky solo conocen el código PHP | Tareas M2.2 y M5 |

## 3. Alcance

### Entra

| Perfil | Funciones en la app | Equivale en la web a |
|---|---|---|
| Todos | Ingresar con correo y contraseña, mantener la sesión abierta, cerrar sesión | `/login` |
| Tutor | Ver a sus hijos con el estado de cada servicio en el ciclo actual | `/autogestion` |
| Tutor | Inscribir y dar de baja: cursado, comedor, transporte (eligiendo recorrido) y deportes (viendo horarios) | `/autogestion/alumnos/{id}` |
| Docente | Ver sus materias agrupadas por curso, con niveles y horarios | `/docente` |
| Docente | Ver la nómina de alumnos de un curso asignado | `/docente/cursos/{id}` |
| Administrador y Administrativo | Pueden ingresar, pero la app les indica que usen la web | — |

### No entra (posibles mejoras futuras)

- Notificaciones push (por ejemplo, avisar al padre cuando se abre el ciclo lectivo).
- Funcionamiento sin conexión.
- Publicación en Google Play o App Store: se entrega un APK y se prueba con Expo Go.
- Configuración institucional, gestión de personas y reportes en la app.
- Recuperar contraseña: la web tampoco lo tiene.

### Requerimientos nuevos

Para agregar al documento de requerimientos y a la matriz de trazabilidad (tarea M0.2):

| Código | Requerimiento |
|---|---|
| RF-25 | El sistema expone una API REST en JSON, versionada (`/api/v1`) y autenticada por token |
| RF-26 | La app mobile permite ingresar con las cuentas existentes y redirige según el rol |
| RF-27 | Desde la app, el tutor consulta, inscribe y da de baja a sus hijos en los cuatro servicios |
| RF-28 | Desde la app, el docente consulta sus cursos, materias, horarios y alumnos |
| RNF-11 | El token se guarda cifrado en el dispositivo, vence a los 30 días y se revoca al cerrar sesión |
| RNF-12 | La app funciona en Android 10 o superior e iOS 16 o superior |
| RNF-13 | Si el servidor está dormido, la app avisa que está despertándolo en lugar de mostrar un error |
| HU10 | Como padre, quiero inscribir a mi hijo desde el celular para no depender de una computadora |
| HU11 | Como docente, quiero ver la lista de mi curso desde el celular para consultarla en el aula |

Las reglas **RN-01 a RN-07 no cambian**: se aplican igual que en la web porque la API usa el mismo código.

## 4. Arquitectura

| Decisión | Motivo |
|---|---|
| **Un solo repositorio:** la app vive en `mobile/` | Un único tablero, CI, historial y revisión por PR. El backend y la app cambian juntos cuando cambia el contrato de la API |
| **API versionada en `/api/v1`** | Si después cambia el formato, la app instalada sigue funcionando con `v1` |
| **Laravel Sanctum con tokens personales** | Es el paquete oficial de Laravel para apps mobile, no necesita OAuth y convive con la sesión web sin tocarla |
| **Controladores de API separados** (`app/Http/Controllers/Api/`) | Los de la web devuelven vistas Blade. Los de la API devuelven JSON con *API Resources*, y ambos llaman a los mismos Services |
| **API Resources** para armar el JSON | Controlan qué campos salen. Por ejemplo, el DNI del alumno no se expone si la app no lo usa |
| **Expo + Expo Router + TypeScript** | Se prueba en el celular con Expo Go sin compilar. Las rutas por archivos se parecen a las carpetas de vistas, y TypeScript detecta errores de forma del JSON antes de correr la app |

### 4.1 Endpoints

| Método y ruta | Acceso | Qué hace | Reusa |
|---|---|---|---|
| `POST /api/v1/login` | Público, `throttle:6,1` | Recibe correo, contraseña y nombre del dispositivo. Devuelve token, usuario, rol y permisos | Misma validación que `AuthController` |
| `POST /api/v1/logout` | Token | Revoca el token actual | — |
| `GET /api/v1/perfil` | Token | Usuario, rol, etiqueta del rol y permisos | `Support\Rol` |
| `GET /api/v1/hijos` | `inscripciones.autogestion` | Hijos con curso, nivel y estado de los cuatro servicios en el ciclo actual | `AutogestionController::index`, `InscripcionService::estado` |
| `GET /api/v1/hijos/{alumno}` | Ídem + `AlumnoPolicy::operar` | Detalle del alumno con horarios de cursado y estado | `AutogestionController::show` |
| `GET /api/v1/catalogo` | `inscripciones.autogestion` | Ciclo actual, deportes con horarios, recorridos y tope de deportes | `MAX_DEPORTES` |
| `POST /api/v1/hijos/{alumno}/inscripciones/{servicio}` | Ídem + policy | Inscribe. `servicio`: `cursado`, `comedor`, `transporte` (con `recorrido_id`) o `deporte` (con `deporte_id`) | `InscripcionService::inscribir*` |
| `DELETE /api/v1/hijos/{alumno}/inscripciones/{servicio}/{id}` | Ídem + policy | Da de baja. Responde `404` si la inscripción no es de ese alumno | `InscripcionService::baja` |
| `GET /api/v1/docente/cursos` | `docente.panel` | Materias agrupadas por curso, con nivel y horarios | `DocenteController::index` |
| `GET /api/v1/docente/cursos/{curso}` | `docente.panel` + curso asignado | Horarios, materias del docente y nómina ordenada por apellido | `DocenteController::curso` |

Si al armar los endpoints aparece lógica repetida entre `AutogestionController` y su par de la API, se mueve a un método de `InscripcionService` y ambos controladores lo llaman.

### 4.2 Respuestas de error

| Código | Cuándo | Cuerpo | Qué muestra la app |
|---|---|---|---|
| `401` | Sin token, token vencido o revocado | `{ "message": "..." }` | Vuelve al login |
| `403` | Rol sin permiso, o tutor sobre un alumno que no es suyo (RN-07) | `{ "message": "Solo podés gestionar..." }` | El mensaje |
| `404` | Alumno, curso o inscripción inexistente | `{ "message": "..." }` | "No se encontró" y vuelve atrás |
| `422` | Validación de campos | `{ "message": "...", "errors": { "campo": [...] } }` | El error debajo del campo |
| `422` | Regla de negocio | `{ "message": "No se puede: se superpone con Fútbol...", "regla": "RN-04" }` | El mensaje en un aviso |
| `429` | Más de 6 intentos de ingreso por minuto | `{ "message": "..." }` | "Esperá un minuto y volvé a intentar" |

Todos los mensajes salen en español, de `lang/es` o de las excepciones propias (RNF-05).

## 5. Etapas y tareas

Las horas son estimadas. Cada tarea es un issue del [tablero](https://github.com/users/DaniloAgustinBenitez/projects/2) con la etiqueta `mobile`.

### M0 — Preparación (4 h)

| # | Tarea | Entregable | Horas |
|---|---|---|---|
| M0.1 | Crear los issues de este plan en el tablero, con la etiqueta `mobile` | Issues en *Por hacer* | 1 |
| M0.2 | Agregar RF-25 a RF-28, RNF-11 a RNF-13 y HU10–HU11 al documento de requerimientos | Documento actualizado | 1 |
| M0.3 | Crear la cuenta gratuita de Expo e instalar Expo Go en un Android y, si hay, en un iPhone | Cuenta y dispositivos listos | 1 |
| M0.4 | Revisar el contrato de la sección 4 (por ahora Danilo trabaja solo: lo revisa él antes de mergear) | Sección 4 y [api.md](api.md) revisados | 1 |

### M1 — API REST en Laravel (24 h)

| # | Tarea | Entregable / criterio de aceptación | Horas | Depende de |
|---|---|---|---|---|
| M1.1 | `php artisan install:api`: Sanctum, `routes/api.php` y la migración `personal_access_tokens`. Agregar `HasApiTokens` al modelo `User` | La migración corre en local, en el CI (SQLite) y en Render | 2 | M0.4 |
| M1.2 | Endpoints `login`, `logout` y `perfil`, con vencimiento de tokens a 30 días (`sanctum.expiration`) | Login correcto devuelve token; contraseña incorrecta, `422`; 7.º intento, `429` | 3 | M1.1 |
| M1.3 | Verificar que los permisos de Spatie funcionen con el guard `sanctum` (ver riesgo R1) | Test: el tutor con token entra a `/hijos` y el docente recibe `403` | 2 | M1.2 |
| M1.4 | Respuestas de error JSON de la sección 4.2: `ReglaNegocioException` a `422` con `regla`, y mensajes en español | Test de cada código de error | 2 | M1.1 |
| M1.5 | API Resources: alumno, estado de inscripciones, deporte con horarios, recorrido, curso, materia | JSON sin campos sensibles (contraseñas, DNI que no se usa) | 3 | M1.1 |
| M1.6 | Endpoints del tutor: `hijos`, `hijos/{alumno}`, `catalogo`, inscribir y baja | Mismas respuestas que la web para los casos de [pruebas.md](pruebas.md) | 4 | M1.3–M1.5 |
| M1.7 | Endpoints del docente: `docente/cursos` y `docente/cursos/{curso}` | Un curso no asignado responde `403` | 2 | M1.3, M1.5 |
| M1.8 | Tests Feature: `Api/AuthTest`, `Api/AutogestionTest` (RN-03 a RN-07, incluida la RN-07 cambiando el id en la URL) y `Api/DocenteTest` | Todos en verde y cobertura total de 80 % o más | 5 | M1.6, M1.7 |
| M1.9 | Documentar la API en `docs/api.md` y armar una colección de [Bruno](https://www.usebruno.com) en `docs/api/` | Se puede probar cada endpoint sin la app | 1 | M1.6, M1.7 |

### M2 — Base de la app (14 h)

| # | Tarea | Entregable / criterio de aceptación | Horas | Depende de |
|---|---|---|---|---|
| M2.1 | Crear el proyecto con `npx create-expo-app@latest mobile` (TypeScript + Expo Router), más ESLint, Prettier, `.gitignore` y `.env.example` | `npx expo start` abre la app en Expo Go | 2 | M0.3 |
| M2.2 | Que `mobile/` no rompa lo existente: agregarla a `.dockerignore` y a `sonar.exclusions`, y revisar que Husky y Pint la ignoren | La imagen de Render no crece y el CI actual sigue en verde | 1 | M2.1 |
| M2.3 | Cliente HTTP (`src/api/cliente.ts`): URL desde `EXPO_PUBLIC_API_URL`, token en cada pedido, timeout de 70 s y traducción de errores de la sección 4.2 | Un `401` lleva al login y un `422` devuelve el mensaje | 3 | M2.1, M0.4 |
| M2.4 | Sesión: contexto con token y usuario, guardado en `expo-secure-store` y restaurado al abrir la app | Cerrar y abrir la app no pide login de nuevo; cerrar sesión borra el token | 3 | M2.3 |
| M2.5 | Pantalla de login con validación y mensajes de error | Mismos usuarios de prueba que la web | 2 | M2.4 |
| M2.6 | Redirección por rol: tutor a `(tutor)`, docente a `(docente)` y el resto a una pantalla que indica usar la web | Probado con los cuatro usuarios de prueba | 1 | M2.5 |
| M2.7 | Identidad visual: nombre, ícono, *splash* y colores tomados de `public/css/educontrol.css`, con modo claro y oscuro | La app se reconoce como EduControl | 2 | M2.1 |

La app puede avanzar con respuestas simuladas mientras se termina M1, porque el contrato ya está acordado en M0.4.

### M3 — Pantallas del tutor (16 h)

| # | Tarea | Entregable / criterio de aceptación | Horas | Depende de |
|---|---|---|---|---|
| M3.1 | **Mis hijos:** una tarjeta por hijo con curso y estado de los cuatro servicios; deslizar para actualizar; avisos para "sin hijos vinculados" y "no hay ciclo abierto" | Con `tutor@educontrol.test` aparecen Sofía y Tomás | 3 | M2.6, M1.6 |
| M3.2 | **Detalle del alumno:** encabezado con curso y horario de cursado, y una sección por servicio | Navegación desde la tarjeta | 2 | M3.1 |
| M3.3 | Cursado y comedor: inscribir y dar de baja, con confirmación antes de la baja | La web refleja el cambio al recargar | 2 | M3.2 |
| M3.4 | Transporte: elegir recorrido; si ya tiene uno, se muestra el mensaje de RN-06 | Mismo mensaje que la web | 2 | M3.2 |
| M3.5 | Deportes: lista con horarios, contador "1 de 2", inscribir y dar de baja | Repetir el guion de [unidad-4.md](unidad-4.md): Fútbol sí, Básquet no (RN-04), Vóley sí, un tercero no (RN-03) | 4 | M3.2 |
| M3.6 | Tests con Jest y React Native Testing Library, con la API simulada | Flujos de inscribir, error de regla y baja cubiertos | 3 | M3.3–M3.5 |

### M4 — Pantallas del docente (7 h)

| # | Tarea | Entregable / criterio de aceptación | Horas | Depende de |
|---|---|---|---|---|
| M4.1 | **Mis cursos:** materias agrupadas por curso, con nivel y horarios | Con `docente@educontrol.test` se ven sus asignaciones | 2 | M2.6, M1.7 |
| M4.2 | **Detalle del curso:** horarios y nómina ordenada por apellido, con buscador | Funciona con un curso de 40 alumnos sin trabarse | 3 | M4.1 |
| M4.3 | Tests de las dos pantallas | En verde en el CI | 2 | M4.2 |

### M5 — Calidad e integración continua (10 h)

| # | Tarea | Entregable / criterio de aceptación | Horas | Depende de |
|---|---|---|---|---|
| M5.1 | Workflow `.github/workflows/mobile.yml` que corre solo si cambia `mobile/**`: `npm ci`, ESLint, `tsc --noEmit` y Jest con cobertura | Visible en cada PR que toca la app | 2 | M2.1 |
| M5.2 | Pre-commit: si hay archivos de `mobile/` en el commit, correr ESLint sobre ellos | Un error de lint frena el commit | 1 | M2.1 |
| M5.3 | SonarQube: agregar `mobile/app` y `mobile/src` como fuentes TypeScript, con la cobertura de Jest (`lcov`) | La app aparece en el análisis | 1 | M3.6 |
| M5.4 | Checklist de pruebas manuales por rol en Android y en iOS con Expo Go, con el resultado en [pruebas.md](pruebas.md) | Todos los pasos en OK, o los fallos registrados como no conformidad | 3 | M3, M4 |
| M5.5 | Accesibilidad básica: botones de al menos 44 px, textos para lector de pantalla y contraste en modo oscuro | Revisado con TalkBack o VoiceOver | 1 | M3, M4 |
| M5.6 | Medir el tiempo de respuesta de cada endpoint con el servidor despierto (RNF-07: menos de 3 s) | Tabla en [pruebas.md](pruebas.md) | 2 | M1.8 |

### M6 — Entrega (9 h)

| # | Tarea | Entregable / criterio de aceptación | Horas | Depende de |
|---|---|---|---|---|
| M6.1 | Verificar que Render corra la migración de Sanctum al desplegar y que la API responda en producción | `POST /api/v1/login` funciona contra `educontrol-5btq.onrender.com` | 1 | M1 en `main` |
| M6.2 | Generar el APK de Android con EAS Build (perfil `preview`, gratis) apuntando a producción | APK descargable e instalado en un celular real | 3 | M6.1, M5.4 |
| M6.3 | Agregar la app al manual de usuario, con capturas | `docs/manual-de-usuario.pdf` actualizado | 2 | M6.2 |
| M6.4 | Actualizar [trazabilidad.md](trazabilidad.md), [correcciones-plan.md](correcciones-plan.md) (la sección 9.5 ya no es una corrección: la API existe) y el [README](../README.md) | Documentación coherente con el sistema | 2 | M6.2 |
| M6.5 | Guion de demostración para la defensa: web y app en simultáneo con la misma cuenta de tutor | Guion en [unidad-4.md](unidad-4.md) o en un documento propio | 1 | M6.2 |

**Total estimado: 84 horas**, unas 42 por integrante.

## 6. Cronograma

Cinco semanas con dos personas en paralelo: una lleva el **backend** (API, CI y despliegue) y la otra la **app**. Si se empieza el lunes 16/11/2026, termina el 20/12/2026. Hay que ajustarlo a la fecha de entrega de la cátedra.

| Semana | Backend | App | Hito |
|---|---|---|---|
| 1 | M0 (juntos), M1.1 a M1.4 | M2.1, M2.2, M2.7 | Contrato acordado; login por API funcionando |
| 2 | M1.5 a M1.9 | M2.3 a M2.6 | API completa y probada; la app ingresa y redirige por rol |
| 3 | M5.1, M5.2, M5.6 | M3.1 a M3.5 | El tutor inscribe desde el celular |
| 4 | M4.1 a M4.3 | M3.6, M5.3, M5.5 | El docente consulta sus cursos; tests de la app en el CI |
| 5 | M6.1, M6.4 | M5.4, M6.2, M6.3 | APK instalado, pruebas por rol completas, documentación al día |
| — | M6.5 (juntos) | | Listo para la defensa |

## 7. Ramas, PR e issues

Se siguen las reglas de [CONTRIBUTING.md](../CONTRIBUTING.md): una rama por tarea creada desde `main`, PR chicos, CI en verde, revisión del otro integrante y *Squash and merge*.

| Etapa | Ramas sugeridas |
|---|---|
| M1 | `feature/rf-25-api-autenticacion` (M1.1–M1.4), `feature/rf-25-api-tutor` (M1.5, M1.6), `feature/rf-25-api-docente` (M1.7), `docs/api` (M1.9) |
| M2 | `feature/rf-26-app-base` (M2.1, M2.2, M2.7), `feature/rf-26-app-login` (M2.3–M2.6) |
| M3 | `feature/rf-27-app-tutor-hijos` (M3.1, M3.2), `feature/rf-27-app-tutor-servicios` (M3.3–M3.6) |
| M4 | `feature/rf-28-app-docente` |
| M5 | `feature/ci-mobile` (M5.1–M5.3), `docs/pruebas-mobile` (M5.4–M5.6) |
| M6 | `docs/entrega-mobile` |

Commits con el formato de siempre, por ejemplo: `feat(api): endpoints de inscripción del tutor (RF-25)` o `feat(mobile): pantalla de deportes con contador (RF-27, RN-03)`.

## 8. Pruebas

| Nivel | Herramienta | Qué cubre |
|---|---|---|
| Unitarias y de integración de la API | PHPUnit (ya existente) | Autenticación por token, permisos, RN-03 a RN-07 por la API y formato de errores |
| Componentes de la app | Jest + React Native Testing Library | Que cada pantalla muestre los datos y los errores que devuelve la API |
| Contrato | Colección de Bruno (M1.9) | Probar la API a mano sin la app |
| De punta a punta | Checklist manual en Expo Go (M5.4) | Flujos completos del tutor y del docente en Android e iOS |
| Regresión de la web | Selenium y PHPUnit actuales | Que agregar la API no rompa nada de lo que ya funcionaba |

## 9. Riesgos

| # | Riesgo | Impacto | Cómo se mitiga |
|---|---|---|---|
| R1 | Los permisos de Spatie se crearon con el guard `web` y con tokens el guard es `sanctum`: el middleware `permission:*` podría rechazar todo | Alto | Se prueba primero (M1.3). Si falla, se configura el guard en los middlewares de la API |
| R2 | Render gratis se duerme a los 15 minutos y tarda hasta un minuto en volver | Medio | Timeout de 70 s, pantalla "Despertando el servidor…" y un pedido a `/up` al abrir la app (RNF-13) |
| R3 | El celular no llega al Laravel local | Medio | `php artisan serve --host=0.0.0.0`, la IP de la PC y permiso en el firewall de Windows; si no, usar producción |
| R4 | Expo cambia de versión seguido y rompe dependencias | Medio | Fijar la versión del SDK y agregar paquetes siempre con `npx expo install` |
| R5 | Robo del token si alguien accede al celular | Medio | Token en `expo-secure-store`, vencimiento a 30 días, revocación al cerrar sesión y solo HTTPS en producción |
| R6 | La carpeta `mobile/` agranda la imagen de Docker o confunde a las herramientas de PHP | Bajo | M2.2: `.dockerignore`, exclusiones de SonarQube y Husky |
| R7 | Que el alcance crezca (notificaciones push, reportes en la app) | Medio | Todo lo de "No entra" se anota como issue con la etiqueta `futuro` y no se hace en este plan |
| R8 | No tener un iPhone para probar | Bajo | Se prueba en Android y se deja asentado; Expo Go en iOS no necesita una Mac |

## 10. Documentación a actualizar

| Documento | Cambio |
|---|---|
| [README.md](../README.md) | Sección de la app, cómo correrla y link al APK |
| [mobile/README.md](../mobile/README.md) | Pasar el estado de "en planificación" a "disponible" y completar los comandos reales |
| `docs/api.md` (nuevo) | Endpoints, ejemplos de pedido y respuesta, y errores |
| [trazabilidad.md](trazabilidad.md) | Filas de RF-25 a RF-28, RNF-11 a RNF-13 y HU10–HU11; quitar el pendiente de la sección 9.5 |
| [correcciones-plan.md](correcciones-plan.md) | La sección 9.5 ya no se corrige: el sistema tiene API REST. Puede volver Postman o Bruno a la sección 10.2 |
| [pruebas.md](pruebas.md) | Checklist mobile, resultados y tiempos de la API |
| [ci-cd.md](ci-cd.md) | El workflow `mobile.yml` |
| Manual de usuario | Capítulo de la app |

## 11. Definición de terminado

Una tarea pasa a *Hecho* cuando:

1. Hace lo que dice su criterio de aceptación.
2. Tiene tests, y `php artisan test` y los tests de la app pasan.
3. El CI está en verde.
4. El otro integrante revisó el PR.
5. Si toca pantallas, se probó en un celular real con Expo Go.
6. La documentación de la sección 10 que le corresponde está actualizada.

## 12. Estado de avance

Actualizado al 10/11/2026. Los issues del tablero son el [#75 al #87](https://github.com/DaniloAgustinBenitez/EduControl/issues?q=label%3Amobile).

| Tarea | Estado | Evidencia o lo que falta |
|---|---|---|
| M0.1 Issues en el tablero | Hecha | Issues #75 a #87 con la etiqueta `mobile`, uno por PR de la sección 7 |
| M0.2 Requerimientos en el documento del plan | **Pendiente** | Pasar RF-25 a RF-28, RNF-11 a RNF-13 y HU10–HU11 al documento. Ya están en [trazabilidad.md](trazabilidad.md) |
| M0.3 Cuenta de Expo y Expo Go | **Pendiente** | La tienen que crear los integrantes |
| M0.4 Contrato de la API | **Pendiente de revisión** | El contrato está escrito en [api.md](api.md) y la API y la app ya lo cumplen; falta que Danilo lo revise en el diff del PR antes de mergear |
| M1.1 a M1.9 API REST | Hecha | 39 tests en `tests/Feature/Api/`, prueba de punta a punta 31 de 31 OK ([pruebas.md](pruebas.md)), colección de Bruno en [api/](api/). El riesgo R1 no se dio: Spatie funciona con el token sin cambios |
| M2.1 a M2.7 Base de la app | Hecha | Expo SDK 58 con TypeScript y Expo Router. `mobile/` excluida del Docker y sumada a SonarQube |
| M3.1 a M3.6 Pantallas del tutor | Hecha | `mobile/app/(tutor)/hijos`, tests en `tutor.test.tsx` |
| M4.1 a M4.3 Pantallas del docente | Hecha | `mobile/app/(docente)/cursos`, tests en `docente.test.tsx` |
| Diseño igual a la web | Hecha (10/11) | Colores de `educontrol.css`, íconos equivalentes a Bootstrap Icons, barra superior con el ciclo, modo claro por defecto con botón de tema. Recorrido con clics reales en el navegador contra la API local: 19 de 19 pasos OK |
| M5.1 Workflow `mobile.yml` | Hecha | Corre en el primer push que toque `mobile/` |
| M5.2 Pre-commit con ESLint | Hecha | `.husky/pre-commit` |
| M5.3 SonarQube | Hecha | `sonar-project.properties` con `mobile/app`, `mobile/src` y la cobertura `lcov` |
| M5.4 Checklist en celulares | **Pendiente** | Necesita un Android y, si hay, un iPhone con Expo Go: checklist en [pruebas.md](pruebas.md) |
| M5.5 Accesibilidad | Hecha en el código | Roles y etiquetas de accesibilidad y botones de 44 px o más. Falta revisarla con TalkBack o VoiceOver (está en la checklist) |
| M5.6 Tiempos de la API | Hecha | Todos los endpoints por debajo de 1,1 s ([pruebas.md](pruebas.md)) |
| M6.1 API en producción | **Parcial** | `docker/inicio.sh` corre `migrate --force` en cada arranque, así que la tabla de tokens se crea sola. Falta confirmar el login contra Render después del merge a `main` |
| M6.2 APK con EAS Build | **Pendiente** | Necesita la cuenta de Expo: `npm install -g eas-cli`, `eas login`, `eas build:configure` y `eas build -p android --profile preview` |
| M6.3 Manual de usuario | **Pendiente** | Agregar el capítulo de la app con capturas sacadas del celular |
| M6.4 Documentación | Hecha | README, [trazabilidad.md](trazabilidad.md), [correcciones-plan.md](correcciones-plan.md), [calidad.md](calidad.md), [ci-cd.md](ci-cd.md) y [pruebas.md](pruebas.md) |
| M6.5 Guion de la defensa | Hecha | Anexo de abajo |

**Cambio respecto de la estructura prevista:** las pantallas quedaron en `app/(tutor)/hijos/` y `app/(docente)/cursos/` en lugar de un `index.tsx` dentro de cada grupo. Con dos `index.tsx`, los grupos `(tutor)` y `(docente)` chocaban en la misma ruta `/`. Ahora `app/index.tsx` decide a dónde ir según el rol.

## Anexo: guion de demostración para la defensa (M6.5)

Unos cinco minutos. Hace falta la web abierta en la computadora y la app en un celular con Expo Go (o el APK), las dos contra producción. Diez minutos antes, abrir la web para que Render se despierte.

1. **El problema (30 s).** El padre hoy tiene que usar una computadora para inscribir a sus hijos. La app lo resuelve sin duplicar reglas: la app y la web usan el mismo `InscripcionService`.
2. **Ingreso por rol (30 s).** En la app, ingresar como `admin@educontrol.test`: aparece "Usá la versión web". Cerrar sesión e ingresar como `tutor@educontrol.test`.
3. **Mis hijos (30 s).** Se ven Sofía y Tomás con el estado de cada servicio. Deslizar hacia abajo para actualizar.
4. **Reglas de negocio desde el celular (1 min 30 s).** Con Sofía:
   - inscribir en Fútbol: se acepta;
   - intentar Básquet: aparece "Básquet se superpone con Fútbol…" (RN-04);
   - inscribir en Vóley: el contador marca "2 de 2" (RN-03).
5. **Web y app comparten los datos (30 s).** En la computadora, entrar con el mismo tutor a `/autogestion`: Sofía aparece con Fútbol y Vóley. Dar de baja Vóley en la web, volver a la app y deslizar para actualizar: ya no está.
6. **Docente (30 s).** Cerrar sesión e ingresar como `docente@educontrol.test`: sus cursos con materias, y la nómina de 1° año con el buscador.
7. **Calidad (1 min).** En GitHub:
   - la corrida de **CI** (PHPUnit con los 39 tests de la API) y la de **Mobile** (ESLint, TypeScript, Jest y el bundle de Android);
   - [api.md](api.md) como contrato entre la app y el servidor;
   - en [trazabilidad.md](trazabilidad.md), las filas de RF-25 a RF-28.

Si Render no despierta a tiempo, la app muestra "Despertando el servidor…". Es justamente lo que pide RNF-13, así que también sirve para mostrarlo.
