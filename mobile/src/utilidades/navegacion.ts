// Botón "Volver": a la pantalla anterior o, si no hay historial (por ejemplo se entró por URL
// en el navegador o desde un enlace), a la pantalla indicada.
import { router } from 'expo-router';
import type { Href } from 'expo-router';

export function volverA(destino: Href) {
  if (router.canGoBack()) router.back();
  else router.replace(destino);
}
