// Aviso destacado como las alertas de Bootstrap (fondo suave, borde e ícono).
// El lector de pantalla lo anuncia.
import { StyleSheet, Text, View } from 'react-native';

import { conAlfa, useColores } from '../tema/colores';
import { Icono } from './Icono';
import type { NombreIcono } from './Icono';

export type TipoAviso = 'error' | 'exito' | 'info' | 'advertencia';

interface Props {
  tipo: TipoAviso;
  texto: string;
}

const ICONOS: Record<TipoAviso, NombreIcono> = {
  error: 'alert-circle-outline',
  exito: 'check-circle-outline',
  info: 'information-outline',
  advertencia: 'alert-outline',
};

export function Aviso({ tipo, texto }: Props) {
  const c = useColores();
  const colores = {
    error: { texto: c.peligro, fondo: c.peligroFondo },
    exito: { texto: c.exito, fondo: c.exitoFondo },
    info: { texto: c.info, fondo: c.infoFondo },
    advertencia: { texto: c.advertencia, fondo: c.advertenciaFondo },
  }[tipo];

  return (
    <View
      accessible
      accessibilityRole={tipo === 'error' ? 'alert' : 'text'}
      accessibilityLiveRegion={tipo === 'error' ? 'assertive' : 'polite'}
      style={[
        estilos.aviso,
        { backgroundColor: colores.fondo, borderColor: conAlfa(colores.texto, 0.35) },
      ]}
    >
      <Icono nombre={ICONOS[tipo]} color={colores.texto} tamano={20} />
      <Text style={[estilos.texto, { color: colores.texto }]}>{texto}</Text>
    </View>
  );
}

const estilos = StyleSheet.create({
  aviso: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 10,
    borderWidth: 1,
    borderRadius: 10,
    paddingVertical: 11,
    paddingHorizontal: 13,
  },
  texto: { flex: 1, fontSize: 15, lineHeight: 21 },
});
