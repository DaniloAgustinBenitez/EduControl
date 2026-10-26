# Matriz de trazabilidad

Dónde se implementa cada requerimiento del plan de trabajo y qué test lo verifica.
Estado al 22/10/2026: **56 tests, 302 verificaciones, todos en verde** (`php artisan test` y CI de GitHub Actions), con 83,6 % de cobertura de líneas.

## Requerimientos funcionales

| Código | Implementado en | Test |
|---|---|---|
| RF-01 Alumnos (legajo, DNI, nombre, apellido, fecha de nacimiento, contacto) | `AlumnoController`, migración `agregar_contacto_y_legajo` | `RequerimientosTest::test_rf01_*`, `GestionTest::test_alumno_con_legajo_o_dni_repetido_*` |
| RF-02 Profesores (con legajo) | `ProfesorController`, `CuentaService` | `RequerimientosTest::test_rf02_*` |
| RF-03 Tutores vinculados a alumnos | `TutorController`, tabla `alumno_tutor` | `GestionTest::test_alta_de_tutor_*` |
| RF-04 Autenticación con 4 roles | `AuthController`, `Support\Rol`, Spatie Permission | `AccesoPorRolTest` (completo) |
| RF-05 Materias por docente | `ProfesorController::guardarAsignaciones` | `GestionTest::test_alta_de_profesor_y_asignaciones` |
| RF-06 Cursos y niveles por docente | `ProfesorController::guardarAsignaciones` | idem |
| RF-07 Alumno a un curso | `AlumnoController::asignarCurso` | `GestionTest::test_rn01_*` |
| RF-08 Inscripción al cursado | `InscripcionService::inscribirCursado` | `InscripcionServiceTest`, `GestionTest::test_autogestion_completa_*` |
| RF-09 Inscripción a deportes | `InscripcionService::inscribirDeporte` | `InscripcionServiceTest` (RN-03/04/05) |
| RF-10 Comedor | `InscripcionService::inscribirComedor` | `InscripcionServiceTest::test_comedor_sin_duplicados` |
| RF-11 Transporte con un recorrido | `InscripcionService::inscribirTransporte` | `InscripcionServiceTest::test_rn06_*` |
| RF-12 Consulta y baja | `AutogestionController`, `InscripcionService::baja/estado` | `InscripcionServiceTest::test_baja_*` |
| RF-13 Reporte por alumno | `ReporteService::porAlumno` | `RequerimientosTest::test_rf13_*` |
| RF-14 Reporte por docente | `ReporteService::porDocente` | `GestionTest::test_reportes_se_exportan_*` |
| RF-15 Reporte por curso | `ReporteService::porCurso` | idem |
| RF-16 Reporte por materia (nómina + docente) | `ReporteService::porMateria` | `RequerimientosTest::test_rf16_*` |
| RF-17 Reporte por deporte | `ReporteService::porDeporte` | `GestionTest::test_reportes_se_exportan_*` |
| RF-18 Reporte por recorrido | `ReporteService::porRecorrido` | idem |
| RF-19 Exportar a PDF y planilla | `ReporteController::pdf/planilla` (DomPDF, PhpSpreadsheet) | idem |
| RF-20 a RF-24 Configuración institucional | `Nivel/Curso/Materia/Deporte/Recorrido/HorarioController` | `GestionTest::test_rn02_*`, `test_alta_de_curso_*`, `AccesoPorRolTest` |

## Requerimientos no funcionales

| Código | Cómo se cumple | Test / evidencia |
|---|---|---|
| RNF-01 Acceso por rol | Middleware `permission` en todas las rutas, `AlumnoPolicy` para el tutor | `AccesoPorRolTest`, incluidas pruebas por URL directa |
| RNF-02 Contraseñas hasheadas | Cast `hashed` en `User` (bcrypt) | `AccesoPorRolTest::test_las_contrasenas_se_guardan_hasheadas` |
| RNF-03 HTTPS | `URL::forceScheme('https')` en producción + `trustProxies` | Pendiente de verificar en producción |
| RNF-04 Sin duplicados, datos al día | Índices únicos en las 4 tablas de inscripción; base única | `InscripcionServiceTest` |
| RNF-05 Errores en lenguaje claro | `lang/es/validation.php` con nombres de campo en español | `RequerimientosTest::test_rnf05_*` |
| RNF-06 Responsive y multinavegador | Bootstrap 5, menú lateral que colapsa, DataTables Responsive, campos de 16 px en celulares | Pruebas del 26/10 en `pruebas.md` (Edge, escritorio y celular). Falta Firefox y Safari |
| RNF-07 Menos de 3 segundos | Medido con 3000 alumnos: el reporte más pesado, 0,9 s | `php artisan reportes:medir`, tabla en `pruebas.md` |
| RNF-08 Disponibilidad | Depende del despliegue | Pendiente (Railway) |
| RNF-09 Respaldo y restauración | `db:respaldar` / `db:restaurar` | Prueba documentada en `respaldos.md` |
| RNF-10 Capas separadas | Controladores (presentación) → Services (negocio) → Eloquent (datos) | Las reglas viven solo en `app/Services` |

## Reglas de negocio

| Código | Implementada en | Test |
|---|---|---|
| RN-01 Un curso por alumno | `AlumnoController::asignarCurso`, único en `inscripciones_cursado` | `GestionTest::test_rn01_*`, `test_editar_alumno_no_cambia_su_curso` |
| RN-02 Curso con nivel | Validación + FK obligatoria | `GestionTest::test_rn02_*` |
| RN-03 Máximo 2 deportes | `InscripcionService::MAX_DEPORTES` | `InscripcionServiceTest::test_rn03_*` |
| RN-04 Deportes sin superposición | `SuperposicionHoraria` | `InscripcionServiceTest::test_rn04_*`, `SuperposicionHorariaTest` |
| RN-05 Deporte vs. cursado | `InscripcionService::horariosDeCursado` | `InscripcionServiceTest::test_rn05_*` |
| RN-06 Un recorrido | `InscripcionService::inscribirTransporte` + único | `InscripcionServiceTest::test_rn06_*` |
| RN-07 El tutor solo sus hijos | `AlumnoPolicy::operar` | `AccesoPorRolTest::test_rn07_*` (dos casos, por URL directa) |

## Historias de usuario

| HU | Estado | Dónde verla |
|---|---|---|
| HU1 Autenticación por rol | Hecha | `/login` con los cuatro usuarios de prueba |
| HU2 Configurar niveles, cursos y materias | Hecha | Menú *Institución* |
| HU3 Registrar alumnos, profesores y tutores | Hecha | Menú *Personas* |
| HU4 El docente ve lo suyo | Hecha | `/docente` |
| HU5 Inscripción al cursado | Hecha | `/autogestion` |
| HU6 Hasta dos deportes sin superposición | Hecha | `/autogestion/alumnos/{id}` |
| HU7 Comedor y transporte | Hecha | idem |
| HU8 Consulta y baja | Hecha | idem |
| HU9 Reportes consolidados | Hecha | `/reportes` |

## Pendientes conocidos

| Tema | Detalle |
|---|---|
| Despliegue (Objetivo Específico 4, RNF-03, RNF-08) | Falta crear la cuenta y publicar. Guía en `despliegue.md` |
| `main` protegida (sección 12.1) | GitHub exige plan Pro para repos privados |
| Wireframes y mockup (sección 11) | No están en el repositorio |
| Arquitectura, sección 9.5 | El plan menciona API REST/JSON; el sistema usa formularios con Blade. Ver `correcciones-plan.md` |
| Nombre del cuarto rol | El plan lo llama "padre"; el sistema lo muestra como "Tutor / Padre" |
| PDF de más de 500 filas | Se exporta con filtro o en Excel (límite de memoria de DomPDF) |
