// "Pill" de estado: verde si está activo, gris si no (como .ec-pill-si / .ec-pill-no de la web).
import { StyleSheet, Text, View } from 'react-native';

import { useColores } from '../tema/colores';
import { Icono } from './Icono';
import type { NombreIcono } from './Icono';

interface Props {
  texto: string;
  activo: boolean;
  /** Muestra el ícono de la web (check si está activo, guion si no). */
  conIcono?: boolean;
  /** Ícono propio en lugar del de estado (por ejemplo el calendario del ciclo). */
  icono?: NombreIcono;
}

export function Etiqueta({ texto, activo, conIcono = false, icono }: Props) {
  const c = useColores();
  const color = activo ? c.exito : c.neutro;
  const iconoEstado: NombreIcono = activo ? 'check-circle' : 'minus-circle-outline';
  const nombreIcono = icono ?? (conIcono ? iconoEstado : null);

  return (
    <View style={[estilos.etiqueta, { backgroundColor: activo ? c.exitoFondo : c.neutroFondo }]}>
      {nombreIcono ? <Icono nombre={nombreIcono} color={color} tamano={14} /> : null}
      <Text accessibilityLabel={texto} style={[estilos.texto, { color }]}>
        {texto}
      </Text>
    </View>
  );
}

const estilos = StyleSheet.create({
  etiqueta: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 4,
    alignSelf: 'flex-start',
  },
  texto: { fontSize: 13, fontWeight: '600' },
});
