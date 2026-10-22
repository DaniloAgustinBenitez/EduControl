# Convenciones de trabajo

## Ramas

- `main`: siempre estable y desplegable. **No se commitea directo**: todo entra por pull request.
- `Nilo`: rama de trabajo personal.
- Una rama por requerimiento o tarea, creada desde `main`:

| Tipo | Formato | Ejemplo |
|---|---|---|
| Requerimiento funcional | `feature/rf-XX-descripcion` | `feature/rf-09-inscripcion-deportes` |
| Corrección | `fix/descripcion` | `fix/superposicion-horaria-medianoche` |
| Documentación | `docs/descripcion` | `docs/manual-de-usuario` |

```bash
git switch main && git pull
git switch -c feature/rf-09-inscripcion-deportes
```

## Commits

Mensajes en español, en imperativo, con el formato `tipo(alcance): descripción`:

```
feat(inscripciones): validar tope de dos deportes (RN-03)
fix(reportes): exportar a Excel con acentos correctos
docs(readme): agregar usuarios de prueba
test(horarios): casos de franjas consecutivas
refactor(alumnos): mover validación a InscripcionService
```

Tipos: `feat`, `fix`, `docs`, `test`, `refactor`, `chore`. Cuando aplique, citar el RF/RN/HU entre paréntesis y cerrar el issue en el cuerpo con `Closes #N`.

## Pull requests

1. Antes de abrir el PR: `php artisan test` y `vendor/bin/pint --test` en verde.
2. Título igual al commit principal. Completar la plantilla (qué cambia, cómo probarlo, issues que cierra).
3. **CI en verde:** el pipeline de GitHub Actions ([docs/ci-cd.md](docs/ci-cd.md)) tiene que pasar. Con la cruz roja no se mergea.
4. **Revisión cruzada:** el otro integrante revisa y aprueba antes de mergear. Si se trabaja solo, se revisa el diff completo en GitHub antes de mergear.
5. Se mergea con *Squash and merge* y se borra la rama.
6. **PR chicos:** un PR por issue o por grupo chico de issues relacionados, no uno por etapa completa. Así el CI revisa cada cambio por separado y la revisión es más fácil.

## Tablero

Cada tarea es un issue en el [tablero](https://github.com/users/DaniloAgustinBenitez/projects/2):
**Por hacer → En progreso → En revisión → Hecho**. Al abrir el PR la tarjeta pasa a *En revisión*; al mergear, el `Closes #N` la cierra sola.
