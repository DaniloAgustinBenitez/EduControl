// Botón de la app, con las variantes de Bootstrap que usa la web.
// Área táctil de 44 px como mínimo aunque sea compacto; estados deshabilitado y enviando.
import { ActivityIndicator, Pressable, StyleSheet, Text } from 'react-native';
import type { StyleProp, ViewStyle } from 'react-native';

import { medidas, useColores } from '../tema/colores';
import { Icono } from './Icono';
import type { NombreIcono } from './Icono';

/**
 * primario: btn-primary · secundario: btn-outline-primary · peligro: btn-outline-danger ·
 * contorno: btn-outline-secondary (por ejemplo "Volver").
 */
type Variante = 'primario' | 'secundario' | 'peligro' | 'contorno';

interface Props {
  titulo: string;
  onPress: () => void;
  variante?: Variante;
  /** Ícono a la izquierda del texto. */
  icono?: NombreIcono;
  /** Ancho según el texto y alineado a la izquierda (como btn-sm); si no, ocupa todo el ancho. */
  compacto?: boolean;
  /** Muestra un indicador y deshabilita el botón. */
  cargando?: boolean;
  deshabilitado?: boolean;
  /** Texto para el lector de pantalla, si el título solo no alcanza. */
  etiqueta?: string;
  pista?: string;
  estilo?: StyleProp<ViewStyle>;
}

export function Boton({
  titulo,
  onPress,
  variante = 'primario',
  icono,
  compacto = false,
  cargando = false,
  deshabilitado = false,
  etiqueta,
  pista,
  estilo,
}: Props) {
  const c = useColores();
  const inactivo = deshabilitado || cargando;

  const colores = {
    primario: { fondo: c.primario, texto: c.textoSobrePrimario, borde: c.primario },
    secundario: { fondo: 'transparent', texto: c.primario, borde: c.primario },
    peligro: { fondo: 'transparent', texto: c.peligro, borde: c.peligro },
    contorno: { fondo: 'transparent', texto: c.secundario, borde: c.secundario },
  }[variante];

  return (
    <Pressable
      onPress={onPress}
      disabled={inactivo}
      accessibilityRole="button"
      accessibilityLabel={etiqueta ?? titulo}
      accessibilityHint={pista}
      accessibilityState={{ disabled: inactivo, busy: cargando }}
      style={({ pressed }) => [
        estilos.boton,
        compacto ? estilos.compacto : estilos.ancho,
        { backgroundColor: colores.fondo, borderColor: colores.borde },
        pressed && estilos.presionado,
        inactivo && estilos.inactivo,
        estilo,
      ]}
    >
      {cargando ? (
        <ActivityIndicator color={colores.texto} />
      ) : (
        <>
          {icono ? <Icono nombre={icono} color={colores.texto} tamano={17} /> : null}
          <Text style={[estilos.texto, { color: colores.texto }]}>{titulo}</Text>
        </>
      )}
    </Pressable>
  );
}

const estilos = StyleSheet.create({
  boton: {
    minHeight: medidas.areaTactil,
    minWidth: medidas.areaTactil,
    borderRadius: 9,
    borderWidth: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
  },
  ancho: { alignSelf: 'stretch', paddingHorizontal: 16, paddingVertical: 10 },
  compacto: { alignSelf: 'flex-start', paddingHorizontal: 14, paddingVertical: 8 },
  texto: { fontSize: 15, fontWeight: '600', textAlign: 'center' },
  presionado: { opacity: 0.85 },
  inactivo: { opacity: 0.55 },
});
