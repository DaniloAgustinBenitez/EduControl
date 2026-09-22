# Correcciones al Plan de Trabajo

Cambios a aplicar en `PlanTrabajo_Completo.pdf` para que el documento describa lo que el sistema realmente hace. Cada bloque se puede pegar tal cual en el documento.

## Sección 9.5 — Conectores de la arquitectura

El plan indica que el intercambio de datos se hace con **API REST en formato JSON**. El sistema no expone una API: las pantallas se arman en el servidor con Blade y el navegador envía formularios HTML sobre HTTPS. Es una decisión válida para un sistema de gestión interno (menos código, menos superficie de ataque), pero conviene que el documento lo refleje.

**Texto propuesto para reemplazar la tabla de la sección 9.5:**

| Conector | Tecnología | Función |
|---|---|---|
| Cliente ↔ Servidor de aplicación | Protocolo HTTPS (formularios HTML y respuestas renderizadas con Blade) | Transporta las solicitudes del navegador al servidor y devuelve la página ya construida, sobre canal cifrado. |
| Validación en el cliente | HTML5 + JavaScript | Verifica los datos obligatorios y su formato antes de enviarlos, reduciendo viajes innecesarios al servidor. |
| Servidor de aplicación ↔ Base de datos | Eloquent ORM sobre PDO, con consultas parametrizadas | Ejecuta las operaciones CRUD evitando la inyección de código SQL. |

## Sección 10.2 — Herramientas por área

Quitar la fila de **Postman**, porque no hay API que probar, y reemplazarla por:

| Área | Herramienta | Justificación |
|---|---|---|
| Pruebas automatizadas | PHPUnit (incluido en Laravel) | 50 tests que verifican las 7 reglas de negocio, el acceso por rol y los reportes antes de cada entrega. |

## Sección 10.1 — Decisión de stack

Dejar asentado que se eligió la **Opción A (Laravel)**, con Railway como entorno de producción.

## Sección 12 — Gestión de proyecto

El plan menciona Jira como herramienta principal. Se usó **GitHub Projects**, que el propio documento admite como alternativa, por integrarse con el repositorio: cada tarea es un issue vinculado a su RF/RN y se cierra sola al mergear el pull request.

## Sección 11 — Maquetación

El documento afirma que se elaboraron wireframes. Si el profesor los pide, hay dos caminos:

1. Adjuntar los wireframes que ya figuran en el PDF (pantalla de ingreso y autogestión) como anexo, aclarando que el sistema final los respeta.
2. Reemplazarlos por capturas del sistema terminado, que cumplen la misma función de mostrar la disposición de los elementos.

## Nombre del cuarto rol

El plan llama **"padre"** al cuarto perfil (RF-04). El sistema usa internamente `tutor` y lo muestra como **"Tutor / Padre"**, porque el vínculo puede ser de la madre o de un tutor legal. Si se prefiere unificar con el documento, se cambia la etiqueta en `app/Support/Rol.php`.
