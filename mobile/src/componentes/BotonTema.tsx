// Botón para alternar entre modo claro y oscuro, como #btnTema de la web
// (luna en claro, sol en oscuro).
import { Pressable, StyleSheet } from 'react-native';

import { medidas, useTema } from '../tema/colores';
import { Icono } from './Icono';

export function BotonTema() {
  const { oscuro, alternar, colores: c } = useTema();
  return (
    <Pressable
      onPress={alternar}
      accessibilityRole="button"
      accessibilityLabel={oscuro ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'}
      style={({ pressed }) => [estilos.boton, pressed && { backgroundColor: c.neutroFondo }]}
    >
      <Icono
        nombre={oscuro ? 'white-balance-sunny' : 'weather-night'}
        color={c.tenue}
        tamano={22}
      />
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
