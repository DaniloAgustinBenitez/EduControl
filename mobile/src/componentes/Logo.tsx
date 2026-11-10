// Logo de EduControl: cuadradito con degradé azul a verde azulado y birrete blanco (.ec-logo de la web).
import { StyleSheet, View } from 'react-native';

import { Icono } from './Icono';

const AZUL = '#1d4ed8';
const VERDE_AZULADO = '#0d9488';

export function Logo({ tamano = 36 }: { tamano?: number }) {
  return (
    <View
      accessibilityElementsHidden
      importantForAccessibility="no-hide-descendants"
      style={[
        estilos.logo,
        {
          width: tamano,
          height: tamano,
          borderRadius: Math.round(tamano * 0.28),
          backgroundImage: `linear-gradient(135deg, ${AZUL}, ${VERDE_AZULADO})`,
        },
      ]}
    >
      <Icono nombre="school" color="#ffffff" tamano={Math.round(tamano * 0.55)} />
    </View>
  );
}

const estilos = StyleSheet.create({
  // El color de fondo queda por si la plataforma no dibuja el degradé.
  logo: { backgroundColor: AZUL, alignItems: 'center', justifyContent: 'center' },
});
