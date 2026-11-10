// Proveedor del tema: arranca en claro (como la web) y recuerda la elección del usuario.
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import type { ReactNode } from 'react';

import { coloresClaros, coloresOscuros, TemaContext } from './colores';
import type { ModoTema, ValorTema } from './colores';
import { guardarTema, leerTema, leerTemaWebSincronico } from './preferencia';

export function TemaProvider({ children }: { children: ReactNode }) {
  const [modo, setModo] = useState<ModoTema>(() => leerTemaWebSincronico() ?? 'claro');
  const modoActual = useRef(modo);
  // Si el usuario ya tocó el botón, la lectura guardada (más lenta) no lo pisa.
  const elegido = useRef(false);

  useEffect(() => {
    let activo = true;
    void leerTema().then((guardado) => {
      if (activo && guardado && !elegido.current) {
        modoActual.current = guardado;
        setModo(guardado);
      }
    });
    return () => {
      activo = false;
    };
  }, []);

  const alternar = useCallback(() => {
    elegido.current = true;
    const nuevo: ModoTema = modoActual.current === 'oscuro' ? 'claro' : 'oscuro';
    modoActual.current = nuevo;
    setModo(nuevo);
    void guardarTema(nuevo);
  }, []);

  const valor = useMemo<ValorTema>(
    () => ({
      modo,
      oscuro: modo === 'oscuro',
      colores: modo === 'oscuro' ? coloresOscuros : coloresClaros,
      alternar,
    }),
    [modo, alternar],
  );

  return <TemaContext.Provider value={valor}>{children}</TemaContext.Provider>;
}
