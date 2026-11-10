// Tarjeta con fondo de superficie, borde y sombra suave, como las .card de la web.
import { StyleSheet, View } from 'react-native';
import type { ReactNode } from 'react';
import type { StyleProp, ViewStyle } from 'react-native';

import { medidas, useColores } from '../tema/colores';

interface Props {
  children: ReactNode;
  estilo?: StyleProp<ViewStyle>;
}

export function Tarjeta({ children, estilo }: Props) {
  const c = useColores();
  return (
    <View
      style={[
        estilos.tarjeta,
        { backgroundColor: c.superficie, borderColor: c.borde, boxShadow: c.sombra },
        estilo,
      ]}
    >
      {children}
    </View>
  );
}

const estilos = StyleSheet.create({
  tarjeta: {
    borderWidth: 1,
    borderRadius: medidas.radio,
    padding: medidas.espacio,
    gap: 12,
  },
});
