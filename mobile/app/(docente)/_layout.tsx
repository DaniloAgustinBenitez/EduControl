// Pantallas del docente. Solo entra un usuario con rol docente; el resto vuelve al inicio.
// En lugar del encabezado nativo se usa la barra superior de la web (logo, tema y salir).
import { Redirect, Stack } from 'expo-router';
import { StyleSheet, View } from 'react-native';

import { BarraSuperior } from '../../src/componentes/BarraSuperior';
import { Cargando } from '../../src/componentes/Cargando';
import { rutaInicial } from '../../src/sesion/rutas';
import { useSesion } from '../../src/sesion/SesionContext';
import { useColores } from '../../src/tema/colores';

export default function LayoutDocente() {
  const c = useColores();
  const { cargando, usuario } = useSesion();
  if (cargando) return <Cargando />;
  if (rutaInicial(usuario) !== '/cursos') return <Redirect href="/" />;

  // La API del docente no devuelve el ciclo lectivo: la barra no muestra la insignia.
  return (
    <View style={[estilos.pantalla, { backgroundColor: c.fondo }]}>
      <BarraSuperior />
      <Stack screenOptions={{ headerShown: false, contentStyle: { backgroundColor: c.fondo } }}>
        <Stack.Screen name="cursos/index" options={{ title: 'Mis cursos' }} />
        <Stack.Screen name="cursos/[id]" options={{ title: 'Curso' }} />
      </Stack>
    </View>
  );
}

const estilos = StyleSheet.create({ pantalla: { flex: 1 } });
