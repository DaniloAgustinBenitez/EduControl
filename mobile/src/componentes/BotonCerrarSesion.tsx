// Botón de salir de la barra superior (bi-box-arrow-right de la web).
import { useState } from 'react';
import { ActivityIndicator, Pressable, StyleSheet } from 'react-native';

import { useSesion } from '../sesion/SesionContext';
import { medidas, useColores } from '../tema/colores';
import { Icono } from './Icono';

export function BotonCerrarSesion() {
  const c = useColores();
  const { cerrarSesion } = useSesion();
  const [saliendo, setSaliendo] = useState(false);

  async function salir() {
    setSaliendo(true);
    await cerrarSesion();
  }

  return (
    <Pressable
      onPress={salir}
      disabled={saliendo}
      accessibilityRole="button"
      accessibilityLabel="Cerrar sesión"
      accessibilityState={{ disabled: saliendo, busy: saliendo }}
      style={({ pressed }) => [estilos.boton, pressed && { backgroundColor: c.neutroFondo }]}
    >
      {saliendo ? (
        <ActivityIndicator size="small" color={c.tenue} />
      ) : (
        <Icono nombre="logout" color={c.tenue} tamano={22} />
      )}
    </Pressable>
  );
}

const estilos = StyleSheet.create({
  boton: {
    width: medidas.areaTactil,
    height: medidas.areaTactil,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
