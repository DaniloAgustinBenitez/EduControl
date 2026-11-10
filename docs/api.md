# API REST de EduControl (v1)

API JSON que usa la app mobile ([mobile/](../mobile/README.md)). Es el contrato de la sección 4 del [plan mobile](plan-mobile.md): la app y el backend se construyen contra este documento.

- **URL base:** `https://educontrol-5btq.onrender.com/api/v1` en producción, o `http://localhost:8000/api/v1` en local.
- **Formato:** todos los pedidos y respuestas son JSON. Los pedidos llevan `Accept: application/json`.
- **Autenticación:** token personal de Laravel Sanctum en el encabezado `Authorization: Bearer <token>`. El token vence a los 30 días.
- **Reglas de negocio:** las mismas que la web. La API llama a `InscripcionService` y a `AlumnoPolicy`, así que un pedido que la web rechaza, la API también lo rechaza, con el mismo mensaje.
- **Colección para probarla a mano:** [docs/api/](api/) (se abre con [Bruno](https://www.usebruno.com)).

## Objetos comunes

Se repiten en varias respuestas.

**Usuario**
```json
{
  "id": 4,
  "nombre": "Laura Pérez",
  "email": "tutor@educontrol.test",
  "rol": "tutor",
  "rol_etiqueta": "Tutor / Padre",
  "permisos": ["inscripciones.autogestion"],
  "usa_app": true
}
```
`rol` es `administrador`, `administrativo`, `docente` o `tutor`. `usa_app` es `true` solo para `tutor` y `docente`: la app muestra a los demás un aviso para que usen la web.

**Ciclo** (o `null` si no hay ciclo lectivo activo)
```json
{ "id": 1, "anio": 2026 }
```

**Horario**
```json
{ "dia": 1, "dia_nombre": "Lunes", "inicio": "08:00", "fin": "12:00", "descripcion": "Lunes 08:00 a 12:00" }
```
`dia` va de 1 (lunes) a 7 (domingo). `inicio` y `fin` siempre en formato `HH:MM`.

**Curso**
```json
{ "id": 5, "nombre": "3° A", "nivel": "Primario", "nombre_completo": "3° A — Primario" }
```

**Deporte**
```json
{ "id": 2, "nombre": "Fútbol", "descripcion": "Entrenamiento mixto", "horarios": [Horario] }
```

**Recorrido**
```json
{ "id": 1, "nombre": "Recorrido Norte", "descripcion": "Barrios ..." }
```

**Estado** (inscripciones de un alumno en el ciclo actual)
```json
{
  "cursado":    { "id": 10, "curso": Curso },
  "comedor":    { "id": 7 },
  "transporte": { "id": 3, "recorrido": Recorrido },
  "deportes":   [ { "id": 21, "deporte": Deporte } ]
}
```
`cursado`, `comedor` y `transporte` son `null` cuando el alumno no está inscripto. `deportes` es una lista, vacía si no tiene ninguno. El `id` de cada inscripción es el que se usa para darla de baja.

**AlumnoResumen**
```json
{
  "id": 1,
  "legajo": "A-0001",
  "nombre": "Sofía",
  "apellido": "Pérez",
  "nombre_completo": "Pérez, Sofía",
  "curso": Curso,
  "estado": Estado
}
```
`curso` es `null` si el alumno no tiene curso asignado. `estado` es `null` si no hay ciclo activo. No se exponen DNI, teléfono, correo ni domicilio del alumno.

## Endpoints

### Autenticación

#### `POST /login`

Público, con un máximo de 6 intentos por minuto.

```json
{ "email": "tutor@educontrol.test", "password": "password", "dispositivo": "Pixel 7" }
```
`dispositivo` es opcional (máximo 100 caracteres) y se guarda como nombre del token.

**200**
```json
{ "token": "1|Xk2...", "usuario": Usuario }
```
**422** si el correo o la contraseña no son correctos:
```json
{ "message": "El correo o la contraseña no son correctos.", "errors": { "email": ["El correo o la contraseña no son correctos."] } }
```

#### `POST /logout`

Requiere token. Revoca el token usado en el pedido. Responde **204** sin cuerpo.

#### `GET /perfil`

Requiere token. **200**
```json
{ "usuario": Usuario }
```

### Tutor

Requieren el permiso `inscripciones.autogestion`. Las rutas con `{alumno}` además pasan por `AlumnoPolicy::operar` (RN-07): si el alumno no es hijo del tutor, responden **403**.

#### `GET /hijos`

**200**
```json
{ "ciclo": Ciclo, "hijos": [AlumnoResumen] }
```
Ordenados por nombre, igual que en la web. Si el usuario no tiene tutor vinculado, `hijos` es una lista vacía.

#### `GET /hijos/{alumno}`

**200**
```json
{ "ciclo": Ciclo, "alumno": AlumnoResumen + { "horarios_cursado": [Horario] } }
```
Es decir, el `AlumnoResumen` con un campo más: `horarios_cursado`, los horarios de su curso (lista vacía si no tiene curso).

#### `GET /catalogo`

Lo que la app necesita para mostrar las opciones de inscripción. **200**
```json
{
  "ciclo": Ciclo,
  "max_deportes": 2,
  "deportes": [Deporte],
  "recorridos": [Recorrido]
}
```
Deportes y recorridos ordenados por nombre.

#### `POST /hijos/{alumno}/inscripciones/{servicio}`

`servicio` es `cursado`, `comedor`, `transporte` o `deporte`. Cuerpo:

| Servicio | Cuerpo |
|---|---|
| `cursado`, `comedor` | `{}` |
| `transporte` | `{ "recorrido_id": 1 }` |
| `deporte` | `{ "deporte_id": 2 }` |

**201**
```json
{ "message": "Inscripción registrada.", "estado": Estado }
```
**422** si se rompe una regla de negocio (ver [Errores](#errores)), si falta o no existe `recorrido_id` / `deporte_id`, o si no hay ciclo activo:
```json
{ "message": "No hay un ciclo lectivo abierto para inscripciones.", "regla": null }
```

#### `DELETE /hijos/{alumno}/inscripciones/{servicio}/{inscripcion}`

**200**
```json
{ "message": "Se dio de baja la inscripción.", "estado": Estado }
```
**404** si la inscripción no existe o no es de ese alumno: no se puede borrar la de otro cambiando el id.

### Docente

Requieren el permiso `docente.panel`.

#### `GET /docente/cursos`

**200**
```json
{
  "cursos": [
    Curso + {
      "horarios": [Horario],
      "materias": [ { "id": 12, "nombre": "Matemática" } ]
    }
  ]
}
```
Los cursos asignados al docente, ordenados por nivel y nombre. En cada curso, `materias` son solo las que dicta ese docente en ese curso. Si el usuario no tiene un profesor vinculado, `cursos` es una lista vacía.

#### `GET /docente/cursos/{curso}`

**200**
```json
{
  "curso": Curso + { "horarios": [Horario] },
  "materias": [ { "id": 12, "nombre": "Matemática" } ],
  "alumnos": [ { "id": 1, "legajo": "A-0001", "dni": "45111222", "nombre": "Sofía", "apellido": "Pérez", "nombre_completo": "Pérez, Sofía" } ]
}
```
Alumnos ordenados por apellido y nombre, con los mismos datos que la pantalla web del docente. **403** si el curso no está asignado al docente:
```json
{ "message": "Ese curso no está asignado a tu cuenta." }
```

## Errores

Todos en español y con `message` listo para mostrar al usuario.

| Código | Cuándo | Cuerpo |
|---|---|---|
| `401` | Sin token, o token vencido o revocado | `{ "message": "Tu sesión venció. Volvé a ingresar." }` |
| `403` | El rol no tiene permiso para esa sección | `{ "message": "Tu perfil no tiene acceso a esta sección." }` |
| `403` | Tutor sobre un alumno que no es su hijo (RN-07) | `{ "message": "Solo podés gestionar las inscripciones de los alumnos que tenés a cargo." }` |
| `404` | Recurso inexistente, o ruta que no existe | `{ "message": "No se encontró lo que buscabas." }` |
| `422` | Validación de campos | `{ "message": "...", "errors": { "campo": ["..."] } }` |
| `422` | Regla de negocio (`ReglaNegocioException`) | `{ "message": "Básquet se superpone con Fútbol (Martes 17:00 a 18:30).", "regla": "RN-04" }` |
| `429` | Más de 6 intentos de ingreso en un minuto | `{ "message": "Demasiados intentos. Esperá un minuto y volvé a intentar." }` |

`regla` puede ser `RN-01`, `RN-03`, `RN-04`, `RN-05`, `RN-06` o `null` (por ejemplo, inscribir dos veces al comedor).
