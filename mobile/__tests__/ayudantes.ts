// Ayudantes para simular la API con fetch y datos de ejemplo con la forma de docs/api.md.
import { URL_API } from '../src/api/cliente';
import type {
  AlumnoDetalle,
  AlumnoResumen,
  Deporte,
  Estado,
  RespuestaCatalogo,
  Usuario,
} from '../src/api/tipos';

/** Respuesta de fetch con un status y un cuerpo JSON (sin cuerpo si es undefined). */
export function respuesta(status: number, cuerpo?: unknown): Response {
  return {
    ok: status >= 200 && status < 300,
    status,
    json: async () => {
      if (cuerpo === undefined) throw new SyntaxError('Sin cuerpo');
      return cuerpo;
    },
  } as Response;
}

type Respuestas = Record<string, Response | Response[] | (() => Promise<Response>)>;

/**
 * Simula la API: cada clave es "MÉTODO /ruta" (por ejemplo "GET /hijos").
 * Un arreglo devuelve una respuesta por llamada, en orden. Devuelve el mock de fetch.
 */
export function simularApi(respuestas: Respuestas): jest.Mock {
  const mock = jest.fn(async (url: string, opciones: RequestInit = {}) => {
    const clave = `${opciones.method ?? 'GET'} ${String(url).replace(URL_API, '')}`;
    const valor = respuestas[clave];
    if (!valor) throw new Error(`Pedido no simulado: ${clave}`);
    if (typeof valor === 'function') return valor();
    if (Array.isArray(valor)) {
      const siguiente = valor.shift();
      if (!siguiente) throw new Error(`Sin más respuestas para ${clave}`);
      return siguiente;
    }
    return valor;
  });
  globalThis.fetch = mock as unknown as typeof fetch;
  return mock;
}

/** Pedidos hechos a la API como "MÉTODO /ruta". */
export function pedidosHechos(mock: jest.Mock): string[] {
  return mock.mock.calls.map(
    ([url, opciones]: [string, RequestInit | undefined]) =>
      `${opciones?.method ?? 'GET'} ${String(url).replace(URL_API, '')}`,
  );
}

/** Promesa que se resuelve a mano, para probar estados de "enviando". */
export function pendiente<T>() {
  let resolver!: (valor: T) => void;
  const promesa = new Promise<T>((r) => {
    resolver = r;
  });
  return { promesa, resolver };
}

// ---------- Datos de ejemplo ----------

export const usuarioTutor: Usuario = {
  id: 4,
  nombre: 'Laura Pérez',
  email: 'tutor@educontrol.test',
  rol: 'tutor',
  rol_etiqueta: 'Tutor / Padre',
  permisos: ['inscripciones.autogestion'],
  usa_app: true,
};

export const usuarioDocente: Usuario = {
  id: 3,
  nombre: 'Marta Gómez',
  email: 'docente@educontrol.test',
  rol: 'docente',
  rol_etiqueta: 'Docente',
  permisos: ['docente.panel'],
  usa_app: true,
};

export const usuarioAdministrador: Usuario = {
  id: 1,
  nombre: 'Ana Admin',
  email: 'admin@educontrol.test',
  rol: 'administrador',
  rol_etiqueta: 'Administrador',
  permisos: [],
  usa_app: false,
};

export const curso3A = {
  id: 5,
  nombre: '3° A',
  nivel: 'Primario',
  nombre_completo: '3° A — Primario',
};

const horario = (dia: number, diaNombre: string, inicio: string, fin: string) => ({
  dia,
  dia_nombre: diaNombre,
  inicio,
  fin,
  descripcion: `${diaNombre} ${inicio} a ${fin}`,
});

export const futbol: Deporte = {
  id: 2,
  nombre: 'Fútbol',
  descripcion: 'Entrenamiento mixto',
  horarios: [horario(2, 'Martes', '17:00', '18:30')],
};

export const basquet: Deporte = {
  id: 3,
  nombre: 'Básquet',
  descripcion: null,
  horarios: [horario(2, 'Martes', '17:30', '19:00')],
};

export const voley: Deporte = {
  id: 4,
  nombre: 'Vóley',
  descripcion: null,
  horarios: [horario(4, 'Jueves', '17:00', '18:00')],
};

export const recorridoNorte = {
  id: 1,
  nombre: 'Recorrido Norte',
  descripcion: 'Barrios del norte',
};
export const recorridoSur = { id: 2, nombre: 'Recorrido Sur', descripcion: null };

export const estadoVacio: Estado = { cursado: null, comedor: null, transporte: null, deportes: [] };

export const ciclo2026 = { id: 1, anio: 2026 };

export function alumno(cambios: Partial<AlumnoResumen> = {}): AlumnoResumen {
  return {
    id: 1,
    legajo: 'A-0001',
    nombre: 'Sofía',
    apellido: 'Pérez',
    nombre_completo: 'Pérez, Sofía',
    curso: curso3A,
    estado: estadoVacio,
    ...cambios,
  };
}

export function alumnoDetalle(cambios: Partial<AlumnoDetalle> = {}): AlumnoDetalle {
  return {
    ...alumno(),
    horarios_cursado: [horario(1, 'Lunes', '08:00', '12:00')],
    ...cambios,
  };
}

export const catalogo: RespuestaCatalogo = {
  ciclo: ciclo2026,
  max_deportes: 2,
  deportes: [basquet, futbol, voley],
  recorridos: [recorridoNorte, recorridoSur],
};
