import { render, screen, userEvent, waitFor } from '@testing-library/react-native';
import * as SecureStore from 'expo-secure-store';
import { Linking } from 'react-native';

import LayoutDocente from '../app/(docente)/_layout';
import LayoutTutor from '../app/(tutor)/_layout';
import Inicio from '../app/index';
import UsarWeb from '../app/usar-web';
import { BotonCerrarSesion } from '../src/componentes/BotonCerrarSesion';
import { Cargando, MENSAJE_DESPERTANDO } from '../src/componentes/Cargando';
import { pedir, UMBRAL_LENTO_MS } from '../src/api/cliente';
import type { Usuario } from '../src/api/tipos';
import { SesionProvider } from '../src/sesion/SesionContext';
import {
  pedidosHechos,
  respuesta,
  simularApi,
  usuarioAdministrador,
  usuarioDocente,
  usuarioTutor,
} from './ayudantes';

/** Deja una sesión guardada, como si el usuario ya hubiera ingresado antes. */
async function conSesion(usuario: Usuario) {
  await SecureStore.setItemAsync('educontrol.token', '1|guardado');
  await SecureStore.setItemAsync('educontrol.usuario', JSON.stringify(usuario));
  return simularApi({
    'GET /perfil': respuesta(200, { usuario }),
    'POST /logout': respuesta(204),
  });
}

function conProveedor(elemento: React.ReactElement) {
  return render(<SesionProvider>{elemento}</SesionProvider>);
}

describe('redirección por rol', () => {
  it('sin sesión lleva al login', async () => {
    await conProveedor(<Inicio />);
    expect(await screen.findByText('Redirige a /login')).toBeOnTheScreen();
  });

  it.each([
    [usuarioTutor, '/hijos'],
    [usuarioDocente, '/cursos'],
    [usuarioAdministrador, '/usar-web'],
  ])('el usuario %# va a su pantalla', async (usuario, ruta) => {
    await conSesion(usuario);
    await conProveedor(<Inicio />);
    expect(await screen.findByText(`Redirige a ${ruta}`)).toBeOnTheScreen();
  });

  it('las pantallas del tutor no dejan entrar a un docente', async () => {
    await conSesion(usuarioDocente);
    await conProveedor(<LayoutTutor />);
    expect(await screen.findByText('Redirige a /')).toBeOnTheScreen();
  });

  it('las pantallas del docente no dejan entrar a un tutor', async () => {
    await conSesion(usuarioTutor);
    await conProveedor(<LayoutDocente />);
    expect(await screen.findByText('Redirige a /')).toBeOnTheScreen();
  });
});

describe('Usá la versión web', () => {
  it('explica que use la web, abre el link y permite cerrar sesión', async () => {
    const api = await conSesion(usuarioAdministrador);
    const abrir = jest.spyOn(Linking, 'openURL').mockResolvedValue(undefined);
    const user = userEvent.setup();
    await conProveedor(<UsarWeb />);

    expect(await screen.findByText('Usá la versión web')).toBeOnTheScreen();
    expect(screen.getByText(/Administrador/)).toBeOnTheScreen();

    await user.press(screen.getByRole('button', { name: 'Abrir EduControl web' }));
    expect(abrir).toHaveBeenCalledWith('https://educontrol-5btq.onrender.com');

    await user.press(screen.getByRole('button', { name: 'Cerrar sesión' }));
    expect(await screen.findByText('Redirige a /login')).toBeOnTheScreen();
    expect(pedidosHechos(api)).toContain('POST /logout');
  });
});

describe('botón de cerrar sesión', () => {
  it('cierra la sesión y borra el token', async () => {
    const api = await conSesion(usuarioTutor);
    await conProveedor(<BotonCerrarSesion />);

    await userEvent.setup().press(await screen.findByRole('button', { name: 'Cerrar sesión' }));

    await waitFor(() => expect(pedidosHechos(api)).toContain('POST /logout'));
    await waitFor(async () =>
      expect(await SecureStore.getItemAsync('educontrol.token')).toBeNull(),
    );
  });
});

describe('servidor dormido (RNF-13)', () => {
  beforeEach(() => jest.useFakeTimers());
  afterEach(() => jest.useRealTimers());

  it('después de 4 s la pantalla de carga avisa que se está despertando', async () => {
    let responder!: (r: Response) => void;
    globalThis.fetch = jest.fn(
      () =>
        new Promise<Response>((r) => {
          responder = r;
        }),
    );
    await render(<Cargando />);
    expect(screen.getByText('Cargando…')).toBeOnTheScreen();

    const pedido = pedir('GET', '/hijos');
    await jest.advanceTimersByTimeAsync(UMBRAL_LENTO_MS);

    expect(await screen.findByText(MENSAJE_DESPERTANDO)).toBeOnTheScreen();

    responder(respuesta(200, { hijos: [] }));
    await pedido;
    expect(await screen.findByText('Cargando…')).toBeOnTheScreen();
  });
});
