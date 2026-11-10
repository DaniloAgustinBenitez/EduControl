import { render, screen, userEvent, within } from '@testing-library/react-native';
import { router, useLocalSearchParams } from 'expo-router';
import { Alert, Platform } from 'react-native';
import type { AlertButton } from 'react-native';

import DetalleAlumno from '../app/(tutor)/hijos/[id]';
import MisHijos from '../app/(tutor)/hijos/index';
import type { Estado } from '../src/api/tipos';
import {
  alumno,
  alumnoDetalle,
  basquet,
  catalogo,
  ciclo2026,
  curso3A,
  estadoVacio,
  futbol,
  pedidosHechos,
  pendiente,
  recorridoNorte,
  respuesta,
  simularApi,
} from './ayudantes';

describe('Mis hijos', () => {
  it('muestra una tarjeta por hijo con el curso y el estado de los servicios', async () => {
    const estadoSofia: Estado = {
      cursado: { id: 10, curso: curso3A },
      comedor: null,
      transporte: { id: 3, recorrido: recorridoNorte },
      deportes: [{ id: 21, deporte: futbol }],
    };
    simularApi({
      'GET /hijos': respuesta(200, {
        ciclo: ciclo2026,
        hijos: [
          alumno({ estado: estadoSofia }),
          alumno({ id: 2, nombre: 'Tomás', legajo: 'A-0002', nombre_completo: 'Pérez, Tomás' }),
        ],
      }),
    });

    await render(<MisHijos />);

    expect(await screen.findByText('Sofía Pérez')).toBeOnTheScreen();
    expect(screen.getByText('Tomás Pérez')).toBeOnTheScreen();
    expect(screen.getByText('Inscripciones del ciclo lectivo 2026')).toBeOnTheScreen();
    expect(screen.getByText('Legajo A-0001 · 3° A — Primario')).toBeOnTheScreen();
    expect(screen.getByLabelText('Cursado: inscripto')).toBeOnTheScreen();
    expect(screen.getByLabelText('Transporte: inscripto, Recorrido Norte')).toBeOnTheScreen();
    expect(screen.getByLabelText('Deportes: inscripto, Fútbol')).toBeOnTheScreen();
    expect(screen.getAllByLabelText('Comedor: sin inscribir')).toHaveLength(2);

    await userEvent
      .setup()
      .press(screen.getByRole('button', { name: 'Gestionar inscripciones de Tomás Pérez' }));
    expect(router.push).toHaveBeenCalledWith('/hijos/2');
  });

  it('avisa si el tutor no tiene hijos vinculados', async () => {
    simularApi({ 'GET /hijos': respuesta(200, { ciclo: ciclo2026, hijos: [] }) });

    await render(<MisHijos />);

    expect(await screen.findByText('No tenés alumnos vinculados')).toBeOnTheScreen();
  });

  it('avisa si no hay un ciclo lectivo activo', async () => {
    simularApi({
      'GET /hijos': respuesta(200, { ciclo: null, hijos: [alumno({ estado: null })] }),
    });

    await render(<MisHijos />);

    expect(await screen.findByText(/no hay un ciclo lectivo activo/)).toBeOnTheScreen();
    expect(screen.queryByLabelText(/Cursado:/)).not.toBeOnTheScreen();
  });

  it('si falla la carga muestra el mensaje y permite reintentar', async () => {
    simularApi({
      'GET /hijos': [
        respuesta(403, { message: 'Tu perfil no tiene acceso a esta sección.' }),
        respuesta(200, { ciclo: ciclo2026, hijos: [alumno()] }),
      ],
    });

    await render(<MisHijos />);

    expect(await screen.findByText('Tu perfil no tiene acceso a esta sección.')).toBeOnTheScreen();
    await userEvent.setup().press(screen.getByRole('button', { name: 'Reintentar' }));
    expect(await screen.findByText('Sofía Pérez')).toBeOnTheScreen();
  });
});

describe('Detalle del alumno', () => {
  beforeEach(() => {
    jest.mocked(useLocalSearchParams).mockReturnValue({ id: '1' });
  });

  function simularDetalle(otras: Parameters<typeof simularApi>[0] = {}, estado = estadoVacio) {
    return simularApi({
      'GET /hijos/1': respuesta(200, { ciclo: ciclo2026, alumno: alumnoDetalle({ estado }) }),
      'GET /catalogo': respuesta(200, catalogo),
      ...otras,
    });
  }

  it('muestra el encabezado con el curso, el horario y el contador de deportes', async () => {
    simularDetalle();

    await render(<DetalleAlumno />);

    expect(await screen.findByText('Sofía Pérez')).toBeOnTheScreen();
    expect(screen.getByText('Legajo A-0001 · 3° A — Primario')).toBeOnTheScreen();
    expect(screen.getByText('Horario de cursado: Lunes 08:00 a 12:00')).toBeOnTheScreen();
    expect(screen.getByText('Cursado 2026')).toBeOnTheScreen();
    expect(screen.getByLabelText('0 de 2')).toBeOnTheScreen();
    expect(screen.getByText('Martes 17:00 a 18:30')).toBeOnTheScreen();
  });

  it('inscribe en un deporte y refresca con el estado de la respuesta', async () => {
    const api = simularDetalle({
      'POST /hijos/1/inscripciones/deporte': respuesta(201, {
        message: 'Inscripción registrada.',
        estado: { ...estadoVacio, deportes: [{ id: 21, deporte: futbol }] },
      }),
    });
    const user = userEvent.setup();
    await render(<DetalleAlumno />);

    // Hasta elegir un deporte, el botón está deshabilitado.
    expect(
      await screen.findByRole('button', { name: 'Inscribir en el deporte elegido' }),
    ).toBeDisabled();
    await user.press(screen.getByRole('radio', { name: 'Fútbol' }));
    expect(screen.getByRole('radio', { name: 'Fútbol' })).toBeChecked();
    await user.press(screen.getByRole('button', { name: 'Inscribir en Fútbol' }));

    expect(await screen.findByText('Inscripción registrada.')).toBeOnTheScreen();
    expect(screen.getByLabelText('1 de 2')).toBeOnTheScreen();
    expect(screen.getByRole('button', { name: 'Dar de baja de Fútbol' })).toBeOnTheScreen();
    expect(screen.queryByRole('radio', { name: 'Fútbol' })).not.toBeOnTheScreen();
    expect(JSON.parse(api.mock.calls[2][1].body)).toEqual({ deporte_id: futbol.id });
  });

  it('muestra el mensaje de la regla cuando la API rechaza la inscripción (RN-04)', async () => {
    const mensaje = 'Básquet se superpone con Fútbol (Martes 17:00 a 18:30).';
    simularDetalle(
      {
        'POST /hijos/1/inscripciones/deporte': respuesta(422, { message: mensaje, regla: 'RN-04' }),
      },
      { ...estadoVacio, deportes: [{ id: 21, deporte: futbol }] },
    );
    const user = userEvent.setup();
    await render(<DetalleAlumno />);

    await user.press(await screen.findByRole('radio', { name: basquet.nombre }));
    await user.press(screen.getByRole('button', { name: `Inscribir en ${basquet.nombre}` }));

    const aviso = await screen.findByRole('alert');
    expect(within(aviso).getByText(mensaje)).toBeOnTheScreen();
    expect(screen.getByLabelText('1 de 2')).toBeOnTheScreen();
    // La elección se mantiene para poder probar con otro deporte.
    expect(screen.getByRole('button', { name: 'Inscribir en Básquet' })).toBeEnabled();
  });

  it('pide confirmación y da de baja el comedor', async () => {
    const alerta = jest
      .spyOn(Alert, 'alert')
      .mockImplementation((_titulo, _mensaje, botones?: AlertButton[]) => {
        botones?.find((b) => b.style === 'destructive')?.onPress?.();
      });
    const api = simularDetalle(
      {
        'DELETE /hijos/1/inscripciones/comedor/7': respuesta(200, {
          message: 'Se dio de baja la inscripción.',
          estado: estadoVacio,
        }),
      },
      { ...estadoVacio, comedor: { id: 7 } },
    );
    await render(<DetalleAlumno />);

    await userEvent
      .setup()
      .press(await screen.findByRole('button', { name: 'Dar de baja del comedor' }));

    expect(alerta).toHaveBeenCalledWith(
      'Dar de baja',
      '¿Confirmás la baja de la inscripción al comedor?',
      expect.any(Array),
      expect.objectContaining({ cancelable: true }),
    );
    expect(await screen.findByText('Se dio de baja la inscripción.')).toBeOnTheScreen();
    expect(screen.getByRole('button', { name: 'Inscribir al comedor' })).toBeOnTheScreen();
    expect(pedidosHechos(api)).toContain('DELETE /hijos/1/inscripciones/comedor/7');
  });

  it('si se cancela la confirmación no da de baja', async () => {
    jest.spyOn(Alert, 'alert').mockImplementation(() => {});
    const api = simularDetalle({}, { ...estadoVacio, cursado: { id: 10, curso: curso3A } });
    await render(<DetalleAlumno />);

    await userEvent
      .setup()
      .press(await screen.findByRole('button', { name: 'Dar de baja del cursado' }));

    expect(pedidosHechos(api)).toEqual(['GET /hijos/1', 'GET /catalogo']);
  });

  it('inscribe al transporte con el recorrido elegido y deshabilita los botones mientras envía', async () => {
    const envio = pendiente<Response>();
    const api = simularDetalle({
      'POST /hijos/1/inscripciones/transporte': () => envio.promesa,
    });
    const user = userEvent.setup();
    await render(<DetalleAlumno />);

    const inscribirRecorrido = await screen.findByRole('button', {
      name: 'Inscribir al recorrido',
    });
    expect(inscribirRecorrido).toBeDisabled();

    await user.press(screen.getByRole('radio', { name: 'Recorrido Norte' }));
    expect(screen.getByRole('radio', { name: 'Recorrido Norte' })).toBeChecked();
    await user.press(inscribirRecorrido);

    expect(screen.getByRole('button', { name: 'Inscribir al comedor' })).toBeDisabled();
    expect(JSON.parse(api.mock.calls[2][1].body)).toEqual({ recorrido_id: recorridoNorte.id });

    envio.resolver(
      respuesta(201, {
        message: 'Inscripción registrada.',
        estado: { ...estadoVacio, transporte: { id: 3, recorrido: recorridoNorte } },
      }),
    );

    expect(await screen.findByText('Recorrido Recorrido Norte.')).toBeOnTheScreen();
    expect(screen.getByRole('button', { name: 'Inscribir al comedor' })).toBeEnabled();
  });

  it('con el máximo de deportes no ofrece inscribir otro', async () => {
    simularDetalle(
      {},
      {
        ...estadoVacio,
        deportes: [
          { id: 21, deporte: futbol },
          { id: 22, deporte: { ...basquet, id: 9, nombre: 'Natación' } },
        ],
      },
    );

    await render(<DetalleAlumno />);

    expect(await screen.findByText('Alcanzó el máximo de 2 deportes.')).toBeOnTheScreen();
    expect(screen.queryByRole('button', { name: /^Inscribir en/ })).not.toBeOnTheScreen();
    expect(screen.queryByRole('radio', { name: 'Vóley' })).not.toBeOnTheScreen();
  });

  it('sin ciclo activo avisa que las inscripciones están cerradas', async () => {
    simularApi({
      'GET /hijos/1': respuesta(200, { ciclo: null, alumno: alumnoDetalle({ estado: null }) }),
      'GET /catalogo': respuesta(200, { ...catalogo, ciclo: null }),
    });

    await render(<DetalleAlumno />);

    expect(await screen.findByText(/Las inscripciones están cerradas/)).toBeOnTheScreen();
    expect(screen.queryByText('Comedor')).not.toBeOnTheScreen();
  });

  it('si el alumno no es del tutor (403) muestra el mensaje y permite volver', async () => {
    const mensaje = 'Solo podés gestionar las inscripciones de los alumnos que tenés a cargo.';
    simularApi({
      'GET /hijos/1': respuesta(403, { message: mensaje }),
      'GET /catalogo': respuesta(200, catalogo),
    });

    await render(<DetalleAlumno />);

    expect(await screen.findByText(mensaje)).toBeOnTheScreen();
    await userEvent.setup().press(screen.getByRole('button', { name: 'Volver' }));
    expect(router.back).toHaveBeenCalled();
  });

  it('"Volver" va a Mis hijos si no hay pantalla anterior (se entró por URL)', async () => {
    jest.mocked(router.canGoBack).mockReturnValueOnce(false);
    simularDetalle();
    await render(<DetalleAlumno />);

    await userEvent.setup().press(await screen.findByRole('button', { name: 'Volver' }));

    expect(router.replace).toHaveBeenCalledWith('/hijos');
    expect(router.back).not.toHaveBeenCalled();
  });

  it('en el navegador confirma la baja con window.confirm (Alert no funciona en la web)', async () => {
    const plataforma = jest.replaceProperty(Platform, 'OS', 'web');
    const preguntar = jest.fn(() => true);
    const confirmOriginal = globalThis.confirm;
    globalThis.confirm = preguntar;
    const alerta = jest.spyOn(Alert, 'alert');
    const api = simularDetalle(
      {
        'DELETE /hijos/1/inscripciones/deporte/21': respuesta(200, {
          message: 'Se dio de baja la inscripción.',
          estado: estadoVacio,
        }),
      },
      { ...estadoVacio, deportes: [{ id: 21, deporte: futbol }] },
    );
    try {
      await render(<DetalleAlumno />);

      await userEvent
        .setup()
        .press(await screen.findByRole('button', { name: 'Dar de baja de Fútbol' }));

      expect(preguntar).toHaveBeenCalledWith('¿Confirmás la baja de Fútbol?');
      expect(alerta).not.toHaveBeenCalled();
      expect(await screen.findByText('Se dio de baja la inscripción.')).toBeOnTheScreen();
      expect(pedidosHechos(api)).toContain('DELETE /hijos/1/inscripciones/deporte/21');
    } finally {
      globalThis.confirm = confirmOriginal;
      plataforma.restore();
    }
  });
});
