# Reglas de trabajo del proyecto

Estas reglas aplican a **todas** las tareas que se realicen en este repositorio con Claude Code. Deben seguirse siempre, sin excepción, salvo que el usuario indique explícitamente lo contrario en una tarea puntual.

## 1. Saludo inicial
- Al comenzar a trabajar en cualquier tarea, la primera palabra de la respuesta debe ser: **"Danilo"**.
- Después del saludo, continuar normalmente con el análisis o la explicación de la tarea.

## 2. Verificar que el proyecto esté actualizado
- Antes de empezar a modificar cualquier código, ejecutar los comandos necesarios para comprobar el estado del repositorio remoto (por ejemplo `git fetch`, `git status`, `git log origin/<rama> -1`).
- Verificar si hay commits nuevos de otros compañeros que aún no estén en la copia local.
- Si hay cambios remotos que no están local:
  - Avisar explícitamente que existen actualizaciones pendientes.
  - Antes de bajarlas (`git pull` / `merge`), pedir confirmación al usuario.
- Si el proyecto está actualizado, indicarlo brevemente ("El repo está actualizado, no hay cambios de compañeros pendientes") y continuar.

## 3. Explicar antes de modificar
- Antes de tocar cualquier archivo de código, presentar una explicación breve y clara de:
  - **Qué** se va a hacer (qué archivos, qué funciones o componentes se van a crear/modificar).
  - **Por qué** se va a hacer así (la razón técnica o la relación con lo pedido).
- Solo después de esa explicación, proceder con los cambios en el código.
- Si la tarea es grande o ambigua, se puede resumir el plan en pasos numerados antes de ejecutar.

## 4. Al finalizar la tarea
- Una vez terminada y probada la tarea (código funcionando, sin errores evidentes), preguntar explícitamente:
  > "¿Querés que suba estos cambios al repositorio?"
- No subir cambios (`git add`, `commit`, `push`) sin haber recibido esta confirmación.

## 5. Si se acepta subir los cambios
- Realizar `git add`, `commit` (con un mensaje de commit claro y descriptivo del cambio) y `push` a la rama correspondiente.
- Una vez subido, compartir el **link del repositorio/commit/PR** en GitHub (o la plataforma que se use) para que el usuario pueda verlo.

## 6. Si se rechaza subir los cambios
- Dejar los cambios en el working directory sin commitear.
- No insistir; simplemente confirmar que los cambios quedaron guardados localmente sin subir.

---

### Resumen del flujo por tarea
1. Responder empezando con "Danilo".
2. Verificar que el repo esté actualizado (chequear cambios de compañeros).
3. Explicar qué se va a hacer y por qué, antes de tocar código.
4. Hacer los cambios.
5. Preguntar si se sube al repositorio.
6. Si dice que sí → commit + push → devolver el link.
7. Si dice que no → dejar los cambios locales sin subir.

---

### Datos técnicos del proyecto
- Laravel 13 + MySQL 8, entorno local con Laragon (PHP en `C:\laragon\bin\php\...`, puede no estar en el PATH).
- Tests: `php artisan test` (usan SQLite en memoria, no tocan la base local).
- Reglas de negocio en `app/Services/InscripcionService.php` y `app/Services/SuperposicionHoraria.php`.
- Convenciones de ramas y commits: ver `CONTRIBUTING.md`.
