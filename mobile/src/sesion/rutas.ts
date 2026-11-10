// Pantalla inicial según el rol del usuario (M2.6).
import type { Usuario } from '../api/tipos';

export type RutaInicial = '/login' | '/hijos' | '/cursos' | '/usar-web';

export function rutaInicial(usuario: Usuario | null): RutaInicial {
  if (!usuario) return '/login';
  if (usuario.usa_app && usuario.rol === 'tutor') return '/hijos';
  if (usuario.usa_app && usuario.rol === 'docente') return '/cursos';
  return '/usar-web';
}
