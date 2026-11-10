// Carga datos de la API con estados de carga, error y "deslizar para actualizar".
import { useCallback, useEffect, useRef, useState } from 'react';

import { ErrorApi } from '../api/cliente';

interface ResultadoCarga<T> {
  datos: T | null;
  error: ErrorApi | null;
  /** Primera carga, sin datos todavía. */
  cargando: boolean;
  /** Recarga pedida al deslizar hacia abajo. */
  refrescando: boolean;
  /** Vuelve a pedir los datos. Con `refrescar` se muestra el indicador de la lista. */
  recargar: (opciones?: { refrescar?: boolean }) => Promise<void>;
}

export function useCarga<T>(funcion: () => Promise<T>): ResultadoCarga<T> {
  const [datos, setDatos] = useState<T | null>(null);
  const [error, setError] = useState<ErrorApi | null>(null);
  const [cargando, setCargando] = useState(true);
  const [refrescando, setRefrescando] = useState(false);
  const funcionActual = useRef(funcion);
  const montado = useRef(true);

  useEffect(() => {
    funcionActual.current = funcion;
  });

  useEffect(() => {
    montado.current = true;
    return () => {
      montado.current = false;
    };
  }, []);

  const recargar = useCallback(async (opciones?: { refrescar?: boolean }) => {
    if (opciones?.refrescar) setRefrescando(true);
    try {
      const resultado = await funcionActual.current();
      if (!montado.current) return;
      setDatos(resultado);
      setError(null);
    } catch (e) {
      if (!montado.current) return;
      setError(e instanceof ErrorApi ? e : new ErrorApi(0, 'Ocurrió un error inesperado.'));
    } finally {
      if (montado.current) {
        setCargando(false);
        setRefrescando(false);
      }
    }
  }, []);

  return { datos, error, cargando, refrescando, recargar };
}
