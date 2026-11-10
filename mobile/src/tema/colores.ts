// Paleta de EduControl, tomada de public/css/educontrol.css (modo claro y oscuro).
// El modo lo elige el usuario con el botón de la barra superior (ver TemaContext.tsx), como en la web.
import { createContext, useContext } from 'react';

export type ModoTema = 'claro' | 'oscuro';

/** Fondo y color de los íconos de cada servicio (.ec-acento-* de la web). */
export interface Acento {
  fondo: string;
  color: string;
}

export interface Colores {
  primario: string;
  primarioOscuro: string;
  primarioSuave: string;
  textoSobrePrimario: string;
  acento: string;
  tinta: string;
  texto: string;
  tenue: string;
  borde: string;
  fondo: string;
  superficie: string;
  /** Sombra suave de las tarjetas (--ec-sombra). */
  sombra: string;
  exito: string;
  exitoFondo: string;
  neutro: string;
  neutroFondo: string;
  peligro: string;
  peligroFondo: string;
  advertencia: string;
  advertenciaFondo: string;
  info: string;
  infoFondo: string;
  /** Botón "Volver" (btn-outline-secondary). */
  secundario: string;
  acentoAzul: Acento;
  acentoAmbar: Acento;
  acentoRosa: Acento;
  acentoVerde: Acento;
}

export const coloresClaros: Colores = {
  primario: '#1d4ed8',
  primarioOscuro: '#1e40af',
  primarioSuave: '#eff4ff',
  textoSobrePrimario: '#ffffff',
  acento: '#0d9488',
  tinta: '#0f172a',
  texto: '#334155',
  tenue: '#64748b',
  borde: '#e2e8f0',
  fondo: '#f6f7fb',
  superficie: '#ffffff',
  sombra: '0px 1px 2px rgba(15, 23, 42, 0.04), 0px 8px 24px -12px rgba(15, 23, 42, 0.12)',
  exito: '#047857',
  exitoFondo: '#ecfdf5',
  neutro: '#64748b',
  neutroFondo: '#f1f5f9',
  peligro: '#b91c1c',
  peligroFondo: '#fef2f2',
  advertencia: '#b45309',
  advertenciaFondo: '#fffbeb',
  info: '#1e40af',
  infoFondo: '#eff4ff',
  secundario: '#64748b',
  acentoAzul: { fondo: '#eff4ff', color: '#1d4ed8' },
  acentoAmbar: { fondo: '#fffbeb', color: '#b45309' },
  acentoRosa: { fondo: '#fdf2f8', color: '#be185d' },
  acentoVerde: { fondo: '#ecfdf5', color: '#047857' },
};

export const coloresOscuros: Colores = {
  primario: '#60a5fa',
  primarioOscuro: '#3b82f6',
  primarioSuave: '#1e293b',
  // Sobre el azul claro del modo oscuro se lee mejor un texto casi negro.
  textoSobrePrimario: '#0b1220',
  acento: '#2dd4bf',
  tinta: '#e2e8f0',
  texto: '#cbd5e1',
  tenue: '#94a3b8',
  borde: '#1e293b',
  fondo: '#0b1220',
  superficie: '#111a2e',
  sombra: '0px 1px 2px rgba(0, 0, 0, 0.3), 0px 8px 24px -12px rgba(0, 0, 0, 0.6)',
  exito: '#5eead4',
  exitoFondo: '#052e2b',
  neutro: '#94a3b8',
  neutroFondo: '#1e293b',
  peligro: '#fca5a5',
  peligroFondo: '#3b1219',
  advertencia: '#fcd34d',
  advertenciaFondo: '#3a2a0a',
  info: '#93c5fd',
  infoFondo: '#1e293b',
  secundario: '#94a3b8',
  // En oscuro la web deja el fondo #1e293b; los íconos se aclaran para que se lean.
  acentoAzul: { fondo: '#1e293b', color: '#60a5fa' },
  acentoAmbar: { fondo: '#1e293b', color: '#fbbf24' },
  acentoRosa: { fondo: '#1e293b', color: '#f472b6' },
  acentoVerde: { fondo: '#1e293b', color: '#34d399' },
};

export interface ValorTema {
  modo: ModoTema;
  oscuro: boolean;
  colores: Colores;
  /** Cambia entre claro y oscuro y guarda la elección. */
  alternar: () => void;
}

/** Valor por defecto (modo claro, como la web) para pantallas que se usan sin el proveedor. */
const temaPorDefecto: ValorTema = {
  modo: 'claro',
  oscuro: false,
  colores: coloresClaros,
  alternar: () => {},
};

export const TemaContext = createContext<ValorTema>(temaPorDefecto);

export function useTema(): ValorTema {
  return useContext(TemaContext);
}

/** Colores del modo elegido (claro u oscuro). */
export function useColores(): Colores {
  return useContext(TemaContext).colores;
}

/** Medidas compartidas. 44 es el mínimo recomendado para un área táctil. */
export const medidas = {
  radio: 12,
  espacio: 16,
  areaTactil: 44,
};

/** Agrega transparencia a un color #rrggbb (por ejemplo para los bordes de los avisos). */
export function conAlfa(color: string, alfa: number): string {
  const hex = Math.round(Math.max(0, Math.min(1, alfa)) * 255)
    .toString(16)
    .padStart(2, '0');
  return /^#[0-9a-f]{6}$/i.test(color) ? `${color}${hex}` : color;
}
