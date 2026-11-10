// true mientras haya algún pedido a la API que tarde más de 4 s (servidor dormido, RNF-13).
import { useEffect, useState } from 'react';

import { hayPedidosLentos, suscribirLentitud } from '../api/cliente';

export function useServidorLento(): boolean {
  const [lento, setLento] = useState(hayPedidosLentos);
  useEffect(() => suscribirLentitud(setLento), []);
  return lento;
}
