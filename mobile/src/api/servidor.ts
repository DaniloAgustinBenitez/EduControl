// Chequeo de salud de Laravel (/up). Se llama al abrir la app para ir despertando a Render.
import { URL_SERVIDOR } from './cliente';

/** Dispara un GET a /up sin esperar la respuesta ni mostrar errores. */
export function despertarServidor(): void {
  fetch(`${URL_SERVIDOR}/up`, { headers: { Accept: 'application/json' } }).catch(() => {
    // Si falla no importa: es solo para que el servidor se vaya despertando.
  });
}
