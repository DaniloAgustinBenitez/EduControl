// Pregunta de confirmación (por ejemplo antes de dar de baja) que funciona en el celular y en la web.
// En react-native-web, Alert.alert con botones no muestra nada: ahí se usa window.confirm, como la web.
import { Alert, Platform } from 'react-native';

interface Opciones {
  titulo: string;
  mensaje: string;
  /** Texto del botón que confirma (en el celular). */
  textoConfirmar?: string;
}

/** Devuelve true si el usuario confirma. */
export function confirmar({
  titulo,
  mensaje,
  textoConfirmar = 'Aceptar',
}: Opciones): Promise<boolean> {
  if (Platform.OS === 'web') {
    const preguntar = (globalThis as { confirm?: (texto: string) => boolean }).confirm;
    return Promise.resolve(typeof preguntar === 'function' ? preguntar(mensaje) : false);
  }

  return new Promise((resolver) => {
    Alert.alert(
      titulo,
      mensaje,
      [
        { text: 'Cancelar', style: 'cancel', onPress: () => resolver(false) },
        { text: textoConfirmar, style: 'destructive', onPress: () => resolver(true) },
      ],
      { cancelable: true, onDismiss: () => resolver(false) },
    );
  });
}
