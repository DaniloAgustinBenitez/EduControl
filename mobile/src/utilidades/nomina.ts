// Orden y búsqueda de la nómina del curso (M4.2).
import type { AlumnoNomina } from '../api/tipos';

/** Minúsculas y sin tildes, para que "perez" encuentre a "Pérez". */
export function normalizar(texto: string): string {
  return texto.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();
}

/** Ordena por apellido y nombre, como la web. */
export function ordenarNomina(alumnos: AlumnoNomina[]): AlumnoNomina[] {
  return [...alumnos].sort(
    (a, b) =>
      a.apellido.localeCompare(b.apellido, 'es', { sensitivity: 'base' }) ||
      a.nombre.localeCompare(b.nombre, 'es', { sensitivity: 'base' }),
  );
}

/** Filtra por nombre, apellido, legajo o DNI. Cada palabra buscada tiene que aparecer. */
export function filtrarNomina(alumnos: AlumnoNomina[], busqueda: string): AlumnoNomina[] {
  const palabras = normalizar(busqueda).split(/\s+/).filter(Boolean);
  if (!palabras.length) return alumnos;
  return alumnos.filter((alumno) => {
    const texto = normalizar(`${alumno.nombre} ${alumno.apellido} ${alumno.legajo} ${alumno.dni}`);
    return palabras.every((palabra) => texto.includes(palabra));
  });
}
