// Tipos de los objetos JSON de la API v1. Contrato completo en docs/api.md.

export type Rol = 'administrador' | 'administrativo' | 'docente' | 'tutor';

export interface Usuario {
  id: number;
  nombre: string;
  email: string;
  rol: Rol;
  rol_etiqueta: string;
  permisos: string[];
  /** true solo para tutor y docente: al resto la app le pide que use la web. */
  usa_app: boolean;
}

export interface Ciclo {
  id: number;
  anio: number;
}

export interface Horario {
  /** 1 (lunes) a 7 (domingo). */
  dia: number;
  dia_nombre: string;
  /** Formato HH:MM. */
  inicio: string;
  fin: string;
  descripcion: string;
}

export interface Curso {
  id: number;
  nombre: string;
  nivel: string;
  nombre_completo: string;
}

export interface Deporte {
  id: number;
  nombre: string;
  descripcion: string | null;
  horarios: Horario[];
}

export interface Recorrido {
  id: number;
  nombre: string;
  descripcion: string | null;
}

/** Inscripciones de un alumno en el ciclo actual. El id de cada una se usa para la baja. */
export interface Estado {
  cursado: { id: number; curso: Curso } | null;
  comedor: { id: number } | null;
  transporte: { id: number; recorrido: Recorrido } | null;
  deportes: { id: number; deporte: Deporte }[];
}

export interface AlumnoResumen {
  id: number;
  legajo: string;
  nombre: string;
  apellido: string;
  nombre_completo: string;
  curso: Curso | null;
  /** null si no hay ciclo lectivo activo. */
  estado: Estado | null;
}

export interface AlumnoDetalle extends AlumnoResumen {
  horarios_cursado: Horario[];
}

export type Servicio = 'cursado' | 'comedor' | 'transporte' | 'deporte';

export interface Materia {
  id: number;
  nombre: string;
}

export interface CursoDocente extends Curso {
  horarios: Horario[];
  materias: Materia[];
}

export interface AlumnoNomina {
  id: number;
  legajo: string;
  dni: string;
  nombre: string;
  apellido: string;
  nombre_completo: string;
}

// ---------- Respuestas de cada endpoint ----------

export interface RespuestaLogin {
  token: string;
  usuario: Usuario;
}

export interface RespuestaPerfil {
  usuario: Usuario;
}

export interface RespuestaHijos {
  ciclo: Ciclo | null;
  hijos: AlumnoResumen[];
}

export interface RespuestaHijo {
  ciclo: Ciclo | null;
  alumno: AlumnoDetalle;
}

export interface RespuestaCatalogo {
  ciclo: Ciclo | null;
  max_deportes: number;
  deportes: Deporte[];
  recorridos: Recorrido[];
}

export interface RespuestaInscripcion {
  message: string;
  estado: Estado;
}

export interface RespuestaCursosDocente {
  cursos: CursoDocente[];
}

export interface RespuestaCursoDocente {
  curso: Curso & { horarios: Horario[] };
  materias: Materia[];
  alumnos: AlumnoNomina[];
}

/** Cuerpo de cualquier respuesta de error (401, 403, 404, 422, 429). */
export interface CuerpoError {
  message?: string;
  errors?: Record<string, string[]>;
  regla?: string | null;
}
