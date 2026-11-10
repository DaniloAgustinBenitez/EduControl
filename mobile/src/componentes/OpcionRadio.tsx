// Opción de una lista de radios (form-check de la web): círculo, nombre y descripción debajo.
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { medidas, useColores } from '../tema/colores';
import { Icono } from './Icono';

interface Props {
  titulo: string;
  /** Líneas chicas debajo del título (descripción, horarios). */
  detalles?: (string | null | undefined)[];
  elegido: boolean;
  deshabilitado?: boolean;
  onPress: () => void;
}

export function OpcionRadio({ titulo, detalles = [], elegido, deshabilitado, onPress }: Props) {
  const c = useColores();
  const lineas = detalles.filter((d): d is string => !!d);
  return (
    <Pressable
      onPress={onPress}
      disabled={deshabilitado}
      accessibilityRole="radio"
      accessibilityLabel={titulo}
      accessibilityHint={lineas.join('. ') || undefined}
      accessibilityState={{ checked: elegido, disabled: deshabilitado }}
      style={({ pressed }) => [estilos.opcion, pressed && { opacity: 0.7 }]}
    >
      <Icono
        nombre={elegido ? 'radiobox-marked' : 'radiobox-blank'}
        color={elegido ? c.primario : c.tenue}
        tamano={22}
      />
      <View style={estilos.textos}>
        <Text style={[estilos.titulo, { color: c.tinta }]}>{titulo}</Text>
        {lineas.map((linea) => (
          <Text key={linea} style={[estilos.detalle, { color: c.tenue }]}>
            {linea}
          </Text>
        ))}
      </View>
    </Pressable>
  );
}

const estilos = StyleSheet.create({
  opcion: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 10,
    minHeight: medidas.areaTactil,
    paddingVertical: 4,
  },
  textos: { flex: 1 },
  titulo: { fontSize: 16, lineHeight: 22 },
  detalle: { fontSize: 14, lineHeight: 19 },
});
