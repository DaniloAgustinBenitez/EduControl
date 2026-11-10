import {
  alRecibirNoAutorizado,
  ErrorApi,
  establecerToken,
  MENSAJE_SIN_CONEXION,
  MENSAJE_TIEMPO_AGOTADO,
  mensajeDeError,
  pedir,
  suscribirLentitud,
  TIEMPO_MAXIMO_MS,
  UMBRAL_LENTO_MS,
  URL_API,
  URL_SERVIDOR,
} from '../src/api/cliente';
import { despertarServidor } from '../src/api/servidor';
import { inscribir } from '../src/api/tutor';
import { respuesta } from './ayudantes';

function fetchSimulado() {
  return globalThis.fetch as jest.Mock;
}

describe('cliente HTTP', () => {
  it('arma la URL y manda Accept y el token Bearer', async () => {
    fetchSimulado().mockResolvedValueOnce(respuesta(200, { hijos: [] }));
    establecerToken('1|abc');

    await expect(pedir('GET', '/hijos')).resolves.toEqual({ hijos: [] });

    const [url, opciones] = fetchSimulado().mock.calls[0];
    expect(url).toBe(`${URL_API}/hijos`);
    expect(opciones.method).toBe('GET');
    expect(opciones.headers).toEqual({ Accept: 'application/json', Authorization: 'Bearer 1|abc' });
  });

  it('sin token no manda Authorization, y el cuerpo va en JSON', async () => {
    fetchSimulado().mockResolvedValueOnce(
      respuesta(201, { message: 'Inscripción registrada.', estado: {} }),
    );

    await inscribir(1, 'transporte', { recorrido_id: 2 });

    const [url, opciones] = fetchSimulado().mock.calls[0];
    expect(url).toBe(`${URL_API}/hijos/1/inscripciones/transporte`);
    expect(opciones.headers.Authorization).toBeUndefined();
    expect(opciones.headers['Content-Type']).toBe('application/json');
    expect(JSON.parse(opciones.body)).toEqual({ recorrido_id: 2 });
  });

  it('un 204 sin cuerpo devuelve null', async () => {
    fetchSimulado().mockResolvedValueOnce(respuesta(204));
    await expect(pedir('POST', '/logout')).resolves.toBeNull();
  });

  it('un 401 avisa para cerrar la sesión y lanza el mensaje de la API', async () => {
    const alNoAutorizado = jest.fn();
    alRecibirNoAutorizado(alNoAutorizado);
    fetchSimulado().mockResolvedValueOnce(
      respuesta(401, { message: 'Tu sesión venció. Volvé a ingresar.' }),
    );

    const error = (await pedir('GET', '/perfil').catch((e) => e)) as ErrorApi;

    expect(error).toBeInstanceOf(ErrorApi);
    expect(error.status).toBe(401);
    expect(error.message).toBe('Tu sesión venció. Volvé a ingresar.');
    expect(alNoAutorizado).toHaveBeenCalledTimes(1);
  });

  it('un 422 de regla de negocio trae el mensaje y la regla', async () => {
    const mensaje = 'Básquet se superpone con Fútbol (Martes 17:00 a 18:30).';
    fetchSimulado().mockResolvedValueOnce(respuesta(422, { message: mensaje, regla: 'RN-04' }));

    const error = (await inscribir(1, 'deporte', { deporte_id: 3 }).catch((e) => e)) as ErrorApi;

    expect(error.status).toBe(422);
    expect(error.regla).toBe('RN-04');
    expect(error.message).toBe(mensaje);
    expect(mensajeDeError(error)).toBe(mensaje);
  });

  it('un 422 de validación trae los errores por campo', async () => {
    fetchSimulado().mockResolvedValueOnce(
      respuesta(422, {
        message: 'El correo o la contraseña no son correctos.',
        errors: { email: ['El correo o la contraseña no son correctos.'] },
      }),
    );

    const error = (await pedir('POST', '/login', {}).catch((e) => e)) as ErrorApi;

    expect(error.regla).toBeNull();
    expect(error.errorDe('email')).toBe('El correo o la contraseña no son correctos.');
    expect(error.errorDe('password')).toBeUndefined();
  });

  it('si la respuesta de error no trae mensaje usa uno en español', async () => {
    fetchSimulado().mockResolvedValueOnce(respuesta(500));
    const error = (await pedir('GET', '/hijos').catch((e) => e)) as ErrorApi;
    expect(error.status).toBe(500);
    expect(error.message).toMatch(/servidor tuvo un problema/);

    fetchSimulado().mockResolvedValueOnce(respuesta(404));
    const noEncontrado = (await pedir('GET', '/hijos/99').catch((e) => e)) as ErrorApi;
    expect(noEncontrado.message).toBe('No se encontró lo que buscabas.');
  });

  it('sin conexión lanza un error claro con status 0', async () => {
    fetchSimulado().mockRejectedValueOnce(new TypeError('Network request failed'));

    const error = (await pedir('GET', '/hijos').catch((e) => e)) as ErrorApi;

    expect(error.status).toBe(0);
    expect(error.message).toBe(MENSAJE_SIN_CONEXION);
  });

  it('mensajeDeError da un texto genérico para errores desconocidos', () => {
    expect(mensajeDeError(new Error('x'))).toMatch(/error inesperado/);
  });

  describe('con el reloj simulado', () => {
    beforeEach(() => jest.useFakeTimers());
    afterEach(() => jest.useRealTimers());

    /** fetch que nunca responde, salvo que se cancele el pedido. */
    function fetchQueNoResponde() {
      fetchSimulado().mockImplementation(
        (_url: string, opciones: RequestInit) =>
          new Promise((_resolver, rechazar) => {
            opciones.signal?.addEventListener('abort', () =>
              rechazar(new DOMException('Aborted', 'AbortError')),
            );
          }),
      );
    }

    it('corta el pedido a los 70 s con un mensaje de tiempo agotado', async () => {
      fetchQueNoResponde();
      const promesa = pedir('GET', '/hijos').catch((e) => e);

      await jest.advanceTimersByTimeAsync(TIEMPO_MAXIMO_MS);
      const error = (await promesa) as ErrorApi;

      expect(error).toBeInstanceOf(ErrorApi);
      expect(error.status).toBe(0);
      expect(error.message).toBe(MENSAJE_TIEMPO_AGOTADO);
    });

    it('avisa cuando un pedido tarda más de 4 s y cuando termina', async () => {
      const cambios: boolean[] = [];
      const desuscribir = suscribirLentitud((lento) => cambios.push(lento));
      let responder!: (r: Response) => void;
      fetchSimulado().mockImplementation(
        () =>
          new Promise<Response>((r) => {
            responder = r;
          }),
      );

      const promesa = pedir('GET', '/hijos');
      await jest.advanceTimersByTimeAsync(UMBRAL_LENTO_MS - 1);
      expect(cambios).toEqual([]);

      await jest.advanceTimersByTimeAsync(1);
      expect(cambios).toEqual([true]);

      responder(respuesta(200, { hijos: [] }));
      await promesa;
      expect(cambios).toEqual([true, false]);
      desuscribir();
    });
  });
});

describe('despertarServidor', () => {
  it('hace un GET a /up sin /api/v1 y no falla si no hay conexión', async () => {
    fetchSimulado().mockRejectedValueOnce(new TypeError('Network request failed'));

    expect(() => despertarServidor()).not.toThrow();

    expect(URL_SERVIDOR).not.toMatch(/\/api\/v1$/);
    expect(fetchSimulado().mock.calls[0][0]).toBe(`${URL_SERVIDOR}/up`);
  });
});
