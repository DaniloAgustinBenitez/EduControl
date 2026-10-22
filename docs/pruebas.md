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

Hacer una vez con cada usuario de prueba y marcar:

**Administrador** (`admin@educontrol.test`)
- [ ] Crear el ciclo 2027 activo y verificar que el 2026 queda cerrado.
- [ ] Crear un nivel y un curso en ese nivel, y cargarle el horario de cursado.
- [ ] Intentar una franja superpuesta en el mismo curso: tiene que rechazarla.
- [ ] Crear un deporte con horario y una materia.
- [ ] Intentar borrar un nivel que tiene cursos: tiene que avisar y no borrarlo.

**Administrativo** (`administrativo@educontrol.test`)
- [ ] Dar de alta un alumno y asignarle un curso.
- [ ] Intentar asignarle un segundo curso: tiene que rechazarlo (RN-01).
- [ ] Dar de alta un profesor, asignarle materias y verificar que se agregan su curso y su nivel.
- [ ] Dar de alta un tutor vinculado al alumno nuevo.
- [ ] Abrir los 6 reportes, filtrar y exportar a PDF y Excel.
- [ ] Intentar entrar a `/niveles` escribiendo la URL: tiene que dar 403.

**Docente** (`docente@educontrol.test`)
- [ ] Ver sus materias y cursos (1° año: Matemática y Biología).
- [ ] Ver el listado de alumnos de 1° año.
- [ ] Intentar abrir otro curso cambiando el id en la URL: tiene que dar 403.

**Tutor** (`tutor@educontrol.test`)
- [ ] Ver a Sofía y a Tomás, y a nadie más.
- [ ] Inscribir a Sofía en cursado, comedor, el recorrido Norte y Fútbol.
- [ ] Intentar Básquet: se superpone con Fútbol y tiene que rechazarlo (RN-04).
- [ ] Inscribirla en Vóley y verificar que no permite un tercer deporte (RN-03).
- [ ] Intentar otro recorrido: tiene que pedir baja del actual (RN-06).
- [ ] Dar de baja el comedor.
- [ ] Cambiar en la URL el id de Sofía por el de otro alumno: tiene que dar 403 (RN-07).

## Diseño responsivo (7.5)

Probar en un celular real, o en las herramientas de desarrollo del navegador (F12 → icono de celular):

- [ ] **Menú:** en el celular se colapsa en el botón ☰ y se abre bien.
- [ ] **Tablas:** se adaptan (DataTables Responsive muestra un + para ver las columnas ocultas).
- [ ] **Autogestión:** las tarjetas quedan una debajo de la otra.
- [ ] **Formularios:** se pueden completar sin zoom.
- [ ] **Navegadores:** probar en Chrome, Firefox, Edge y Safari de iPhone, si hay uno a mano.
