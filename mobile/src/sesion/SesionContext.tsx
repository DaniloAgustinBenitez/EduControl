// Contexto de sesión: token y usuario, restaurados al abrir la app y validados con GET /perfil.
import * as Device from 'expo-device';
import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';
import type { ReactNode } from 'react';

import { login, logout, obtenerPerfil } from '../api/autenticacion';
import { alRecibirNoAutorizado, establecerToken } from '../api/cliente';
import type { Usuario } from '../api/tipos';
import { borrarSesion, guardarSesion, guardarUsuario, leerSesion } from './almacen';

interface ValorSesion {
  /** true mientras se lee la sesión guardada al abrir la app. */
  cargando: boolean;
  usuario: Usuario | null;
  iniciarSesion: (email: string, password: string) => Promise<void>;
  cerrarSesion: () => Promise<void>;
}

const SesionContext = createContext<ValorSesion | null>(null);

/** Nombre con el que se guarda el token en el servidor (máximo 100 caracteres). */
export function nombreDispositivo(): string {
  return (Device.modelName || 'app').slice(0, 100);
}

export function SesionProvider({ children }: { children: ReactNode }) {
  const [cargando, setCargando] = useState(true);
  const [usuario, setUsuario] = useState<Usuario | null>(null);
  // Cambia en cada ingreso o salida, para descartar respuestas viejas de /perfil.
  const generacion = useRef(0);

  const limpiarLocal = useCallback(async () => {
    generacion.current += 1;
    establecerToken(null);
    setUsuario(null);
    await borrarSesion();
  }, []);

  // Un 401 en cualquier pedido cierra la sesión y lleva al login.
  useEffect(() => {
    alRecibirNoAutorizado(() => {
      void limpiarLocal();
    });
    return () => alRecibirNoAutorizado(null);
  }, [limpiarLocal]);

  // Al abrir la app: restaurar la sesión guardada y validarla con /perfil.
  useEffect(() => {
    let activo = true;

    async function restaurar() {
      const guardada = await leerSesion();
      if (!activo) return;
      if (!guardada) {
        setCargando(false);
        return;
      }

      establecerToken(guardada.token);
      const miGeneracion = generacion.current;

      if (guardada.usuario) {
        // Se entra enseguida con el usuario guardado y se valida en segundo plano,
        // así un servidor dormido no deja la app trabada en la pantalla de carga.
        setUsuario(guardada.usuario);
        setCargando(false);
      }

      try {
        const { usuario: actual } = await obtenerPerfil();
        if (!activo || miGeneracion !== generacion.current) return;
        setUsuario(actual);
        await guardarUsuario(actual);
      } catch {
        // 401: ya lo resolvió alRecibirNoAutorizado. Sin conexión: se sigue con lo guardado.
        if (activo && !guardada.usuario && miGeneracion === generacion.current) {
          await limpiarLocal();
        }
      } finally {
        if (activo) setCargando(false);
      }
    }

    void restaurar();
    return () => {
      activo = false;
    };
  }, [limpiarLocal]);

  const iniciarSesion = useCallback(async (email: string, password: string) => {
    const respuesta = await login(email, password, nombreDispositivo());
    generacion.current += 1;
    establecerToken(respuesta.token);
    try {
      await guardarSesion(respuesta.token, respuesta.usuario);
    } catch {
      // Si no se puede guardar, la sesión dura hasta cerrar la app.
    }
    setUsuario(respuesta.usuario);
  }, []);

  const cerrarSesion = useCallback(async () => {
    try {
      await logout();
    } catch {
      // Aunque el servidor no responda, el token se borra del celular.
    } finally {
      await limpiarLocal();
    }
  }, [limpiarLocal]);

  const valor = useMemo(
    () => ({ cargando, usuario, iniciarSesion, cerrarSesion }),
    [cargando, usuario, iniciarSesion, cerrarSesion],
  );

  return <SesionContext.Provider value={valor}>{children}</SesionContext.Provider>;
}

export function useSesion(): ValorSesion {
  const valor = useContext(SesionContext);
  if (!valor) throw new Error('useSesion se usa dentro de <SesionProvider>.');
  return valor;
}
