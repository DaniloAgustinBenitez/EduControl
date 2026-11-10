// Guarda el token y el usuario cifrados en el dispositivo (RNF-11, riesgo R5).
// En el navegador (para probar la app en la PC con `npx expo start --web`) no hay almacenamiento
// cifrado: se usa sessionStorage, que se borra al cerrar la pestaña.
import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';

import type { Usuario } from '../api/tipos';

const CLAVE_TOKEN = 'educontrol.token';
const CLAVE_USUARIO = 'educontrol.usuario';

export interface SesionGuardada {
  token: string;
  usuario: Usuario | null;
}

interface Almacen {
  leer(clave: string): Promise<string | null>;
  guardar(clave: string, valor: string): Promise<void>;
  borrar(clave: string): Promise<void>;
}

const almacenCelular: Almacen = {
  leer: (clave) => SecureStore.getItemAsync(clave),
  guardar: (clave, valor) => SecureStore.setItemAsync(clave, valor),
  borrar: (clave) => SecureStore.deleteItemAsync(clave),
};

const almacenNavegador: Almacen = {
  leer: async (clave) => globalThis.sessionStorage?.getItem(clave) ?? null,
  guardar: async (clave, valor) => globalThis.sessionStorage?.setItem(clave, valor),
  borrar: async (clave) => globalThis.sessionStorage?.removeItem(clave),
};

function almacen(): Almacen {
  return Platform.OS === 'web' ? almacenNavegador : almacenCelular;
}

export async function leerSesion(): Promise<SesionGuardada | null> {
  try {
    const token = await almacen().leer(CLAVE_TOKEN);
    if (!token) return null;
    const usuario = await almacen().leer(CLAVE_USUARIO);
    return { token, usuario: usuario ? (JSON.parse(usuario) as Usuario) : null };
  } catch {
    // Datos dañados o almacenamiento no disponible: se pide ingresar de nuevo.
    return null;
  }
}

export async function guardarSesion(token: string, usuario: Usuario): Promise<void> {
  await almacen().guardar(CLAVE_TOKEN, token);
  await almacen().guardar(CLAVE_USUARIO, JSON.stringify(usuario));
}

export async function guardarUsuario(usuario: Usuario): Promise<void> {
  await almacen().guardar(CLAVE_USUARIO, JSON.stringify(usuario));
}

export async function borrarSesion(): Promise<void> {
  try {
    await almacen().borrar(CLAVE_TOKEN);
    await almacen().borrar(CLAVE_USUARIO);
  } catch {
    // Si no se puede borrar, igual se olvida el token en memoria.
  }
}
