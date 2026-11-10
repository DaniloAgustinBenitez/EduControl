// Endpoints del docente: sus cursos y el detalle de cada uno con la nómina.
import { pedir } from './cliente';
import type { RespuestaCursoDocente, RespuestaCursosDocente } from './tipos';

export function obtenerCursosDocente() {
  return pedir<RespuestaCursosDocente>('GET', '/docente/cursos');
}

export function obtenerCursoDocente(cursoId: number) {
  return pedir<RespuestaCursoDocente>('GET', `/docente/cursos/${cursoId}`);
}
