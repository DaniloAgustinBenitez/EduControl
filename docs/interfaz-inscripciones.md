# Servicio de inscripciones: interfaz y reglas

Acuerdo de la tarea 5.1. Toda inscripción o baja pasa por `App\Services\InscripcionService`. Los controladores **no** validan reglas de negocio: llaman al servicio y muestran el mensaje de la excepción.

## Interfaz

```php
$servicio = app(InscripcionService::class);

$servicio->inscribirCursado(Alumno $alumno, CicloLectivo $ciclo): InscripcionCursado
$servicio->inscribirComedor(Alumno $alumno, CicloLectivo $ciclo): InscripcionComedor
$servicio->inscribirTransporte(Alumno $alumno, CicloLectivo $ciclo, Recorrido $recorrido): InscripcionTransporte
$servicio->inscribirDeporte(Alumno $alumno, CicloLectivo $ciclo, Deporte $deporte): InscripcionDeporte

$servicio->baja(Alumno $alumno, string $servicio, int $inscripcionId): bool   // $servicio: cursado|comedor|transporte|deporte
$servicio->estado(Alumno $alumno, CicloLectivo $ciclo): array                 // ['cursado', 'comedor', 'transporte', 'deportes']
```

- Si una regla no se cumple, se lanza `App\Exceptions\ReglaNegocioException`. Su mensaje está listo para mostrar al usuario, y `$e->regla` indica el código de la regla (`'RN-03'`, etc.).
- `baja()` devuelve `false` si la inscripción no pertenece a ese alumno. Así no se puede borrar la inscripción de otro cambiando el id en la URL.
- El permiso (quién puede operar sobre qué alumno) **no** es responsabilidad del servicio: lo resuelve `AlumnoPolicy::operar` antes de llamarlo.

## Reglas de negocio

| Código | Regla | Dónde se valida |
|---|---|---|
| RN-01 | Un alumno pertenece a un único curso | `AlumnoController::asignarCurso` + índice único en `inscripciones_cursado` |
| RN-02 | Todo curso pertenece a un nivel | Validación en `CursoController` + FK obligatoria |
| RN-03 | Máximo de 2 deportes por alumno | `InscripcionService::inscribirDeporte` |
| RN-04 | Los deportes de un alumno no pueden superponerse entre sí | `InscripcionService::inscribirDeporte` + `SuperposicionHoraria` |
| RN-05 | Un deporte no puede superponerse con el horario de cursado | `InscripcionService::inscribirDeporte` + `SuperposicionHoraria` |
| RN-06 | Un único recorrido de transporte | `InscripcionService::inscribirTransporte` + índice único |
| RN-07 | El tutor solo opera sobre sus hijos vinculados | `AlumnoPolicy::operar`, probada también por URL directa |

## Superposición horaria (5.6)

`SuperposicionHoraria::seSuperponen($a, $b)` considera que dos franjas se pisan si son del mismo día y `a.inicio < b.fin` y `b.inicio < a.fin`. Franjas **consecutivas** (una termina 15:00 y la otra empieza 15:00) **no** se superponen.

Casos cubiertos por `tests/Unit/SuperposicionHorariaTest.php`: superposición parcial, franja contenida en otra, franjas idénticas, consecutivas, separadas, distinto día, orden invertido y formato con segundos.

## Supuestos a confirmar con el documento de requerimientos

Las reglas de arriba se interpretaron a partir del plan de tareas. Si el documento original dice otra cosa (por ejemplo, otro tope de deportes), se cambia en un solo lugar: `InscripcionService::MAX_DEPORTES`.
