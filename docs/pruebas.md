# Pruebas

## Tests automáticos

```bash
php artisan test
```

| Archivo | Qué cubre |
|---|---|
| `tests/Unit/SuperposicionHorariaTest.php` | Casos límite del validador de horarios (5.6) |
| `tests/Feature/InscripcionServiceTest.php` | RN-01, RN-03, RN-04, RN-05 y RN-06, duplicados y bajas |
| `tests/Feature/AccesoPorRolTest.php` | Cada rol entra solo a lo suyo, incluso por URL directa (2.7, 5.9), además de login y hash de contraseñas |
| `tests/Feature/GestionTest.php` | ABM, asignaciones, autogestión completa del tutor y exportación de los 6 reportes |
| `tests/Feature/ConfiguracionTest.php` | ABM de niveles, materias, deportes y recorridos; no se borran deportes ni recorridos con inscriptos |
| `tests/Feature/RequerimientosTest.php` | RF-01, RF-02, RF-13, RF-16 y mensajes de error en español (RNF-05) |

Los tests corren también en el CI en cada push, con reporte de cobertura (ver [ci-cd.md](ci-cd.md)).

## Rendimiento (6.7, RNF-07)

Medido con 3003 alumnos cargados con `VolumenSeeder`, usando `php artisan reportes:medir` y peticiones HTTP reales:

| Reporte | Filas | Generación | Pantalla completa (HTTP) |
|---|---|---|---|
| Por alumno | 3003 | 598 ms | 0,89 s |
| Por curso | 3003 | 647 ms | 0,94 s |
| Por deporte | 3046 | 350 ms | — |
| Por recorrido | 1205 | 156 ms | — |
| Por materia | 64 | 13 ms | — |
| Por docente | 1 | 15 ms | — |

- **Excel:** el reporte por alumno completo (3003 filas) se exporta en 2,3 s.
- **PDF:** DomPDF arma el documento en memoria, así que el PDF admite hasta 500 filas. Con un filtro (por ejemplo, un recorrido o un curso) sale en menos de 2 s. Para reportes más grandes se usa el Excel.
- **Índices:** todas las claves foráneas, `alumnos.apellido` y las combinaciones únicas de inscripción tienen índice.

## Pruebas end-to-end por rol (7.4)

**Ejecutadas el 26/10/2026: 53 de 53 pasos OK.** Se automatizaron con Playwright sobre Microsoft Edge, con la app corriendo en local (`php artisan serve`) contra una base SQLite nueva cargada con `migrate:fresh --seed`. Cada paso se verificó en pantalla (mensaje de éxito o de error) y, cuando correspondía, también en la base. Los mensajes que mostró el sistema están en la tabla del final.

Hacer una vez con cada usuario de prueba y marcar:

**Administrador** (`admin@educontrol.test`)
- [x] Crear el ciclo 2027 activo y verificar que el 2026 queda cerrado.
- [x] Crear un nivel y un curso en ese nivel, y cargarle el horario de cursado.
- [x] Intentar una franja superpuesta en el mismo curso: tiene que rechazarla.
- [x] Crear un deporte y una materia. *(Pendiente: cargarle horario al deporte. La pantalla de horarios se probó con el curso.)*
- [x] Intentar borrar un nivel que tiene cursos: tiene que avisar y no borrarlo.

**Administrativo** (`administrativo@educontrol.test`)
- [x] Dar de alta un alumno y asignarle un curso.
- [x] Intentar asignarle un segundo curso: tiene que rechazarlo (RN-01).
- [x] Dar de alta un profesor, asignarle materias y verificar que se agregan su curso y su nivel.
- [x] Dar de alta un tutor vinculado al alumno nuevo.
- [x] Abrir los 6 reportes y exportar cada uno a PDF y Excel. *(Pendiente: probar los filtros.)*
- [x] Intentar entrar a `/niveles` escribiendo la URL: tiene que dar 403.

**Docente** (`docente@educontrol.test`)
- [x] Ver sus materias y cursos (1° año: Matemática y Biología).
- [x] Ver el listado de alumnos de 1° año.
- [x] Intentar abrir otro curso cambiando el id en la URL: tiene que dar 403.

**Tutor** (`tutor@educontrol.test`)
- [x] Ver a Sofía y a Tomás, y a nadie más.
- [x] Inscribir a Sofía en cursado, comedor, el recorrido Norte y Fútbol.
- [x] Intentar Básquet: se superpone con Fútbol y tiene que rechazarlo (RN-04).
- [x] Inscribirla en Vóley y verificar que no permite un tercer deporte (RN-03).
- [x] Intentar otro recorrido: tiene que pedir baja del actual (RN-06).
- [x] Dar de baja el comedor.
- [x] Cambiar en la URL el id de Sofía por el de otro alumno: tiene que dar 403 (RN-07).

## Diseño responsivo (7.5)

Probar en un celular real, o en las herramientas de desarrollo del navegador (F12 → icono de celular):

- [x] **Menú:** en el celular se colapsa en el botón ☰ y se abre bien.
- [x] **Tablas:** se adaptan (DataTables Responsive muestra un + para ver las columnas ocultas).
- [x] **Autogestión:** las tarjetas quedan una debajo de la otra.
- [x] **Formularios:** se pueden completar sin zoom. *(El selector de deportes de la autogestión medía 14 px y el iPhone hacía zoom; se corrigió el 26/10 en `educontrol.css`.)*
- [ ] **Navegadores:** probado solo en Edge, que usa el mismo motor que Chrome. Falta probar en Firefox y en Safari de iPhone.

### Resultado de la corrida del 26/10/2026

| Rol | Paso | Resultado | Lo que mostró el sistema |
|---|---|---|---|
| Tutor | Ingresa con su usuario | OK | http://127.0.0.1:8010/autogestion |
| Tutor | Ve a Sofía y a Tomás | OK | — |
| Tutor | No ve a nadie más (Lucía no aparece) | OK | — |
| Tutor | Inscribe a Sofía al cursado | OK | Inscripción registrada. |
| Tutor | Inscribe a Sofía al comedor | OK | Inscripción registrada. |
| Tutor | Inscribe a Sofía al recorrido Norte | OK | Inscripción registrada. |
| Tutor | Inscribe a Sofía en Fútbol | OK | Inscripción registrada. |
| Tutor | RN-04: rechaza Básquet porque se superpone con Fútbol | OK | Básquet se superpone con Fútbol (Lunes 15:00 a 16:30). |
| Tutor | Inscribe a Sofía en Vóley | OK | Inscripción registrada. |
| Tutor | RN-03: no permite un tercer deporte | OK | — |
| Tutor | RN-06: la pantalla pide dar de baja el recorrido actual para cambiarlo | OK | — |
| Tutor | RN-06: rechaza un segundo recorrido aunque se fuerce el envío | OK | El alumno ya tiene asignado el recorrido Norte. Solo se permite un recorrido: dalo de baja para elegir otro. |
| Tutor | Da de baja el comedor | OK | Se dio de baja la inscripción. |
| Tutor | La baja quedó registrada en la base | OK | — |
| Tutor | RN-07: el id de otro alumno en la URL da 403 | OK | HTTP 403 |
| Responsive | Autogestión: las tarjetas quedan una debajo de la otra | OK | [{"x":16,"w":358},{"x":16,"w":358},{"x":16,"w":358},{"x":16,"w":358}] |
| Responsive | Autogestión: sin scroll horizontal | OK | — |
| Responsive | Menú: en el celular se colapsa en el botón ☰ | OK | — |
| Responsive | Menú: el botón ☰ lo abre | OK | — |
| Responsive | Autogestión: campos de 16 px o más (sin zoom en el celular) | OK | todos de 16 px o más |
| Docente | Ve sus materias y cursos (1° año: Matemática y Biología) | OK | — |
| Docente | Ve el listado de alumnos de 1° año | OK | — |
| Docente | Otro curso cambiando el id en la URL da 403 | OK | HTTP 403 |
| Administrativo | Da de alta un alumno y le asigna un curso | OK | Alumno creado. |
| Administrativo | RN-01: rechaza asignarle un segundo curso | OK | Ruiz, Martín ya está asignado a 2° año — Secundario. Un alumno solo puede tener un curso: quitalo primero. |
| Administrativo | Da de alta un profesor | OK | Profesor creado. Asignale sus materias y cursos. |
| Administrativo | Asigna una materia y se agregan solos su curso y su nivel | OK | curso=true nivel=true |
| Administrativo | Da de alta un tutor vinculado al alumno nuevo | OK | Tutor creado. |
| Administrativo | Reporte por alumno: se abre y se exporta a PDF y Excel | OK | HTTP 200 pdf=true excel=true |
| Administrativo | Reporte por docente: se abre y se exporta a PDF y Excel | OK | HTTP 200 pdf=true excel=true |
| Administrativo | Reporte por curso: se abre y se exporta a PDF y Excel | OK | HTTP 200 pdf=true excel=true |
| Administrativo | Reporte por materia: se abre y se exporta a PDF y Excel | OK | HTTP 200 pdf=true excel=true |
| Administrativo | Reporte por deporte: se abre y se exporta a PDF y Excel | OK | HTTP 200 pdf=true excel=true |
| Administrativo | Reporte por recorrido: se abre y se exporta a PDF y Excel | OK | HTTP 200 pdf=true excel=true |
| Administrativo | Entrar a /niveles escribiendo la URL da 403 | OK | HTTP 403 |
| Responsive | Tablas: se adaptan sin scroll horizontal (DataTables Responsive) | OK | — |
| Responsive | Formularios de alta: campos de 16 px o más (sin zoom en el celular) | OK | todos de 16 px o más |
| Administrador | Abre /ciclos | OK | HTTP 200 |
| Administrador | Abre /niveles | OK | HTTP 200 |
| Administrador | Abre /cursos | OK | HTTP 200 |
| Administrador | Abre /materias | OK | HTTP 200 |
| Administrador | Abre /deportes | OK | HTTP 200 |
| Administrador | Abre /recorridos | OK | HTTP 200 |
| Administrador | Abre /profesores | OK | HTTP 200 |
| Administrador | Abre /tutores | OK | HTTP 200 |
| Administrador | Crea un nivel | OK | Nivel creado. |
| Administrador | Crea un curso en ese nivel | OK | Curso creado. Cargá ahora su horario de cursado. |
| Administrador | Le carga el horario de cursado | OK | Horario agregado. |
| Administrador | Rechaza una franja superpuesta en el mismo curso | OK | Esa franja se superpone con otra ya cargada. |
| Administrador | Crea un deporte | OK | Deporte creado. Cargá ahora sus horarios. |
| Administrador | Crea una materia | OK | Materia creada. |
| Administrador | Intenta borrar un nivel con cursos: avisa y no lo borra | OK | No se puede eliminar un nivel que tiene cursos. |
| Administrador | Crea el ciclo 2027 activo y el 2026 queda cerrado | OK | [{"anio":2026,"activo":0},{"anio":2027,"activo":1}] |
