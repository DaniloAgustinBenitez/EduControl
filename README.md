# EduControl

[![CI](https://github.com/DaniloAgustinBenitez/EduControl/actions/workflows/ci.yml/badge.svg)](https://github.com/DaniloAgustinBenitez/EduControl/actions/workflows/ci.yml)

Sistema de gestión escolar: configuración institucional, gestión de alumnos, docentes y tutores, autogestión de inscripciones por parte de los padres (cursado, comedor, transporte y deportes) y reportes exportables.

Hecho con **Laravel 13**, **MySQL 8**, **Bootstrap 5** y **DataTables**, con menú lateral, modo claro/oscuro y páginas de error propias. Los estilos del sistema están en `public/css/educontrol.css`.

- **Producción:** https://educontrol-5btq.onrender.com (Render, plan gratis: si nadie la usó en los últimos 15 minutos, la primera carga tarda hasta un minuto). Cómo se publica: [docs/despliegue.md](docs/despliegue.md)
- **Manual de usuario:** [docs/manual-de-usuario.pdf](docs/manual-de-usuario.pdf)
- **Tablero del proyecto:** https://github.com/users/DaniloAgustinBenitez/projects/2

## Perfiles

| Rol | Qué puede hacer |
|---|---|
| Administrador | Todo: configuración institucional, personas y reportes |
| Administrativo | Alumnos, profesores, tutores, asignaciones y reportes |
| Docente | Ver sus materias, cursos y los alumnos de sus cursos |
| Tutor / Padre | Ver a sus hijos e inscribirlos o darlos de baja en los servicios |

### Usuarios de prueba

Todos con contraseña `password`. Existen en producción y en local (se crean con `php artisan db:seed`):

| Rol | Correo |
|---|---|
| Administrador | `admin@educontrol.test` |
| Administrativo | `administrativo@educontrol.test` |
| Docente | `docente@educontrol.test` |
| Tutor | `tutor@educontrol.test` (padre de Sofía y Tomás Pérez) |

## Instalación local (Windows + Laragon)

Requisitos: [Laragon](https://laragon.org) (trae PHP 8.3, MySQL 8 y Composer), Git y VS Code con Intelephense.

1. En Laragon, **Start All**, y después menú → *Tools → Path → Add Laragon to Path*. Reabrí la terminal.
2. En `C:\laragon\bin\php\php-8.3.*\php.ini` verificá que esté activa `extension=zip` (la usa la exportación a Excel).
3. Cloná e instalá:
   ```bash
   git clone https://github.com/DaniloAgustinBenitez/EduControl.git
   cd EduControl
   composer install
   copy .env.example .env
   php artisan key:generate
   ```
4. Creá la base (desde HeidiSQL de Laragon o por consola):
   ```sql
   CREATE DATABASE educacion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
5. Revisá en `.env` los datos `DB_*` (por defecto `root` sin contraseña, como Laragon) y, para los respaldos, la ruta de `MYSQLDUMP_PATH` y `MYSQL_PATH`.
6. Creá las tablas y los datos de prueba, y levantá el servidor:
   ```bash
   php artisan migrate --seed
   php artisan serve
   ```
7. Entrá a http://localhost:8000 con alguno de los usuarios de prueba.

## Comandos útiles

| Comando | Para qué |
|---|---|
| `php artisan test` | Corre los tests (SQLite en memoria, no toca tu base) |
| `php artisan migrate:fresh --seed` | Reinicia la base con los datos de prueba |
| `php artisan db:seed --class=VolumenSeeder` | Agrega 3000 alumnos para pruebas de rendimiento |
| `php artisan reportes:medir` | Mide el tiempo de cada reporte (RNF-07: menos de 3 s) |
| `php artisan db:respaldar` | Copia de seguridad en `storage/app/respaldos` |
| `php artisan db:restaurar` | Restaura el respaldo más reciente |

## Documentación

- [Matriz de trazabilidad: qué requerimiento cumple cada archivo](docs/trazabilidad.md)
- [Modelo de datos y DER](docs/modelo-datos.md) — el DER en formato dbdiagram.io está en [docs/der.dbml](docs/der.dbml)
- [Reglas de negocio e interfaz del servicio de inscripciones](docs/interfaz-inscripciones.md)
- [Despliegue en Render + Aiven](docs/despliegue.md)
- [Copias de seguridad](docs/respaldos.md)
- [Pruebas por rol y de diseño responsivo](docs/pruebas.md)
- [Integración y entrega continua (pipeline de GitHub Actions)](docs/ci-cd.md)
- [Métricas de productividad y mejoras](docs/metricas.md)
- [Unidad 4: contenidos aplicados y no aplicados, y opciones elegidas](docs/unidad-4.md)
- [Calidad del software: FURPS+, SQA/SQC y registro de no conformidades](docs/calidad.md)
- [Convenciones de trabajo: ramas, commits y PR](CONTRIBUTING.md)
- [Correcciones a aplicar en el Plan de Trabajo](docs/correcciones-plan.md)

## Estructura principal

```
.github/                Pipeline de CI, Dependabot, CODEOWNERS y plantillas de PR e issues
app/
  Http/Controllers/     ABM, autogestión, docente y reportes
  Services/             InscripcionService (reglas RN), SuperposicionHoraria, ReporteService, CuentaService
  Policies/             AlumnoPolicy (RN-07: el tutor solo opera sobre sus hijos)
  Support/Rol.php       Los cuatro roles y sus permisos
database/
  migrations/           Esquema completo
  seeders/              Roles, datos institucionales, usuarios de prueba y volumen
resources/views/        Vistas Blade (layout con Bootstrap 5 + DataTables)
tests/                  Tests unitarios y de integración
```

## Licencia

MIT. Ver [LICENSE](LICENSE).
