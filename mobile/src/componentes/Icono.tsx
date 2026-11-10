// Íconos de la app (MaterialCommunityIcons), equivalentes a los Bootstrap Icons de la web.
import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
import type { ComponentProps } from 'react';
import type { StyleProp, TextStyle } from 'react-native';

export type NombreIcono = ComponentProps<typeof MaterialCommunityIcons>['name'];

interface Props {
  nombre: NombreIcono;
  color: string;
  tamano?: number;
  estilo?: StyleProp<TextStyle>;
}

/** Ícono decorativo: el lector de pantalla lo saltea (el texto de al lado ya lo explica). */
export function Icono({ nombre, color, tamano = 18, estilo }: Props) {
  return (
    <MaterialCommunityIcons
      name={nombre}
      color={color}
      size={tamano}
      style={estilo}
      accessibilityElementsHidden
      importantForAccessibility="no-hide-descendants"
      aria-hidden
    />
  );
}

/** Íconos de cada servicio, como en la web (bi-book, bi-cup-hot, bi-bus-front, bi-trophy). */
export const ICONOS_SERVICIO = {
  cursado: 'book-open-outline',
  comedor: 'coffee-outline',
  transporte: 'bus',
  deportes: 'trophy-outline',
} as const satisfies Record<string, NombreIcono>;
