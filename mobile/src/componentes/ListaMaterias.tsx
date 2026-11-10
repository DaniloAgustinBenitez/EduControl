// Materias como insignias azules, igual que los "badge text-bg-primary" de la web del docente.
import { StyleSheet, Text, View } from 'react-native';

import type { Materia } from '../api/tipos';
import { useColores } from '../tema/colores';

interface Props {
  materias: Materia[];
  /** Texto si no hay materias. */
  vacio: string;
}

export function ListaMaterias({ materias, vacio }: Props) {
  const c = useColores();
  if (!materias.length) return <Text style={[estilos.vacio, { color: c.tenue }]}>{vacio}</Text>;
  return (
    <View
      style={estilos.materias}
      accessible
      accessibilityLabel={`Materias: ${materias.map((m) => m.nombre).join(', ')}`}
    >
      {materias.map((materia) => (
        <View key={materia.id} style={[estilos.materia, { backgroundColor: c.primario }]}>
          <Text style={[estilos.texto, { color: c.textoSobrePrimario }]}>{materia.nombre}</Text>
        </View>
      ))}
    </View>
  );
}

const estilos = StyleSheet.create({
  vacio: { fontSize: 14 },
  materias: { flexDirection: 'row', flexWrap: 'wrap', gap: 6 },
  materia: { borderRadius: 6, paddingHorizontal: 8, paddingVertical: 4 },
  texto: { fontSize: 12.5, fontWeight: '700' },
});
