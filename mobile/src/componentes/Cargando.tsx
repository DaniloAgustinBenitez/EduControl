// Pantalla de carga. Si el pedido tarda, avisa que el servidor se está despertando (RNF-13).
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';

import { useServidorLento } from '../hooks/useServidorLento';
import { useColores } from '../tema/colores';
import { Logo } from './Logo';

export const MENSAJE_DESPERTANDO = 'Despertando el servidor, puede tardar hasta un minuto…';

export function Cargando({ texto = 'Cargando…' }: { texto?: string }) {
  const c = useColores();
  const lento = useServidorLento();
  const mensaje = lento ? MENSAJE_DESPERTANDO : texto;

  return (
    <View style={[estilos.contenedor, { backgroundColor: c.fondo }]}>
      <Logo tamano={44} />
      <ActivityIndicator size="large" color={c.primario} accessibilityLabel={mensaje} />
      <Text
        accessibilityLiveRegion="polite"
        style={[estilos.texto, { color: lento ? c.texto : c.tenue }]}
      >
        {mensaje}
      </Text>
    </View>
  );
}

/** Aviso chico para mostrar junto a un botón mientras se espera un servidor dormido. */
export function AvisoServidorLento() {
  const c = useColores();
  const lento = useServidorLento();
  if (!lento) return null;
  return (
    <View
      style={[estilos.fila, { backgroundColor: c.infoFondo, borderColor: c.borde }]}
      accessibilityLiveRegion="polite"
    >
      <ActivityIndicator size="small" color={c.primario} />
      <Text style={[estilos.textoChico, { color: c.texto }]}>{MENSAJE_DESPERTANDO}</Text>
    </View>
  );
}

const estilos = StyleSheet.create({
  contenedor: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 24,
    gap: 16,
  },
  texto: { fontSize: 16, textAlign: 'center' },
  fila: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderWidth: 1,
    borderRadius: 10,
    paddingVertical: 10,
    paddingHorizontal: 12,
  },
  textoChico: { flex: 1, fontSize: 14 },
});
