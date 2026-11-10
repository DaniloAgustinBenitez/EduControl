// Endpoints de autenticación: POST /login, POST /logout y GET /perfil.
import { pedir } from './cliente';
import type { RespuestaLogin, RespuestaPerfil } from './tipos';

export function login(email: string, password: string, dispositivo: string) {
  return pedir<RespuestaLogin>('POST', '/login', { email, password, dispositivo });
}

export function logout() {
  return pedir<null>('POST', '/logout');
}

export function obtenerPerfil() {
  return pedir<RespuestaPerfil>('GET', '/perfil');
}
