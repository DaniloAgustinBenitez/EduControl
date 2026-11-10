// Guarda el tema elegido (claro u oscuro), con el mismo criterio que src/sesion/almacen.ts:
// SecureStore en el celular y, en el navegador, localStorage con la misma clave que la web ("ec-tema").
import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';

import type { ModoTema } from './colores';

export const CLAVE_TEMA_WEB = 'ec-tema';
export const CLAVE_TEMA_CELULAR = 'educontrol.tema';

/** La web guarda "light" o "dark"; se usa el mismo formato para compartir la elección. */
function desdeTexto(valor: string | null | undefined): ModoTema | null {
  if (valor === 'dark') return 'oscuro';
  if (valor === 'light') return 'claro';
  return null;
}

const aTexto = (modo: ModoTema) => (modo === 'oscuro' ? 'dark' : 'light');

/** Lectura inmediata, solo en el navegador (evita que la pantalla parpadee al abrir). */
export function leerTemaWebSincronico(): ModoTema | null {
  if (Platform.OS !== 'web') return null;
  try {
    return desdeTexto(globalThis.localStorage?.getItem(CLAVE_TEMA_WEB));
  } catch {
    return null;
  }
}

export async function leerTema(): Promise<ModoTema | null> {
  if (Platform.OS === 'web') return leerTemaWebSincronico();
  try {
    return desdeTexto(await SecureStore.getItemAsync(CLAVE_TEMA_CELULAR));
  } catch {
    return null;
  }
}

export async function guardarTema(modo: ModoTema): Promise<void> {
  try {
    if (Platform.OS === 'web') globalThis.localStorage?.setItem(CLAVE_TEMA_WEB, aTexto(modo));
    else await SecureStore.setItemAsync(CLAVE_TEMA_CELULAR, aTexto(modo));
  } catch {
    // Si no se puede guardar, el tema dura hasta cerrar la app.
  }
}
