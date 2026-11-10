// Punto de entrada: espera la sesión guardada y redirige según el rol.
import { Redirect } from 'expo-router';

import { Cargando } from '../src/componentes/Cargando';
import { rutaInicial } from '../src/sesion/rutas';
import { useSesion } from '../src/sesion/SesionContext';

export default function Inicio() {
  const { cargando, usuario } = useSesion();
  if (cargando) return <Cargando />;
  return <Redirect href={rutaInicial(usuario)} />;
}
