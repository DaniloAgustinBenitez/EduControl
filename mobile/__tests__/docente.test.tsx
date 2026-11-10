import { render, screen, userEvent } from '@testing-library/react-native';
import { router, useLocalSearchParams } from 'expo-router';

import DetalleCurso from '../app/(docente)/cursos/[id]';
import MisCursos from '../app/(docente)/cursos/index';
import type { AlumnoNomina } from '../src/api/tipos';
import { filtrarNomina, normalizar, ordenarNomina } from '../src/utilidades/nomina';
import { curso3A, respuesta, simularApi } from './ayudantes';

const horarioLunes = {
  dia: 1,
  dia_nombre: 'Lunes',
  inicio: '08:00',
  fin: '12:00',
  descripcion: 'Lunes 08:00 a 12:00',
};

function alumnoNomina(id: number, nombre: string, apellido: string, dni: string): AlumnoNomina {
  return {
    id,
    legajo: `A-000${id}`,
    dni,
    nombre,
    apellido,
    nombre_completo: `${apellido}, ${nombre}`,
  };
}

const nomina = [
  alumnoNomina(3, 'Tomás', 'Pérez', '45333444'),
  alumnoNomina(1, 'Sofía', 'Pérez', '45111222'),
  alumnoNomina(2, 'Juan', 'Álvarez', '46222333'),
];

describe('Mis cursos', () => {
  it('muestra cada curso con nivel, materias y horarios', async () => {
    simularApi({
      'GET /docente/cursos': respuesta(200, {
        cursos: [
          {
            ...curso3A,
            horarios: [horarioLunes],
            materias: [
              { id: 12, nombre: 'Matemática' },
              { id: 13, nombre: 'Lengua' },
            ],
          },
          {
            id: 6,
            nombre: '1° B',
            nivel: 'Secundario',
            nombre_completo: '1° B — Secundario',
            horarios: [],
            materias: [],
          },
        ],
      }),
    });

    await render(<MisCursos />);

    expect(await screen.findByText('3° A')).toBeOnTheScreen();
    expect(screen.getByText('Primario')).toBeOnTheScreen();
    expect(screen.getByText('Matemática')).toBeOnTheScreen();
    expect(screen.getByText('Lengua')).toBeOnTheScreen();
    expect(screen.getByText('Lunes 08:00 a 12:00')).toBeOnTheScreen();
    expect(screen.getByText('Sin materias asignadas en este curso')).toBeOnTheScreen();
    expect(screen.getByText('Sin horario de cursado')).toBeOnTheScreen();

    await userEvent
      .setup()
      .press(screen.getByRole('button', { name: 'Ver alumnos de 3° A — Primario' }));
    expect(router.push).toHaveBeenCalledWith('/cursos/5');
  });

  it('avisa si el docente no tiene cursos', async () => {
    simularApi({ 'GET /docente/cursos': respuesta(200, { cursos: [] }) });

    await render(<MisCursos />);

    expect(await screen.findByText('Todavía no tenés cursos asignados')).toBeOnTheScreen();
  });

  it('si falla la carga muestra el mensaje', async () => {
    simularApi({
      'GET /docente/cursos': () => Promise.reject(new TypeError('Network request failed')),
    });

    await render(<MisCursos />);

    expect(await screen.findByText(/No se pudo conectar con el servidor/)).toBeOnTheScreen();
    expect(screen.getByRole('button', { name: 'Reintentar' })).toBeOnTheScreen();
  });
});

describe('Detalle del curso', () => {
  beforeEach(() => {
    jest.mocked(useLocalSearchParams).mockReturnValue({ id: '5' });
  });

  function simularCurso() {
    return simularApi({
      'GET /docente/cursos/5': respuesta(200, {
        curso: { ...curso3A, horarios: [horarioLunes] },
        materias: [{ id: 12, nombre: 'Matemática' }],
        alumnos: nomina,
      }),
    });
  }

  it('muestra horarios, materias y la nómina ordenada por apellido', async () => {
    simularCurso();

    await render(<DetalleCurso />);

    expect(await screen.findByText('3° A — Primario')).toBeOnTheScreen();
    expect(screen.getByText('Horario: Lunes 08:00 a 12:00')).toBeOnTheScreen();
    expect(screen.getByText('Matemática')).toBeOnTheScreen();
    expect(screen.getByText('3 alumnos')).toBeOnTheScreen();

    const nombres = screen.getAllByText(/^\S+, \S+$/).map((t) => t.props.children);
    expect(nombres).toEqual(['Álvarez, Juan', 'Pérez, Sofía', 'Pérez, Tomás']);
    expect(screen.getByText('Legajo A-0001 · DNI 45111222')).toBeOnTheScreen();
  });

  it('filtra la nómina por nombre, apellido, legajo o DNI', async () => {
    simularCurso();
    const user = userEvent.setup();
    await render(<DetalleCurso />);
    const buscador = await screen.findByLabelText('Buscar alumno');

    await user.type(buscador, 'perez');
    expect(screen.getByText('2 de 3 alumnos')).toBeOnTheScreen();
    expect(screen.queryByText('Álvarez, Juan')).not.toBeOnTheScreen();

    await user.clear(buscador);
    await user.type(buscador, '46222333');
    expect(screen.getByText('Álvarez, Juan')).toBeOnTheScreen();
    expect(screen.queryByText('Pérez, Sofía')).not.toBeOnTheScreen();

    await user.clear(buscador);
    await user.type(buscador, 'A-0003');
    expect(screen.getByText('Pérez, Tomás')).toBeOnTheScreen();

    await user.clear(buscador);
    await user.type(buscador, 'zzz');
    expect(screen.getByText('Ningún alumno coincide con la búsqueda.')).toBeOnTheScreen();
  });

  it('si el curso no es del docente (403) muestra el mensaje', async () => {
    simularApi({
      'GET /docente/cursos/5': respuesta(403, {
        message: 'Ese curso no está asignado a tu cuenta.',
      }),
    });

    await render(<DetalleCurso />);

    expect(await screen.findByText('Ese curso no está asignado a tu cuenta.')).toBeOnTheScreen();
    await userEvent.setup().press(screen.getByRole('button', { name: 'Volver' }));
    expect(router.back).toHaveBeenCalled();
  });
});

describe('utilidades de la nómina', () => {
  it('normaliza tildes y mayúsculas', () => {
    expect(normalizar('  PÉREZ Ñandú ')).toBe('perez nandu');
  });

  it('ordena por apellido y nombre sin modificar la lista original', () => {
    const ordenada = ordenarNomina(nomina);
    expect(ordenada.map((a) => a.id)).toEqual([2, 1, 3]);
    expect(nomina[0].id).toBe(3);
  });

  it('exige que aparezcan todas las palabras buscadas', () => {
    expect(filtrarNomina(nomina, 'sofia perez').map((a) => a.id)).toEqual([1]);
    expect(filtrarNomina(nomina, '   ')).toBe(nomina);
  });
});
