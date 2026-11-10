// Barra superior de las pantallas del tutor y del docente, como .ec-barra de la web:
// logo y "EduControl" a la izquierda; ciclo lectivo, tema y salir a la derecha.
import { createContext, useContext, useEffect, useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { useColores } from '../tema/colores';
import { BotonCerrarSesion } from './BotonCerrarSesion';
import { BotonTema } from './BotonTema';
import { Etiqueta } from './Etiqueta';
import { Logo } from './Logo';

/**
 * Año del ciclo lectivo que muestra la barra. undefined: todavía no se sabe (no se muestra);
 * null: no hay ciclo activo ("Sin ciclo", como la web).
 */
type CicloBarra = number | null | undefined;

interface ValorCiclo {
  ciclo: CicloBarra;
  setCiclo: (ciclo: CicloBarra) => void;
}

const CicloContext = createContext<ValorCiclo | null>(null);

export function ProveedorCiclo({ children }: { children: ReactNode }) {
  const [ciclo, setCiclo] = useState<CicloBarra>(undefined);
  const valor = useMemo(() => ({ ciclo, setCiclo }), [ciclo]);
  return <CicloContext.Provider value={valor}>{children}</CicloContext.Provider>;
}

/** Las pantallas avisan el ciclo que les devolvió la API para mostrarlo en la barra. */
export function useAnunciarCiclo(anio: CicloBarra) {
  const setCiclo = useContext(CicloContext)?.setCiclo;
  useEffect(() => {
    if (setCiclo && anio !== undefined) setCiclo(anio);
  }, [setCiclo, anio]);
}

export function BarraSuperior() {
  const c = useColores();
  const insets = useSafeAreaInsets();
  const ciclo = useContext(CicloContext)?.ciclo;

  return (
    <View
      style={[
        estilos.barra,
        { paddingTop: insets.top, backgroundColor: c.superficie, borderBottomColor: c.borde },
      ]}
    >
      <View
        style={[estilos.fila, { paddingLeft: 16 + insets.left, paddingRight: 8 + insets.right }]}
      >
        <View
          style={estilos.marca}
          accessible
          accessibilityRole="header"
          accessibilityLabel="EduControl"
        >
          <Logo tamano={34} />
          <Text style={[estilos.nombre, { color: c.tinta }]}>EduControl</Text>
        </View>
        {ciclo !== undefined ? (
          <View style={estilos.ciclo}>
            {ciclo === null ? (
              <Etiqueta texto="Sin ciclo" activo={false} icono="calendar-remove" />
            ) : (
              <Etiqueta texto={String(ciclo)} activo icono="calendar-check" />
            )}
          </View>
        ) : null}
        <BotonTema />
        <BotonCerrarSesion />
      </View>
    </View>
  );
}

const estilos = StyleSheet.create({
  barra: { borderBottomWidth: 1 },
  fila: { flexDirection: 'row', alignItems: 'center', gap: 4, minHeight: 60 },
  marca: { flex: 1, flexDirection: 'row', alignItems: 'center', gap: 10 },
  nombre: { fontSize: 18, fontWeight: '700', letterSpacing: -0.3 },
  ciclo: { marginRight: 4 },
});
