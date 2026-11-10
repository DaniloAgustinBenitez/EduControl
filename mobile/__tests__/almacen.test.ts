import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';

import { borrarSesion, guardarSesion, leerSesion } from '../src/sesion/almacen';
import { usuarioTutor } from './ayudantes';

describe('almacén de la sesión en el navegador', () => {
  const sistemaOriginal = Platform.OS;
  const datos = new Map<string, string>();

  beforeAll(() => {
    Platform.OS = 'web';
    (globalThis as { sessionStorage?: unknown }).sessionStorage = {
      getItem: (clave: string) => datos.get(clave) ?? null,
      setItem: (clave: string, valor: string) => void datos.set(clave, valor),
      removeItem: (clave: string) => void datos.delete(clave),
    };
  });

  afterAll(() => {
    Platform.OS = sistemaOriginal;
    delete (globalThis as { sessionStorage?: unknown }).sessionStorage;
  });

  it('guarda, lee y borra la sesión en sessionStorage, sin usar SecureStore', async () => {
    await guardarSesion('1|token', usuarioTutor);
    expect(await leerSesion()).toEqual({ token: '1|token', usuario: usuarioTutor });
    expect(SecureStore.setItemAsync).not.toHaveBeenCalled();

    await borrarSesion();
    expect(await leerSesion()).toBeNull();
    expect(datos.size).toBe(0);
  });
});
