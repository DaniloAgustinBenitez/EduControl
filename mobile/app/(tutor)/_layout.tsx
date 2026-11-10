// Pantallas del tutor. Solo entra un usuario con rol tutor; el resto vuelve al inicio.
// En lugar del encabezado nativo se usa la barra superior de la web (logo, ciclo, tema y salir).
import { Redirect, Stack } from 'expo-router';
import { StyleSheet, View } from 'react-native';

import { BarraSuperior, ProveedorCiclo } from '../../src/componentes/BarraSuperior';
import { Cargando } from '../../src/componentes/Cargando';
import { rutaInicial } from '../../src/sesion/rutas';
import { useSesion } from '../../src/sesion/SesionContext';
import { useColores } from '../../src/tema/colores';

export default function LayoutTutor() {
  const c = useColores();
  const { cargando, usuario } = useSesion();
  if (cargando) return <Cargando />;
  if (rutaInicial(usuario) !== '/hijos') return <Redirect href="/" />;

  return (
    <ProveedorCiclo>
      <View style={[estilos.pantalla, { backgroundColor: c.fondo }]}>
        <BarraSuperior />
        <Stack screenOptions={{ headerShown: false, contentStyle: { backgroundColor: c.fondo } }}>
          <Stack.Screen name="hijos/index" options={{ title: 'Mis hijos' }} />
          <Stack.Screen name="hijos/[id]" options={{ title: 'Inscripciones' }} />
        </Stack>
      </View>
    </ProveedorCiclo>
  );
}

const estilos = StyleSheet.create({ pantalla: { flex: 1 } });
