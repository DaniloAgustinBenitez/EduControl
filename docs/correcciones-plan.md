# Correcciones al Plan de Trabajo

Cambios a aplicar en `PlanTrabajo_Completo.pdf` para que el documento describa lo que el sistema realmente hace. Cada bloque se puede pegar tal cual en el documento.

## Sección 9.5 — Conectores de la arquitectura

El plan indica que el intercambio de datos se hace con **API REST en formato JSON**. Desde el trabajo mobile ([plan-mobile.md](plan-mobile.md)) eso es cierto, pero solo para la app: la web sigue armando las pantallas en el servidor con Blade. Conviene que el documento distinga los dos clientes.

**Texto propuesto para reemplazar la tabla de la sección 9.5:**

| Conector | Tecnología | Función |
|---|---|---|
| Navegador ↔ Servidor de aplicación | Protocolo HTTPS (formularios HTML y respuestas renderizadas con Blade) | Transporta las solicitudes del navegador al servidor y devuelve la página ya construida, sobre canal cifrado. |
| App mobile ↔ Servidor de aplicación | API REST en JSON (`/api/v1`) sobre HTTPS, autenticada con tokens de Laravel Sanctum | Intercambia datos con la app del tutor y del docente. Usa las mismas reglas de negocio que la web ([api.md](api.md)). |
| Validación en el cliente | HTML5 + JavaScript en la web; TypeScript en la app | Verifica los datos obligatorios y su formato antes de enviarlos, reduciendo viajes innecesarios al servidor. |
| Servidor de aplicación ↔ Base de datos | Eloquent ORM sobre PDO, con consultas parametrizadas | Ejecuta las operaciones CRUD evitando la inyección de código SQL. |

## Sección 10.2 — Herramientas por área

La fila de **Postman** puede quedar, porque ahora hay una API que probar. Se usó [Bruno](https://www.usebruno.com), una alternativa libre cuya colección se guarda en el repositorio ([api/](api/)). Agregar además:

| Área | Herramienta | Justificación |
|---|---|---|
| Pruebas automatizadas | PHPUnit (incluido en Laravel) | Tests que verifican las 7 reglas de negocio, el acceso por rol, los reportes y la API antes de cada entrega. |
| Pruebas de la API | Bruno | Colección versionada con un pedido por endpoint, contra local y producción. |
| App mobile | Expo (React Native) + Jest | La app se prueba en el celular con Expo Go y sus pantallas con tests automáticos. |

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
