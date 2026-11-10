// Texto con una lista de horarios ("Lunes 08:00 a 12:00 · Miércoles ..."), como en la web.
// Con `conIcono` se muestra el reloj adelante (bi-clock).
import { StyleSheet, Text, View } from 'react-native';
import type { StyleProp, TextStyle } from 'react-native';

import type { Horario } from '../api/tipos';
import { useColores } from '../tema/colores';
import { Icono } from './Icono';

interface Props {
  horarios: Horario[];
  /** Texto si la lista está vacía. */
  vacio?: string;
  prefijo?: string;
  conIcono?: boolean;
  estilo?: StyleProp<TextStyle>;
}

export function textoHorarios(horarios: Horario[]): string {
  return horarios.map((h) => h.descripcion).join(' · ');
}

export function Horarios({ horarios, vacio = 'Sin horario', prefijo, conIcono, estilo }: Props) {
  const c = useColores();
  const texto = horarios.length ? textoHorarios(horarios) : vacio;
  const contenido = (
    <Text style={[estilos.texto, { color: c.tenue }, conIcono && estilos.flex, estilo]}>
      {prefijo ? `${prefijo}: ` : ''}
      {texto}
    </Text>
  );
  if (!conIcono) return contenido;
  return (
    <View style={estilos.fila}>
      <Icono nombre="clock-outline" color={c.tenue} tamano={15} estilo={estilos.icono} />
      {contenido}
    </View>
  );
}

const estilos = StyleSheet.create({
  texto: { fontSize: 14, lineHeight: 20 },
  fila: { flexDirection: 'row', alignItems: 'flex-start', gap: 6 },
  icono: { marginTop: 2 },
  flex: { flex: 1 },
});
