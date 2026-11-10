// Cliente HTTP de la app: agrega el token, corta a los 70 s y traduce los errores de la API.
import type { CuerpoError } from './tipos';

const URL_PRODUCCION = 'https://educontrol-5btq.onrender.com/api/v1';

/** URL base de la API, sin barra final (por ejemplo https://.../api/v1). */
export const URL_API = (process.env.EXPO_PUBLIC_API_URL || URL_PRODUCCION).replace(/\/+$/, '');

/** URL del servidor sin /api/v1, para el chequeo /up de Laravel. */
export const URL_SERVIDOR = URL_API.replace(/\/api\/v\d+$/, '');

/** Render gratis puede tardar hasta un minuto en despertar (riesgo R2). */
export const TIEMPO_MAXIMO_MS = 70_000;

/** A partir de este tiempo se avisa que el servidor se está despertando (RNF-13). */
export const UMBRAL_LENTO_MS = 4_000;

export const MENSAJE_SIN_CONEXION =
  'No se pudo conectar con el servidor. Revisá tu conexión a internet y volvé a intentar.';
export const MENSAJE_TIEMPO_AGOTADO =
  'El servidor tardó demasiado en responder. Revisá tu conexión y volvé a intentar.';

const MENSAJES_POR_CODIGO: Record<number, string> = {
  401: 'Tu sesión venció. Volvé a ingresar.',
  403: 'Tu perfil no tiene acceso a esta sección.',
  404: 'No se encontró lo que buscabas.',
  422: 'Revisá los datos ingresados.',
  429: 'Demasiados intentos. Esperá un minuto y volvé a intentar.',
};

/** Error de la API con el mensaje listo para mostrar. status 0 = sin conexión o tiempo agotado. */
export class ErrorApi extends Error {
  readonly status: number;
  readonly regla: string | null;
  readonly errors: Record<string, string[]>;

  constructor(
    status: number,
    message: string,
    regla: string | null = null,
    errors: Record<string, string[]> = {},
  ) {
    super(message);
    this.name = 'ErrorApi';
    this.status = status;
    this.regla = regla;
    this.errors = errors;
  }

  /** Primer error de validación de un campo, si lo hay. */
  errorDe(campo: string): string | undefined {
    return this.errors[campo]?.[0];
  }
}

/** Devuelve un mensaje para mostrar a partir de cualquier error. */
export function mensajeDeError(error: unknown): string {
  if (error instanceof ErrorApi) return error.message;
  return 'Ocurrió un error inesperado. Volvé a intentar.';
}

// ---------- Estado del cliente: token y aviso de cierre de sesión ----------

let token: string | null = null;
let alNoAutorizado: (() => void) | null = null;

export function establecerToken(nuevo: string | null): void {
  token = nuevo;
}

/** Se llama cuando la API responde 401 (token vencido o revocado). */
export function alRecibirNoAutorizado(funcion: (() => void) | null): void {
  alNoAutorizado = funcion;
}

// ---------- Pedidos lentos (servidor dormido) ----------

let pedidosLentos = 0;
const oyentesLentitud = new Set<(lento: boolean) => void>();

function cambiarPedidosLentos(delta: number): void {
  const antes = pedidosLentos > 0;
  pedidosLentos = Math.max(0, pedidosLentos + delta);
  const ahora = pedidosLentos > 0;
  if (antes !== ahora) oyentesLentitud.forEach((oyente) => oyente(ahora));
}

/** Suscribe a los cambios de "hay algún pedido tardando más de 4 s". Devuelve la desuscripción. */
export function suscribirLentitud(oyente: (lento: boolean) => void): () => void {
  oyentesLentitud.add(oyente);
  return () => {
    oyentesLentitud.delete(oyente);
  };
}

export function hayPedidosLentos(): boolean {
  return pedidosLentos > 0;
}

// ---------- Pedido genérico ----------

type Metodo = 'GET' | 'POST' | 'DELETE';

export async function pedir<T>(metodo: Metodo, ruta: string, cuerpo?: unknown): Promise<T> {
  const encabezados: Record<string, string> = { Accept: 'application/json' };
  if (cuerpo !== undefined) encabezados['Content-Type'] = 'application/json';
  if (token) encabezados.Authorization = `Bearer ${token}`;

  const controlador = new AbortController();
  let fueLento = false;
  const temporizadorCorte = setTimeout(() => controlador.abort(), TIEMPO_MAXIMO_MS);
  const temporizadorLento = setTimeout(() => {
    fueLento = true;
    cambiarPedidosLentos(1);
  }, UMBRAL_LENTO_MS);

  let respuesta: Response;
  try {
    respuesta = await fetch(`${URL_API}${ruta}`, {
      method: metodo,
      headers: encabezados,
      body: cuerpo === undefined ? undefined : JSON.stringify(cuerpo),
      signal: controlador.signal,
    });
  } catch {
    throw new ErrorApi(
      0,
      controlador.signal.aborted ? MENSAJE_TIEMPO_AGOTADO : MENSAJE_SIN_CONEXION,
    );
  } finally {
    clearTimeout(temporizadorCorte);
    clearTimeout(temporizadorLento);
    if (fueLento) cambiarPedidosLentos(-1);
  }

  const datos = await leerJson(respuesta);

  if (!respuesta.ok) {
    const error = datos as CuerpoError | null;
    if (respuesta.status === 401) alNoAutorizado?.();
    throw new ErrorApi(
      respuesta.status,
      error?.message || mensajePorCodigo(respuesta.status),
      error?.regla ?? null,
      error?.errors ?? {},
    );
  }

  return datos as T;
}

async function leerJson(respuesta: Response): Promise<unknown> {
  if (respuesta.status === 204) return null;
  try {
    return await respuesta.json();
  } catch {
    return null;
  }
}

function mensajePorCodigo(status: number): string {
  if (MENSAJES_POR_CODIGO[status]) return MENSAJES_POR_CODIGO[status];
  if (status >= 500) return 'El servidor tuvo un problema. Probá de nuevo en unos minutos.';
  return 'Ocurrió un error inesperado. Volvé a intentar.';
}
