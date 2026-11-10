// Aviso para administradores y administrativos: su trabajo se hace desde la web.
import { Redirect } from 'expo-router';
import { Linking, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { Boton } from '../src/componentes/Boton';
import { Icono } from '../src/componentes/Icono';
import { Logo } from '../src/componentes/Logo';
import { Tarjeta } from '../src/componentes/Tarjeta';
import { useSesion } from '../src/sesion/SesionContext';
import { useColores } from '../src/tema/colores';

const URL_WEB = 'https://educontrol-5btq.onrender.com';

export default function UsarWeb() {
  const c = useColores();
  const { usuario, cerrarSesion } = useSesion();

  if (!usuario) return <Redirect href="/login" />;

  return (
    <SafeAreaView style={[estilos.pantalla, { backgroundColor: c.fondo }]}>
      <ScrollView contentContainerStyle={estilos.contenido}>
        <Tarjeta estilo={estilos.tarjeta}>
          <View style={estilos.marca}>
            <Logo tamano={38} />
            <Text style={[estilos.marcaNombre, { color: c.tinta }]}>EduControl</Text>
          </View>

          <View style={[estilos.icono, { backgroundColor: c.primarioSuave }]}>
            <Icono nombre="monitor" color={c.primario} tamano={28} />
          </View>

          <Text accessibilityRole="header" style={[estilos.titulo, { color: c.tinta }]}>
            Usá la versión web
          </Text>
          <Text style={[estilos.texto, { color: c.texto }]}>
            Hola, {usuario.nombre}. Tu perfil ({usuario.rol_etiqueta}) se gestiona desde la web de
            EduControl: la configuración institucional, las personas y los reportes están ahí.
          </Text>
          <Text style={[estilos.chico, { color: c.tenue }]}>
            La app es para tutores y docentes.
          </Text>

          <View style={estilos.acciones}>
            <Boton
              titulo="Abrir EduControl web"
              icono="open-in-new"
              pista="Abre la web en el navegador"
              onPress={() => Linking.openURL(URL_WEB)}
            />
            <Boton
              titulo="Cerrar sesión"
              icono="logout"
              variante="contorno"
              onPress={cerrarSesion}
            />
          </View>
        </Tarjeta>
      </ScrollView>
    </SafeAreaView>
  );
}

const estilos = StyleSheet.create({
  pantalla: { flex: 1 },
  contenido: { flexGrow: 1, justifyContent: 'center', padding: 20 },
  tarjeta: { width: '100%', maxWidth: 440, alignSelf: 'center', padding: 24, gap: 14 },
  marca: { alignItems: 'center', gap: 8, marginBottom: 8 },
  marcaNombre: { fontSize: 22, fontWeight: '700', letterSpacing: -0.4 },
  icono: {
    width: 52,
    height: 52,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  titulo: { fontSize: 22, fontWeight: '700' },
  texto: { fontSize: 16, lineHeight: 23 },
  chico: { fontSize: 14 },
  acciones: { gap: 10, marginTop: 6 },
});
