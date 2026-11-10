# EduControl Mobile

App para celulares de EduControl, pensada para los dos perfiles que usan el sistema fuera de la oficina:

| Perfil | Qué hace en la app |
|---|---|
| **Tutor / Padre** | Ve a sus hijos y su estado del ciclo lectivo, los inscribe o da de baja en cursado, comedor, transporte y deportes |
| **Docente** | Ve sus cursos, materias y horarios, y la nómina de alumnos de cada curso |

La configuración institucional, la gestión de personas y los reportes **siguen en la web**: son tareas de escritorio, con tablas grandes y exportaciones a PDF y Excel. Si un administrador o administrativo entra a la app, se le indica que use la web.

> **Estado:** en planificación. El plan de trabajo completo, con etapas, tareas y cronograma, está en [docs/plan-mobile.md](../docs/plan-mobile.md).

## Cómo funciona

```
 Celular (app Expo)                       Servidor Laravel (Render)                 MySQL (Aiven)
┌────────────────────┐   HTTPS + JSON    ┌───────────────────────────────┐
│ Pantallas React    │ ───────────────▶  │ routes/api.php  (/api/v1/...) │
│ Native             │   Bearer token    │ auth:sanctum + permission:*   │
│ Token en           │ ◀───────────────  │ AlumnoPolicy (RN-07)          │ ───▶  misma base
│ SecureStore        │                   │ InscripcionService (RN-01..06)│       que la web
└────────────────────┘                   └───────────────────────────────┘
```

- La app **no tiene reglas de negocio**: le pide todo a la API, que reusa el mismo `InscripcionService` y la misma `AlumnoPolicy` que la web. Una inscripción que la web rechaza, la app también la rechaza, con el mismo mensaje.
- El ingreso usa **Laravel Sanctum** con tokens personales. El token se guarda cifrado en el celular con `expo-secure-store` y se borra al cerrar sesión.
- Web y app comparten la base de datos: lo que el padre hace desde el celular aparece al instante en los reportes de la web.

## Stack

| Parte | Tecnología |
|---|---|
| Framework | [Expo](https://expo.dev) (React Native) con TypeScript |
| Navegación | Expo Router (rutas por archivos, igual que las carpetas de vistas de Laravel) |
| Sesión | `expo-secure-store` para el token |
| Llamadas a la API | `fetch` con un cliente propio (`src/api/cliente.ts`) que agrega el token y traduce los errores |
| Pruebas | Jest + React Native Testing Library |
| Estilo de código | ESLint (config de Expo) + Prettier |
| Builds | Expo Go para desarrollo; EAS Build para el APK de Android |

## Estructura prevista

```
mobile/
  app/                      Pantallas (Expo Router)
    _layout.tsx             Proveedor de sesión y redirección según el rol
    login.tsx
    (tutor)/
      index.tsx             Mis hijos, con el estado de cada uno
      alumno/[id].tsx       Detalle: inscribir y dar de baja servicios
    (docente)/
      index.tsx             Mis cursos y materias
      curso/[id].tsx        Horarios y nómina del curso
  src/
    api/                    Cliente HTTP y una función por endpoint
    sesion/                 Contexto de sesión (token, usuario, rol)
    componentes/            Botones, tarjetas, avisos y estados de carga
  __tests__/                Tests de Jest
  app.json                  Configuración de Expo (nombre, ícono, colores)
  .env.example              EXPO_PUBLIC_API_URL
```

## Cómo se va a correr (cuando exista la etapa M2)

Requisitos: Node 20 o superior y la app **Expo Go** en el celular (Android o iOS).

```bash
cd mobile
npm install
copy .env.example .env      # y completar EXPO_PUBLIC_API_URL
npx expo start              # escanear el QR con Expo Go
```

Para `EXPO_PUBLIC_API_URL` hay dos opciones:

- **Contra producción:** `https://educontrol-5btq.onrender.com/api/v1`. Es lo más simple, pero la primera llamada puede tardar hasta un minuto si Render estaba dormido.
- **Contra el Laravel local:** levantar `php artisan serve --host=0.0.0.0` y usar la IP de la PC en la red (por ejemplo `http://192.168.0.10:8000/api/v1`). El celular y la PC tienen que estar en el mismo Wi-Fi, y el firewall de Windows tiene que permitir el puerto 8000.

Los usuarios de prueba son los mismos de la web: `tutor@educontrol.test` y `docente@educontrol.test`, con contraseña `password`.
