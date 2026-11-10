// Mis cursos (M4.1): cursos del docente con nivel, horarios y las materias que dicta,
// con el mismo diseño que docente/index.blade.php.
import { router, useFocusEffect } from 'expo-router';
import { useCallback } from 'react';
import { FlatList, RefreshControl, StyleSheet, Text, View } from 'react-native';

import { obtenerCursosDocente } from '../../../src/api/docente';
import type { CursoDocente } from '../../../src/api/tipos';
import { Aviso } from '../../../src/componentes/Aviso';
import { Boton } from '../../../src/componentes/Boton';
import { Cargando } from '../../../src/componentes/Cargando';
import { Encabezado } from '../../../src/componentes/Encabezado';
import { EstadoVacio } from '../../../src/componentes/EstadoVacio';
import { Horarios } from '../../../src/componentes/Horarios';
import { ListaMaterias } from '../../../src/componentes/ListaMaterias';
import { Tarjeta } from '../../../src/componentes/Tarjeta';
import { useCarga } from '../../../src/hooks/useCarga';
import { medidas, useColores } from '../../../src/tema/colores';

function TarjetaCurso({ curso }: { curso: CursoDocente }) {
  const c = useColores();
  return (
    <Tarjeta>
      <View style={estilos.titulo}>
        <Text accessibilityRole="header" style={[estilos.nombre, { color: c.tinta }]}>
          {curso.nombre}
        </Text>
        <Text style={[estilos.nivel, { color: c.tenue }]}>{curso.nivel}</Text>
      </View>
      <ListaMaterias materias={curso.materias} vacio="Sin materias asignadas en este curso" />
      <Horarios horarios={curso.horarios} vacio="Sin horario de cursado" conIcono />
      <Boton
        titulo="Ver alumnos"
        icono="account-group"
        etiqueta={`Ver alumnos de ${curso.nombre_completo}`}
        variante="secundario"
        compacto
        onPress={() => router.push(`/cursos/${curso.id}`)}
      />
    </Tarjeta>
  );
}

export default function MisCursos() {
  const c = useColores();
  const { datos, error, cargando, refrescando, recargar } = useCarga(obtenerCursosDocente);

  useFocusEffect(
    useCallback(() => {
      void recargar();
    }, [recargar]),
  );

  if (cargando && !datos) return <Cargando />;

  if (!datos) {
    return (
      <View style={[estilos.centrado, { backgroundColor: c.fondo }]}>
        <EstadoVacio
          icono="alert-circle-outline"
          titulo="No se pudieron cargar tus cursos"
          texto={error?.message}
        >
          <Boton titulo="Reintentar" onPress={() => recargar()} />
        </EstadoVacio>
      </View>
    );
  }

  return (
    <FlatList
      style={{ backgroundColor: c.fondo }}
      contentContainerStyle={estilos.lista}
      data={datos.cursos}
      keyExtractor={(curso) => String(curso.id)}
      renderItem={({ item }) => <TarjetaCurso curso={item} />}
      refreshControl={
        <RefreshControl
          refreshing={refrescando}
          onRefresh={() => recargar({ refrescar: true })}
          colors={[c.primario]}
          tintColor={c.primario}
        />
      }
      ListHeaderComponent={
        <View style={estilos.encabezado}>
          <Encabezado
            titulo="Mis materias y cursos"
            subtitulo="Tus cursos con sus horarios y las materias que dictás."
          />
          {error ? <Aviso tipo="error" texto={error.message} /> : null}
        </View>
      }
      ListEmptyComponent={
        <EstadoVacio
          icono="book-open-outline"
          titulo="Todavía no tenés cursos asignados"
          texto="Si esperabas ver tus cursos, pedile a la administración que vincule tu cuenta a un profesor."
        />
      }
    />
  );
}

const estilos = StyleSheet.create({
  centrado: { flex: 1, justifyContent: 'center', padding: medidas.espacio },
  lista: { padding: medidas.espacio, gap: medidas.espacio, paddingBottom: 32 },
  encabezado: { gap: 12, marginBottom: 4 },
  titulo: { flexDirection: 'row', alignItems: 'baseline', flexWrap: 'wrap', gap: 8 },
  nombre: { fontSize: 20, fontWeight: '600' },
  nivel: { fontSize: 15 },
});
