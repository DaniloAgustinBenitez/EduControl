import { render, screen, userEvent, waitFor } from '@testing-library/react-native';
import * as SecureStore from 'expo-secure-store';
import { Alert, Platform, Text } from 'react-native';
import type { AlertButton } from 'react-native';

import { BarraSuperior, ProveedorCiclo, useAnunciarCiclo } from '../src/componentes/BarraSuperior';
import { BotonTema } from '../src/componentes/BotonTema';
import { SesionProvider } from '../src/sesion/SesionContext';
import { coloresClaros, coloresOscuros, conAlfa, useTema } from '../src/tema/colores';
import { TemaProvider } from '../src/tema/TemaContext';
import { confirmar } from '../src/utilidades/confirmar';

/** Muestra el modo y el color de fondo actuales para poder comprobarlos. */
function ModoActual() {
  const { modo, colores } = useTema();
  return <Text>{`modo ${modo} ${colores.fondo}`}</Text>;
}

function conTema() {
  return render(
    <TemaProvider>
      <ModoActual />
      <BotonTema />
    </TemaProvider>,
  );
}

describe('tema claro / oscuro', () => {
  it('arranca en claro, como la web', async () => {
    await conTema();
    expect(screen.getByText(`modo claro ${coloresClaros.fondo}`)).toBeOnTheScreen();
    expect(screen.getByRole('button', { name: 'Cambiar a modo oscuro' })).toBeOnTheScreen();
    expect(screen.getByTestId('icono-weather-night')).toBeOnTheScreen();
  });

  it('el botón alterna el tema y guarda la elección en el celular', async () => {
    const user = userEvent.setup();
    await conTema();

    await user.press(screen.getByRole('button', { name: 'Cambiar a modo oscuro' }));

    expect(screen.getByText(`modo oscuro ${coloresOscuros.fondo}`)).toBeOnTheScreen();
    expect(screen.getByTestId('icono-white-balance-sunny')).toBeOnTheScreen();
    await waitFor(async () =>
      expect(await SecureStore.getItemAsync('educontrol.tema')).toBe('dark'),
    );

    await user.press(screen.getByRole('button', { name: 'Cambiar a modo claro' }));
    expect(screen.getByText(`modo claro ${coloresClaros.fondo}`)).toBeOnTheScreen();
    await waitFor(async () =>
      expect(await SecureStore.getItemAsync('educontrol.tema')).toBe('light'),
    );
  });

  it('al abrir la app recupera el tema guardado', async () => {
    await SecureStore.setItemAsync('educontrol.tema', 'dark');
    await conTema();
    expect(await screen.findByText(`modo oscuro ${coloresOscuros.fondo}`)).toBeOnTheScreen();
  });

  it('en el navegador usa localStorage con la misma clave que la web ("ec-tema")', async () => {
    const plataforma = jest.replaceProperty(Platform, 'OS', 'web');
    const datos = new Map<string, string>([['ec-tema', 'dark']]);
    const original = globalThis.localStorage;
    globalThis.localStorage = {
      getItem: (clave: string) => datos.get(clave) ?? null,
      setItem: (clave: string, valor: string) => void datos.set(clave, valor),
    } as Storage;
    try {
      await conTema();
      // Se lee al instante, sin pasar primero por el modo claro.
      expect(screen.getByText(`modo oscuro ${coloresOscuros.fondo}`)).toBeOnTheScreen();

      await userEvent.setup().press(screen.getByRole('button', { name: 'Cambiar a modo claro' }));
      expect(datos.get('ec-tema')).toBe('light');
      expect(SecureStore.setItemAsync).not.toHaveBeenCalled();
    } finally {
      globalThis.localStorage = original;
      plataforma.restore();
    }
  });

  it('agrega transparencia a los colores hexadecimales', () => {
    expect(conAlfa('#b91c1c', 0.5)).toBe('#b91c1c80');
    expect(conAlfa('rgb(0, 0, 0)', 0.5)).toBe('rgb(0, 0, 0)');
  });
});

describe('barra superior', () => {
  function Pantalla({ ciclo }: { ciclo: number | null | undefined }) {
    useAnunciarCiclo(ciclo);
    return null;
  }

  function conBarra(ciclo: number | null | undefined) {
    return render(
      <TemaProvider>
        <SesionProvider>
          <ProveedorCiclo>
            <BarraSuperior />
            <Pantalla ciclo={ciclo} />
          </ProveedorCiclo>
        </SesionProvider>
      </TemaProvider>,
    );
  }

  it('muestra la marca, el ciclo que anuncia la pantalla, el tema y salir', async () => {
    await conBarra(2026);
    expect(screen.getByLabelText('EduControl')).toBeOnTheScreen();
    expect(await screen.findByText('2026')).toBeOnTheScreen();
    expect(screen.getByTestId('icono-calendar-check')).toBeOnTheScreen();
    expect(screen.getByRole('button', { name: 'Cambiar a modo oscuro' })).toBeOnTheScreen();
    expect(screen.getByRole('button', { name: 'Cerrar sesión' })).toBeOnTheScreen();
  });

  it('sin ciclo activo muestra "Sin ciclo" y, si no se sabe (docente), nada', async () => {
    await conBarra(null);
    expect(await screen.findByText('Sin ciclo')).toBeOnTheScreen();

    await conBarra(undefined);
    expect(screen.queryByText('Sin ciclo')).not.toBeOnTheScreen();
    expect(screen.queryByTestId('icono-calendar-check')).not.toBeOnTheScreen();
  });
});

describe('confirmación de baja', () => {
  it('en el celular usa Alert y devuelve lo que elige el usuario', async () => {
    const alerta = jest.spyOn(Alert, 'alert');
    const tocar = (estilo: AlertButton['style']) =>
      alerta.mockImplementationOnce((_t, _m, botones?: AlertButton[]) => {
        botones?.find((b) => b.style === estilo)?.onPress?.();
      });

    tocar('destructive');
    await expect(confirmar({ titulo: 'Dar de baja', mensaje: '¿Seguro?' })).resolves.toBe(true);
    tocar('cancel');
    await expect(confirmar({ titulo: 'Dar de baja', mensaje: '¿Seguro?' })).resolves.toBe(false);
  });

  it('en el navegador usa window.confirm', async () => {
    const plataforma = jest.replaceProperty(Platform, 'OS', 'web');
    const original = globalThis.confirm;
    globalThis.confirm = jest.fn(() => false);
    try {
      await expect(confirmar({ titulo: 'Dar de baja', mensaje: '¿Seguro?' })).resolves.toBe(false);
      expect(globalThis.confirm).toHaveBeenCalledWith('¿Seguro?');
    } finally {
      globalThis.confirm = original;
      plataforma.restore();
    }
  });
});
