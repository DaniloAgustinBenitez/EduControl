# EduControl Mobile

App para celulares de EduControl, pensada para los dos perfiles que usan el sistema fuera de la oficina:

| Perfil | Qué hace en la app |
|---|---|
| **Tutor / Padre** | Ve a sus hijos y su estado del ciclo lectivo, los inscribe o da de baja en cursado, comedor, transporte y deportes |
| **Docente** | Ve sus cursos, materias y horarios, y la nómina de alumnos de cada curso |

La configuración institucional, la gestión de personas y los reportes **siguen en la web**: son tareas de escritorio, con tablas grandes y exportaciones a PDF y Excel. Si un administrador o administrativo entra a la app, se le indica que use la web.

> **Estado (10/11/2026):** app y API terminadas y probadas con tests (69 de la app, 39 de la API) y contra un servidor Laravel real. Falta la prueba en celulares reales con Expo Go y generar el APK. El avance tarea por tarea está en [docs/plan-mobile.md](../docs/plan-mobile.md#12-estado-de-avance).

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
| Interfaz | Mismo diseño que la web (paleta de `educontrol.css`, tarjetas, insignias); íconos de `@expo/vector-icons` (MaterialCommunityIcons) equivalentes a los Bootstrap Icons |
| Pruebas | Jest + React Native Testing Library |
| Estilo de código | ESLint (config de Expo) + Prettier |
| Builds | Expo Go para desarrollo; EAS Build para el APK de Android |

## Estructura

```
mobile/
  app/                          Pantallas (Expo Router: cada archivo es una ruta)
    _layout.tsx                 Proveedores de tema y sesión, StatusBar según el tema y pedido a /up para despertar al servidor
    index.tsx                   Espera la sesión guardada y redirige según el rol
    login.tsx                   Ingreso con correo y contraseña
    usar-web.tsx                Aviso para administrador y administrativo: "Usá la versión web"
    (tutor)/
      _layout.tsx               Solo deja entrar al tutor; barra superior con logo, ciclo lectivo, tema y salir
      hijos/index.tsx           Mis hijos: tarjeta por hijo con el estado de los 4 servicios
      hijos/[id].tsx            Detalle: inscribir y dar de baja cursado, comedor, transporte y deportes
    (docente)/
      _layout.tsx               Solo deja entrar al docente; barra superior con logo, tema y salir
      cursos/index.tsx          Mis cursos: nivel, horarios y materias
      cursos/[id].tsx           Horarios, materias y nómina con buscador
  src/
    api/
      cliente.ts                fetch con token, timeout de 70 s, errores tipados (ErrorApi) y aviso de servidor lento
      tipos.ts                  Tipos de los objetos de docs/api.md
      autenticacion.ts          login, logout, perfil
      tutor.ts                  hijos, catálogo, inscribir y dar de baja
      docente.ts                cursos del docente y detalle de un curso
      servidor.ts               GET /up al abrir la app
    sesion/
      SesionContext.tsx         Token y usuario: ingreso, cierre y restauración validada con /perfil
      almacen.ts                Guardado cifrado con expo-secure-store
      rutas.ts                  Pantalla inicial según el rol
    componentes/
      BarraSuperior.tsx         Barra como .ec-barra de la web (logo, insignia del ciclo, BotonTema, BotonCerrarSesion)
      Icono.tsx                 Íconos MaterialCommunityIcons y los de cada servicio
      Boton, Tarjeta, Aviso, Encabezado, Etiqueta, OpcionRadio, Logo, Cargando, EstadoVacio, Horarios, ListaMaterias
    hooks/                      useCarga (carga y "deslizar para actualizar") y useServidorLento
    tema/
      colores.ts                Colores de public/css/educontrol.css (claro y oscuro), useTema y useColores
      TemaContext.tsx           Tema elegido por el usuario: arranca en claro, como la web
      preferencia.ts            Guarda el tema (SecureStore en el celular; localStorage "ec-tema" en el navegador)
    utilidades/
      nomina.ts                 Orden y búsqueda de la nómina
      confirmar.ts              Confirmación de bajas: Alert en el celular, window.confirm en el navegador
      navegacion.ts             "Volver": pantalla anterior o, si no hay historial, la lista
  __tests__/                    Tests de Jest con la API simulada (fetch mockeado)
  app.json                      Configuración de Expo (nombre, ícono, colores, paquete com.educontrol.app)
  .env.example                  EXPO_PUBLIC_API_URL
```

La app **no tiene reglas de negocio**: si la API rechaza una inscripción (por ejemplo un `422` con `regla: "RN-04"`), muestra el `message` tal cual en la sección del servicio. Si un pedido tarda más de 4 segundos, avisa "Despertando el servidor, puede tardar hasta un minuto…" (RNF-13), y un `401` cierra la sesión y vuelve al login.

El tema arranca en **claro**, igual que la web, y se cambia con el botón de luna/sol de la barra superior; la elección queda guardada.

## Probar en el celular (la forma más fácil)

En Windows, **doble clic en [`probar-en-celular.cmd`](probar-en-celular.cmd)**. El script:

1. Detecta la IP de la PC en el Wi-Fi.
2. Prepara una base SQLite de prueba (`database/demo-mobile.sqlite`) con los usuarios y datos de siempre. No toca la base MySQL de Laragon.
3. Abre la API en una ventana aparte, *EduControl API*, escuchando en la red.
4. Arranca Expo apuntando a esa API y muestra el QR.

Después, en el celular:

1. Instalá o actualizá **Expo Go** desde Play Store o App Store. Tiene que ser la versión que soporta el SDK 58.
2. Conectá el celular al **mismo Wi-Fi** que la PC.
3. Android: en Expo Go tocá *Scan QR code*. iPhone: escaneá el QR con la cámara.
4. Si el QR no anda: en Expo Go elegí *Enter URL manually* y escribí la dirección `exp://<IP>:8081` que muestra el script.
5. Ingresá con `tutor@educontrol.test` o `docente@educontrol.test` (contraseña `password`). La checklist de qué probar está en [docs/pruebas.md](../docs/pruebas.md#checklist-en-celulares-m54).

Opciones, desde una terminal en `mobile/`:

| Comando | Qué hace |
|---|---|
| `.\probar-en-celular.cmd -Reiniciar` | Igual, pero deja los datos de prueba como nuevos |
| `.\probar-en-celular.cmd -Web` | Abre la app en el navegador de la PC |
| `.\probar-en-celular.cmd -Produccion` | Usa la API publicada en Render y no levanta Laravel |

Para terminar: `Ctrl+C` en la ventana de Expo y cerrar la ventana *EduControl API*. Si el celular no conecta, revisá que los dos estén en la misma red. Algunas redes de invitados o de oficinas aíslan a los dispositivos entre sí.

## Cómo se corre (a mano)

Requisitos: Node 22.13 o superior y la app **Expo Go** en el celular (Android o iOS).

```bash
cd mobile
npm install
copy .env.example .env      # y completar EXPO_PUBLIC_API_URL
npx expo start              # escanear el QR con Expo Go
```

Para `EXPO_PUBLIC_API_URL` hay dos opciones:

- **Contra producción:** `https://educontrol-5btq.onrender.com/api/v1`. Es lo más simple, pero la primera llamada puede tardar hasta un minuto si Render estaba dormido. La API existe en producción recién cuando estos cambios llegan a `main` y Render los despliega.
- **Contra el Laravel local:** levantar `php artisan serve --host=0.0.0.0` y usar la IP de la PC en la red (por ejemplo `http://192.168.0.10:8000/api/v1`). El celular y la PC tienen que estar en el mismo Wi-Fi, y el firewall de Windows tiene que permitir el puerto 8000.

Si no hay `.env`, la app usa la URL de producción. Después de cambiar el `.env` hay que reiniciar `npx expo start`.

Los usuarios de prueba son los mismos de la web: `tutor@educontrol.test` y `docente@educontrol.test`, con contraseña `password`.

### Calidad

| Comando | Qué hace |
|---|---|
| `npm run lint` | ESLint (config de Expo + Prettier), sin advertencias permitidas |
| `npm run typecheck` | TypeScript sin generar archivos (`tsc --noEmit`) |
| `npm test` | Tests de Jest y React Native Testing Library |
| `npm run test:ci` | Tests con cobertura; el reporte `lcov` queda en `mobile/coverage/` |
| `npm run format` | Formatea el código con Prettier |
| `npx expo export --platform android` | Compila el bundle de Android, para comprobar que la app arma sin errores |
