// Mensaje para listas vacías, errores o pantallas sin datos, en una tarjeta con ícono
// (como .ec-vacio de la web), con acciones opcionales.
import { StyleSheet, Text, View } from 'react-native';
import type { ReactNode } from 'react';

import { useColores } from '../tema/colores';
import { Icono } from './Icono';
import type { NombreIcono } from './Icono';
import { Tarjeta } from './Tarjeta';

interface Props {
  titulo: string;
  texto?: string;
  icono?: NombreIcono;
  children?: ReactNode;
}

export function EstadoVacio({ titulo, texto, icono = 'information-outline', children }: Props) {
  const c = useColores();
  return (
    <Tarjeta estilo={estilos.tarjeta}>
      <View style={estilos.icono}>
        <Icono nombre={icono} color={c.tenue} tamano={42} />
      </View>
      <Text accessibilityRole="header" style={[estilos.titulo, { color: c.tinta }]}>
        {titulo}
      </Text>
      {texto ? <Text style={[estilos.texto, { color: c.tenue }]}>{texto}</Text> : null}
      {children ? <View style={estilos.acciones}>{children}</View> : null}
    </Tarjeta>
  );
}

const estilos = StyleSheet.create({
  tarjeta: { alignItems: 'center', paddingVertical: 32, paddingHorizontal: 20, gap: 10 },
  icono: { opacity: 0.55, marginBottom: 4 },
  titulo: { fontSize: 18, fontWeight: '600', textAlign: 'center' },
  texto: { fontSize: 15, lineHeight: 21, textAlign: 'center' },
  acciones: { alignSelf: 'stretch', gap: 10, marginTop: 8 },
});
