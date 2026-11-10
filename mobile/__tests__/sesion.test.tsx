import { act, renderHook, waitFor } from '@testing-library/react-native';
import * as SecureStore from 'expo-secure-store';
import type { ReactNode } from 'react';

import { obtenerHijos } from '../src/api/tutor';
import { rutaInicial } from '../src/sesion/rutas';
import { SesionProvider, useSesion } from '../src/sesion/SesionContext';
import {
  pedidosHechos,
  respuesta,
  simularApi,
  usuarioAdministrador,
  usuarioDocente,
  usuarioTutor,
} from './ayudantes';

const envoltorio = ({ children }: { children: ReactNode }) => (
  <SesionProvider>{children}</SesionProvider>
);

async function renderSesion() {
  const resultado = await renderHook(() => useSesion(), { wrapper: envoltorio });
  await waitFor(() => expect(resultado.result.current.cargando).toBe(false));
  return resultado;
}

describe('sesión', () => {
  it('sin sesión guardada termina de cargar sin usuario', async () => {
    const { result } = await renderSesion();
    expect(result.current.usuario).toBeNull();
  });

  it('el login guarda el token cifrado y lo usa en los pedidos siguientes', async () => {
    const api = simularApi({
      'POST /login': respuesta(200, { token: '1|token', usuario: usuarioTutor }),
      'GET /hijos': respuesta(200, { ciclo: null, hijos: [] }),
    });
    const { result } = await renderSesion();

    await act(() => result.current.iniciarSesion('tutor@educontrol.test', 'password'));

    expect(result.current.usuario).toEqual(usuarioTutor);
    expect(SecureStore.setItemAsync).toHaveBeenCalledWith('educontrol.token', '1|token');
    const cuerpo = JSON.parse(api.mock.calls[0][1].body);
    expect(cuerpo).toEqual({
      email: 'tutor@educontrol.test',
      password: 'password',
      dispositivo: 'Pixel 7',
    });

    await obtenerHijos();
    expect(api.mock.calls[1][1].headers.Authorization).toBe('Bearer 1|token');
  });

  it('el logout llama a POST /logout y borra el token', async () => {
    const api = simularApi({
      'POST /login': respuesta(200, { token: '1|token', usuario: usuarioTutor }),
      'POST /logout': respuesta(204),
    });
    const { result } = await renderSesion();
    await act(() => result.current.iniciarSesion('tutor@educontrol.test', 'password'));

    await act(() => result.current.cerrarSesion());

    expect(pedidosHechos(api)).toEqual(['POST /login', 'POST /logout']);
    expect(api.mock.calls[1][1].headers.Authorization).toBe('Bearer 1|token');
    expect(result.current.usuario).toBeNull();
    expect(SecureStore.deleteItemAsync).toHaveBeenCalledWith('educontrol.token');
    expect(await SecureStore.getItemAsync('educontrol.token')).toBeNull();
  });

  it('el logout borra el token aunque el servidor no responda', async () => {
    simularApi({
      'POST /login': respuesta(200, { token: '1|token', usuario: usuarioTutor }),
      'POST /logout': () => Promise.reject(new TypeError('Network request failed')),
    });
    const { result } = await renderSesion();
    await act(() => result.current.iniciarSesion('tutor@educontrol.test', 'password'));

    await act(() => result.current.cerrarSesion());

    expect(result.current.usuario).toBeNull();
    expect(await SecureStore.getItemAsync('educontrol.token')).toBeNull();
  });

  it('al abrir la app restaura la sesión y la valida con /perfil', async () => {
    await SecureStore.setItemAsync('educontrol.token', '1|guardado');
    await SecureStore.setItemAsync('educontrol.usuario', JSON.stringify(usuarioTutor));
    const actualizado = { ...usuarioTutor, nombre: 'Laura P. Pérez' };
    const api = simularApi({ 'GET /perfil': respuesta(200, { usuario: actualizado }) });

    const { result } = await renderSesion();

    await waitFor(() => expect(result.current.usuario).toEqual(actualizado));
    expect(api.mock.calls[0][1].headers.Authorization).toBe('Bearer 1|guardado');
  });

  it('si el token guardado venció (401) vuelve a pedir el ingreso', async () => {
    await SecureStore.setItemAsync('educontrol.token', '1|vencido');
    await SecureStore.setItemAsync('educontrol.usuario', JSON.stringify(usuarioTutor));
    simularApi({
      'GET /perfil': respuesta(401, { message: 'Tu sesión venció. Volvé a ingresar.' }),
    });

    const { result } = await renderSesion();

    await waitFor(() => expect(result.current.usuario).toBeNull());
    expect(await SecureStore.getItemAsync('educontrol.token')).toBeNull();
  });

  it('sin conexión al abrir la app sigue con el usuario guardado', async () => {
    await SecureStore.setItemAsync('educontrol.token', '1|guardado');
    await SecureStore.setItemAsync('educontrol.usuario', JSON.stringify(usuarioDocente));
    simularApi({ 'GET /perfil': () => Promise.reject(new TypeError('Network request failed')) });

    const { result } = await renderSesion();

    await waitFor(() =>
      expect(pedidosHechos(globalThis.fetch as jest.Mock)).toEqual(['GET /perfil']),
    );
    expect(result.current.usuario).toEqual(usuarioDocente);
  });

  it('con token pero sin usuario guardado espera a /perfil', async () => {
    await SecureStore.setItemAsync('educontrol.token', '1|guardado');
    simularApi({ 'GET /perfil': respuesta(200, { usuario: usuarioDocente }) });

    const { result } = await renderSesion();

    expect(result.current.usuario).toEqual(usuarioDocente);
  });
});

describe('rutaInicial', () => {
  it('redirige según el rol', () => {
    expect(rutaInicial(null)).toBe('/login');
    expect(rutaInicial(usuarioTutor)).toBe('/hijos');
    expect(rutaInicial(usuarioDocente)).toBe('/cursos');
    expect(rutaInicial(usuarioAdministrador)).toBe('/usar-web');
    expect(rutaInicial({ ...usuarioAdministrador, rol: 'administrativo' })).toBe('/usar-web');
  });
});
