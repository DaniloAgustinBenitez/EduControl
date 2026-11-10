// Raíz de la app: tema claro/oscuro elegido por el usuario, sesión y navegación.
import { DarkTheme, DefaultTheme, Stack, ThemeProvider } from 'expo-router';
import type { Theme } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useEffect } from 'react';

import { despertarServidor } from '../src/api/servidor';
import { SesionProvider } from '../src/sesion/SesionContext';
import { useTema } from '../src/tema/colores';
import type { Colores } from '../src/tema/colores';
import { TemaProvider } from '../src/tema/TemaContext';

function temaDeNavegacion(oscuro: boolean, c: Colores): Theme {
  const base = oscuro ? DarkTheme : DefaultTheme;
  return {
    ...base,
    colors: {
      ...base.colors,
      primary: c.primario,
      background: c.fondo,
      card: c.superficie,
      text: c.tinta,
      border: c.borde,
    },
  };
}

function Navegacion() {
  const { oscuro, colores } = useTema();
  return (
    <ThemeProvider value={temaDeNavegacion(oscuro, colores)}>
      <StatusBar style={oscuro ? 'light' : 'dark'} />
      <Stack
        screenOptions={{ headerShown: false, contentStyle: { backgroundColor: colores.fondo } }}
      />
    </ThemeProvider>
  );
}

export default function LayoutRaiz() {
  // Render gratis se duerme: se lo va despertando apenas se abre la app (RNF-13).
  useEffect(() => {
    despertarServidor();
  }, []);

  return (
    <TemaProvider>
      <SesionProvider>
        <Navegacion />
      </SesionProvider>
    </TemaProvider>
  );
}
