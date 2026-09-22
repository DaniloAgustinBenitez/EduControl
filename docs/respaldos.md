# Copias de seguridad (RNF-09)

## Generar un respaldo

```bash
php artisan db:respaldar
```

- **Qué hace:** genera `storage/app/respaldos/educacion-AAAAMMDD-HHMMSS.sql` con `mysqldump`.
- **Consistencia:** usa `--single-transaction`, así la copia es coherente sin frenar el sistema.
- **Cuántos guarda:** conserva los últimos 14. Para cambiarlo: `--conservar=30`.
- **Seguridad:** los respaldos **no** se suben al repositorio, porque `storage/app` está en `.gitignore`.

En Laragon, `mysqldump` no está en el PATH, así que la ruta va en el `.env`:

```
MYSQLDUMP_PATH="C:/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysqldump.exe"
MYSQL_PATH="C:/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysql.exe"
```

## Restaurar

```bash
php artisan db:restaurar                      # el respaldo más reciente
php artisan db:restaurar ruta/al/archivo.sql  # uno en particular
```

Pide confirmación porque **reemplaza todos los datos actuales**. Con `--force` no pregunta.

## Respaldo automático

Para respaldar todas las noches, agregá esto en `routes/console.php`:

```php
Schedule::command('db:respaldar')->dailyAt('03:00');
```

y dejá corriendo `php artisan schedule:work`. En producción, usá el *cron* de la plataforma.

## Prueba realizada

El procedimiento se probó completo el 22/09/2026 sobre la base local con 3003 alumnos y 3046 inscripciones a deportes:

1. `php artisan db:respaldar` generó un archivo de 864 KB.
2. Se borraron 3000 alumnos y todas las inscripciones a deportes (quedaron 3 alumnos y 0 inscripciones).
3. `php artisan db:restaurar --force` los restauró: volvieron a quedar 3003 alumnos y 3046 inscripciones.
4. Los acentos y caracteres especiales ("Fútbol", "1° año") se conservaron correctamente (UTF-8).
