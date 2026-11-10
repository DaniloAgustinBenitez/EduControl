// Preparación común de los tests: módulos nativos simulados y fetch limpio en cada test.
import { alRecibirNoAutorizado, establecerToken } from './src/api/cliente';

// SecureStore en memoria.
jest.mock('expo-secure-store', () => {
  const almacen = new Map<string, string>();
  return {
    __almacen: almacen,
    getItemAsync: jest.fn(async (clave: string) => almacen.get(clave) ?? null),
    setItemAsync: jest.fn(async (clave: string, valor: string) => {
      almacen.set(clave, valor);
    }),
    deleteItemAsync: jest.fn(async (clave: string) => {
      almacen.delete(clave);
    }),
  };
});

jest.mock('expo-device', () => ({ modelName: 'Pixel 7' }));

// Íconos: un View vacío con el nombre, para no depender de las fuentes.
jest.mock('@expo/vector-icons/MaterialCommunityIcons', () => {
  const React = jest.requireActual('react');
  const { View } = jest.requireActual('react-native');
  const Icono = ({ name }: { name: string }) =>
    React.createElement(View, { testID: `icono-${name}` });
  return { __esModule: true, default: Icono };
});

// Área segura simulada (la barra superior usa useSafeAreaInsets).
jest.mock(
  'react-native-safe-area-context',
  () => jest.requireActual('react-native-safe-area-context/jest/mock').default,
);

// Expo Router simulado: las pantallas se prueban sin el navegador real.
jest.mock('expo-router', () => {
  const React = jest.requireActual('react');
  const { Text } = jest.requireActual('react-native');
  const Stack = Object.assign(() => null, { Screen: () => null });
  return {
    router: {
      push: jest.fn(),
      back: jest.fn(),
      replace: jest.fn(),
      canGoBack: jest.fn(() => true),
    },
    useLocalSearchParams: jest.fn(() => ({})),
    useFocusEffect: (efecto: () => void) => React.useEffect(efecto, [efecto]),
    Redirect: ({ href }: { href: string }) => React.createElement(Text, null, `Redirige a ${href}`),
    Stack,
  };
});

beforeEach(() => {
  const SecureStore = jest.requireMock('expo-secure-store') as { __almacen: Map<string, string> };
  SecureStore.__almacen.clear();
  establecerToken(null);
  alRecibirNoAutorizado(null);
  globalThis.fetch = jest.fn(() => Promise.reject(new Error('fetch sin simular')));
});

afterEach(() => {
  jest.clearAllMocks();
});
