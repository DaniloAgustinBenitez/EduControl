# Modelo de datos

El DER completo está en [der.dbml](der.dbml). Para verlo gráficamente, pegá ese archivo en [dbdiagram.io](https://dbdiagram.io/d).

## Entidades (tarea 1.1)

| Entidad | Atributos principales | Requerimiento |
|---|---|---|
| Nivel | nombre | RF-20 |
| Curso | nivel (obligatorio), nombre | RF-21, RN-02 |
| Materia | curso, nombre | RF-22 |
| Deporte | nombre, descripción, horarios | RF-23 |
| Recorrido | nombre, zonas | RF-24 |
| Horario | día, hora de inicio, hora de fin (de un curso o de un deporte) | RN-04, RN-05 |
| Ciclo lectivo | año, activo | RF-08 |
| Alumno | legajo y DNI únicos, nombre, apellido, fecha de nacimiento, curso | RF-01, RF-07 |
| Profesor | DNI, nombre, apellido, teléfono, cuenta de usuario | RF-02 |
| Tutor | DNI, nombre, apellido, teléfono, cuenta, alumnos a cargo | RF-03 |
| Administrativo | DNI, nombre, apellido, cuenta de usuario | RF-04 |
| Usuario | nombre, correo, contraseña (hash), rol | RF-04, RNF-02 |
| Inscripción al cursado / comedor / transporte / deporte | alumno, ciclo lectivo y el servicio elegido | RF-08 a RF-12 |

## Decisiones de diseño

**Horario como entidad propia (1.2).** Un horario es una franja `día + hora_inicio + hora_fin`. Se relaciona de forma polimórfica con `cursos` (horario de cursado) y con `deportes`, así la misma lógica de superposición sirve para las dos reglas: RN-04 entre deportes y RN-05 entre un deporte y el cursado.

**Ciclo lectivo como dimensión (1.3).** Todas las inscripciones llevan `ciclo_lectivo_id`. Así se puede consultar el histórico y reinscribir cada año sin borrar lo anterior. Solo un ciclo está *activo* a la vez, y es el que habilita las inscripciones.

**Una tabla por tipo de inscripción.** Cada servicio tiene datos distintos (el transporte guarda un recorrido, el deporte guarda un deporte), y con tablas separadas la unicidad se garantiza con índices únicos simples, sin columnas nulas.

**Cuenta vinculada a su entidad (2.3).** `profesores`, `tutores` y `administrativos` tienen un `user_id` único. De esa forma, al entrar al sistema, un docente ve sus cursos y un padre ve solo a sus hijos.

## Restricciones de unicidad (1.6)

| Regla | Cómo se garantiza |
|---|---|
| RN-01: un curso por alumno | `alumnos.curso_id` es una sola columna, y la asignación rechaza un segundo curso. `inscripciones_cursado` es única por (alumno, ciclo). |
| RN-06: un recorrido por alumno | Índice único (alumno, ciclo) en `inscripciones_transporte` |
| RNF-04: sin inscripciones duplicadas | Índices únicos en las cuatro tablas de inscripción |
| Legajo y DNI únicos | Índices únicos en `alumnos`, y DNI único en profesores, tutores y administrativos |
| Nombres únicos | Nivel, deporte y recorrido únicos; curso único dentro de su nivel; materia única dentro de su curso |

## Integridad referencial

- Un nivel con cursos, o un deporte o recorrido con inscriptos, **no se pueden borrar** (`restrictOnDelete`). La pantalla avisa en lugar de fallar.
- Si se borra un alumno, se borran también sus inscripciones y sus vínculos con tutores (`cascadeOnDelete`).
- Si se borra un curso, sus alumnos quedan sin curso (`nullOnDelete`) y sus materias se eliminan.

## Datos de prueba (1.7)

`php artisan db:seed` carga:
- **Niveles, cursos y materias:** 3 niveles (Inicial, Primario, Secundario), 13 cursos con su horario de cursado de lunes a viernes y 64 materias.
- **Deportes:** 6, con sus horarios. Básquet se superpone con Fútbol a propósito, para mostrar la validación.
- **Recorridos:** los 4 recorridos de transporte.
- **Ciclo lectivo:** el 2026, activo.
- **Usuarios y alumnos:** un usuario por rol y tres alumnos. Dos son hijos del tutor de prueba y la tercera no, para probar la RN-07.
