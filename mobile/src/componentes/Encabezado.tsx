// Título de cada pantalla con subtítulo y acciones a la derecha (componente x-encabezado de la web).
import { StyleSheet, Text, View } from 'react-native';
import type { ReactNode } from 'react';

import { useColores } from '../tema/colores';

interface Props {
  titulo: string;
  subtitulo?: string;
  /** Botones a la derecha, por ejemplo "Volver". */
  children?: ReactNode;
}

export function Encabezado({ titulo, subtitulo, children }: Props) {
  const c = useColores();
  return (
    <View style={estilos.encabezado}>
      <View style={estilos.textos}>
        <Text accessibilityRole="header" style={[estilos.titulo, { color: c.tinta }]}>
          {titulo}
        </Text>
        {subtitulo ? (
          <Text style={[estilos.subtitulo, { color: c.tenue }]}>{subtitulo}</Text>
        ) : null}
      </View>
      {children ? <View style={estilos.acciones}>{children}</View> : null}
    </View>
  );
}

const estilos = StyleSheet.create({
  encabezado: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    gap: 8,
  },
  textos: { flexGrow: 1, flexShrink: 1, flexBasis: 200, gap: 2 },
  titulo: { fontSize: 26, fontWeight: '700', letterSpacing: -0.4 },
  subtitulo: { fontSize: 16, lineHeight: 22 },
  acciones: { flexDirection: 'row', gap: 8 },
});
