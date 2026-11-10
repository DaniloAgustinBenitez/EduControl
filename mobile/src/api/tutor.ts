// Endpoints del tutor: hijos, catálogo, inscripciones y bajas.
import { pedir } from './cliente';
import type {
  RespuestaCatalogo,
  RespuestaHijo,
  RespuestaHijos,
  RespuestaInscripcion,
  Servicio,
} from './tipos';

export function obtenerHijos() {
  return pedir<RespuestaHijos>('GET', '/hijos');
}

export function obtenerHijo(alumnoId: number) {
  return pedir<RespuestaHijo>('GET', `/hijos/${alumnoId}`);
}

export function obtenerCatalogo() {
  return pedir<RespuestaCatalogo>('GET', '/catalogo');
}

/** Cuerpo del POST según el servicio (ver docs/api.md). */
export type DatosInscripcion = { recorrido_id: number } | { deporte_id: number } | undefined;

export function inscribir(alumnoId: number, servicio: Servicio, datos?: DatosInscripcion) {
  return pedir<RespuestaInscripcion>(
    'POST',
    `/hijos/${alumnoId}/inscripciones/${servicio}`,
    datos ?? {},
  );
}

export function darDeBaja(alumnoId: number, servicio: Servicio, inscripcionId: number) {
  return pedir<RespuestaInscripcion>(
    'DELETE',
    `/hijos/${alumnoId}/inscripciones/${servicio}/${inscripcionId}`,
  );
}
