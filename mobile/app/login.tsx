// Ingreso con correo y contraseña (M2.5), con el mismo diseño que el login de la web.
// Muestra los errores de la API (422 y 429).
import { Redirect } from 'expo-router';
import { useState } from 'react';
import type { ComponentProps } from 'react';
import {
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ErrorApi, mensajeDeError } from '../src/api/cliente';
import { Aviso } from '../src/componentes/Aviso';
import { Boton } from '../src/componentes/Boton';
import { AvisoServidorLento } from '../src/componentes/Cargando';
import { Icono } from '../src/componentes/Icono';
import type { NombreIcono } from '../src/componentes/Icono';
import { Logo } from '../src/componentes/Logo';
import { Tarjeta } from '../src/componentes/Tarjeta';
import { useSesion } from '../src/sesion/SesionContext';
import { medidas, useColores } from '../src/tema/colores';

const FORMATO_CORREO = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

interface Errores {
  email?: string;
  password?: string;
  general?: string;
}

/** Validación local antes de llamar a la API. */
function validarIngreso(email: string, password: string): Errores {
  const errores: Errores = {};
  if (!email.trim()) errores.email = 'Ingresá tu correo.';
  else if (!FORMATO_CORREO.test(email.trim())) errores.email = 'Ingresá un correo válido.';
  if (!password) errores.password = 'Ingresá tu contraseña.';
  return errores;
}

export default function Login() {
  const c = useColores();
  const { usuario, iniciarSesion } = useSesion();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [verClave, setVerClave] = useState(false);
  const [errores, setErrores] = useState<Errores>({});
  const [enviando, setEnviando] = useState(false);

  if (usuario) return <Redirect href="/" />;

  async function ingresar() {
    const locales = validarIngreso(email, password);
    setErrores(locales);
    if (locales.email || locales.password) return;

    setEnviando(true);
    try {
      await iniciarSesion(email.trim(), password);
    } catch (error) {
      if (error instanceof ErrorApi && error.status === 422 && Object.keys(error.errors).length) {
        // Errores de validación: debajo de cada campo.
        setErrores({
          email: error.errorDe('email'),
          password: error.errorDe('password'),
          general: error.errorDe('email') || error.errorDe('password') ? undefined : error.message,
        });
      } else {
        // 429, sin conexión, servidor caído, etc.
        setErrores({ general: mensajeDeError(error) });
      }
      setEnviando(false);
    }
  }

  return (
    <SafeAreaView style={[estilos.pantalla, { backgroundColor: c.fondo }]}>
      <KeyboardAvoidingView
        style={estilos.pantalla}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      >
        <ScrollView contentContainerStyle={estilos.contenido} keyboardShouldPersistTaps="handled">
          <Tarjeta estilo={estilos.tarjeta}>
            <View style={estilos.marca}>
              <Logo tamano={38} />
              <Text style={[estilos.marcaNombre, { color: c.tinta }]}>EduControl</Text>
            </View>

            <View style={estilos.titulos}>
              <Text accessibilityRole="header" style={[estilos.titulo, { color: c.tinta }]}>
                Ingresar
              </Text>
              <Text style={[estilos.subtitulo, { color: c.tenue }]}>
                Usá la cuenta que te dio la institución.
              </Text>
            </View>

            {errores.general ? <Aviso tipo="error" texto={errores.general} /> : null}

            <Campo
              etiqueta="Correo electrónico"
              idEtiqueta="etiqueta-correo"
              icono="email-outline"
              error={errores.email}
              value={email}
              onChangeText={setEmail}
              placeholder="nombre@educontrol.test"
              autoCapitalize="none"
              autoCorrect={false}
              autoComplete="email"
              keyboardType="email-address"
              textContentType="username"
              editable={!enviando}
            />

            <Campo
              etiqueta="Contraseña"
              idEtiqueta="etiqueta-clave"
              icono="lock-outline"
              error={errores.password}
              value={password}
              onChangeText={setPassword}
              secureTextEntry={!verClave}
              autoCapitalize="none"
              autoComplete="current-password"
              textContentType="password"
              editable={!enviando}
              onSubmitEditing={ingresar}
              accesorio={
                <Pressable
                  onPress={() => setVerClave((v) => !v)}
                  accessibilityRole="button"
                  accessibilityLabel={verClave ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                  style={({ pressed }) => [
                    estilos.ojo,
                    { borderColor: c.secundario },
                    pressed && { backgroundColor: c.neutroFondo },
                  ]}
                >
                  <Icono
                    nombre={verClave ? 'eye-off-outline' : 'eye-outline'}
                    color={c.secundario}
                    tamano={20}
                  />
                </Pressable>
              }
            />

            <Boton
              titulo="Ingresar"
              onPress={ingresar}
              cargando={enviando}
              estilo={estilos.boton}
            />
            <AvisoServidorLento />

            <Text style={[estilos.pie, { color: c.tenue }]}>
              ¿Olvidaste tu contraseña? Comunicate con la administración de la escuela.
            </Text>
          </Tarjeta>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

type PropsCampo = ComponentProps<typeof TextInput> & {
  etiqueta: string;
  idEtiqueta: string;
  icono: NombreIcono;
  error?: string;
  /** Botón a la derecha del campo (el ojo de la contraseña). */
  accesorio?: React.ReactNode;
};

/** Campo con etiqueta y un ícono a la izquierda, como los input-group de Bootstrap. */
function Campo({ etiqueta, idEtiqueta, icono, error, accesorio, ...props }: PropsCampo) {
  const c = useColores();
  const [enfocado, setEnfocado] = useState(false);
  const colorBorde = error ? c.peligro : enfocado ? c.primario : c.borde;

  return (
    <View style={estilos.grupo}>
      <Text style={[estilos.etiqueta, { color: c.tinta }]} nativeID={idEtiqueta}>
        {etiqueta}
      </Text>
      <View style={estilos.filaCampo}>
        <View
          style={[
            estilos.campo,
            { borderColor: colorBorde, backgroundColor: c.superficie },
            accesorio ? estilos.campoConAccesorio : null,
            enfocado && { boxShadow: `0px 0px 0px 3px ${c.primarioSuave}` },
          ]}
        >
          <View style={[estilos.prefijo, { borderRightColor: c.borde }]}>
            <Icono nombre={icono} color={c.tenue} tamano={18} />
          </View>
          <TextInput
            {...props}
            accessibilityLabel={etiqueta}
            accessibilityLabelledBy={idEtiqueta}
            placeholderTextColor={c.tenue}
            onFocus={() => setEnfocado(true)}
            onBlur={() => setEnfocado(false)}
            style={[estilos.entrada, { color: c.tinta }]}
          />
        </View>
        {accesorio}
      </View>
      {error ? (
        <Text accessibilityRole="alert" style={[estilos.error, { color: c.peligro }]}>
          {error}
        </Text>
      ) : null}
    </View>
  );
}

const estilos = StyleSheet.create({
  pantalla: { flex: 1 },
  contenido: { flexGrow: 1, justifyContent: 'center', padding: 20 },
  tarjeta: { width: '100%', maxWidth: 440, alignSelf: 'center', padding: 24, gap: 16 },
  marca: { alignItems: 'center', gap: 8, marginBottom: 4 },
  marcaNombre: { fontSize: 22, fontWeight: '700', letterSpacing: -0.4 },
  titulos: { gap: 4 },
  titulo: { fontSize: 22, fontWeight: '700' },
  subtitulo: { fontSize: 16 },
  grupo: { gap: 6 },
  etiqueta: { fontSize: 14, fontWeight: '600' },
  filaCampo: { flexDirection: 'row', gap: 0 },
  campo: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'stretch',
    minHeight: medidas.areaTactil,
    borderWidth: 1,
    borderRadius: 9,
    overflow: 'hidden',
  },
  prefijo: {
    width: 42,
    alignItems: 'center',
    justifyContent: 'center',
    borderRightWidth: 1,
  },
  campoConAccesorio: { borderTopRightRadius: 0, borderBottomRightRadius: 0 },
  entrada: {
    flex: 1,
    minWidth: 0,
    paddingHorizontal: 12,
    fontSize: 16,
    // En el navegador se saca el contorno por defecto: el foco se ve en el borde del grupo.
    ...(Platform.OS === 'web' ? { outlineWidth: 0 } : null),
  },
  ojo: {
    width: medidas.areaTactil + 4,
    marginLeft: -1,
    borderWidth: 1,
    borderTopRightRadius: 9,
    borderBottomRightRadius: 9,
    alignItems: 'center',
    justifyContent: 'center',
  },
  boton: { marginTop: 4 },
  error: { fontSize: 14 },
  pie: { fontSize: 14, lineHeight: 20 },
});
