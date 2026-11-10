import { render, screen, userEvent, waitFor } from '@testing-library/react-native';
import * as SecureStore from 'expo-secure-store';

import Login from '../app/login';
import { SesionProvider } from '../src/sesion/SesionContext';
import { respuesta, simularApi, usuarioTutor } from './ayudantes';

async function renderLogin() {
  await render(
    <SesionProvider>
      <Login />
    </SesionProvider>,
  );
  return screen.findByRole('button', { name: 'Ingresar' });
}

async function completar(email: string, password: string) {
  const user = userEvent.setup();
  await user.type(screen.getByLabelText('Correo electrónico'), email);
  await user.type(screen.getByLabelText('Contraseña'), password);
  await user.press(screen.getByRole('button', { name: 'Ingresar' }));
}

describe('pantalla de login', () => {
  it('valida los campos antes de llamar a la API', async () => {
    const user = userEvent.setup();
    const boton = await renderLogin();

    await user.press(boton);

    expect(screen.getByText('Ingresá tu correo.')).toBeOnTheScreen();
    expect(screen.getByText('Ingresá tu contraseña.')).toBeOnTheScreen();

    await user.type(screen.getByLabelText('Correo electrónico'), 'tutor');
    await user.press(boton);
    expect(screen.getByText('Ingresá un correo válido.')).toBeOnTheScreen();
    expect(globalThis.fetch).not.toHaveBeenCalled();
  });

  it('muestra el 422 de credenciales debajo del correo', async () => {
    simularApi({
      'POST /login': respuesta(422, {
        message: 'El correo o la contraseña no son correctos.',
        errors: { email: ['El correo o la contraseña no son correctos.'] },
      }),
    });
    await renderLogin();

    await completar('tutor@educontrol.test', 'mala');

    expect(
      await screen.findByText('El correo o la contraseña no son correctos.'),
    ).toBeOnTheScreen();
    expect(screen.getByRole('button', { name: 'Ingresar' })).toBeEnabled();
  });

  it('muestra el aviso del 429 por demasiados intentos', async () => {
    simularApi({
      'POST /login': respuesta(429, {
        message: 'Demasiados intentos. Esperá un minuto y volvé a intentar.',
      }),
    });
    await renderLogin();

    await completar('tutor@educontrol.test', 'password');

    expect(
      await screen.findByText('Demasiados intentos. Esperá un minuto y volvé a intentar.'),
    ).toBeOnTheScreen();
  });

  it('muestra un mensaje claro si no hay conexión', async () => {
    simularApi({ 'POST /login': () => Promise.reject(new TypeError('Network request failed')) });
    await renderLogin();

    await completar('tutor@educontrol.test', 'password');

    expect(await screen.findByText(/No se pudo conectar con el servidor/)).toBeOnTheScreen();
  });

  it('con datos correctos guarda el token y redirige al inicio', async () => {
    const api = simularApi({
      'POST /login': respuesta(200, { token: '1|token', usuario: usuarioTutor }),
    });
    await renderLogin();

    await completar(' tutor@educontrol.test ', 'password');

    expect(await screen.findByText('Redirige a /')).toBeOnTheScreen();
    const cuerpo = JSON.parse(api.mock.calls[0][1].body);
    expect(cuerpo).toEqual({
      email: 'tutor@educontrol.test',
      password: 'password',
      dispositivo: 'Pixel 7',
    });
    await waitFor(async () =>
      expect(await SecureStore.getItemAsync('educontrol.token')).toBe('1|token'),
    );
  });
});
