// Detalle del curso (M4.2): horarios, materias del docente y nómina con buscador,
// con el mismo diseño que docente/curso.blade.php (la tabla se muestra como lista en una tarjeta).
import { useLocalSearchParams } from 'expo-router';
import { useCallback, useEffect, useMemo, useState } from 'react';
import {
  FlatList,
  Platform,
  RefreshControl,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';

import { obtenerCursoDocente } from '../../../src/api/docente';
import type { AlumnoNomina } from '../../../src/api/tipos';
import { Aviso } from '../../../src/componentes/Aviso';
import { Boton } from '../../../src/componentes/Boton';
import { Cargando } from '../../../src/componentes/Cargando';
import { Encabezado } from '../../../src/componentes/Encabezado';
import { EstadoVacio } from '../../../src/componentes/EstadoVacio';
import { Horarios } from '../../../src/componentes/Horarios';
import { Icono } from '../../../src/componentes/Icono';
import { ListaMaterias } from '../../../src/componentes/ListaMaterias';
import { useCarga } from '../../../src/hooks/useCarga';
import { medidas, useColores } from '../../../src/tema/colores';
import { volverA } from '../../../src/utilidades/navegacion';
import { filtrarNomina, ordenarNomina } from '../../../src/utilidades/nomina';

const volverAMisCursos = () => volverA('/cursos');

function FilaAlumno({ alumno, primera }: { alumno: AlumnoNomina; primera: boolean }) {
  const c = useColores();
  return (
    <View
      style={[
        estilos.fila,
        { backgroundColor: c.superficie, borderColor: c.borde },
        !primera && { borderTopWidth: 1 },
      ]}
      accessible
      accessibilityLabel={`${alumno.nombre_completo}, legajo ${alumno.legajo}, DNI ${alumno.dni}`}
    >
      <Text style={[estilos.alumno, { color: c.tinta }]}>{alumno.nombre_completo}</Text>
      <Text style={[estilos.chico, { color: c.tenue }]}>
        Legajo {alumno.legajo} · DNI {alumno.dni}
      </Text>
    </View>
  );
}

export default function DetalleCurso() {
  const c = useColores();
  const { id } = useLocalSearchParams<{ id: string }>();
  const cursoId = Number(id);
  const [busqueda, setBusqueda] = useState('');
  const [enfocado, setEnfocado] = useState(false);

  const cargar = useCallback(() => obtenerCursoDocente(cursoId), [cursoId]);
  const { datos, error, cargando, refrescando, recargar } = useCarga(cargar);

  useEffect(() => {
    void recargar();
  }, [recargar]);

  const nomina = useMemo(() => ordenarNomina(datos?.alumnos ?? []), [datos]);
  const filtrados = useMemo(() => filtrarNomina(nomina, busqueda), [nomina, busqueda]);

  if (cargando && !datos) return <Cargando />;

  if (!datos) {
    return (
      <View style={[estilos.centrado, { backgroundColor: c.fondo }]}>
        <EstadoVacio
          icono="alert-circle-outline"
          titulo="No se pudo abrir el curso"
          texto={error?.message}
        >
          <Boton titulo="Reintentar" onPress={() => recargar()} />
          <Boton
            titulo="Volver"
            icono="arrow-left"
            variante="contorno"
            onPress={volverAMisCursos}
          />
        </EstadoVacio>
      </View>
    );
  }

  const { curso, materias } = datos;
  const cantidad =
    filtrados.length === nomina.length
      ? `${nomina.length} ${nomina.length === 1 ? 'alumno' : 'alumnos'}`
      : `${filtrados.length} de ${nomina.length} alumnos`;
  const lateral = { backgroundColor: c.superficie, borderColor: c.borde };

  return (
    <FlatList
      style={{ backgroundColor: c.fondo }}
      contentContainerStyle={estilos.lista}
      data={filtrados}
      keyExtractor={(alumno) => String(alumno.id)}
      renderItem={({ item, index }) => <FilaAlumno alumno={item} primera={index === 0} />}
      keyboardShouldPersistTaps="handled"
      initialNumToRender={20}
      refreshControl={
        <RefreshControl
          refreshing={refrescando}
          onRefresh={() => recargar({ refrescar: true })}
          colors={[c.primario]}
          tintColor={c.primario}
        />
      }
      ListHeaderComponent={
        <View style={estilos.cabecera}>
          <Encabezado titulo={curso.nombre_completo}>
            <Boton
              titulo="Volver"
              icono="arrow-left"
              variante="contorno"
              compacto
              onPress={volverAMisCursos}
            />
          </Encabezado>
          <Horarios
            horarios={curso.horarios}
            prefijo="Horario"
            vacio="Sin horario de cursado"
            conIcono
          />
          <View style={estilos.materias}>
            <Text style={[estilos.subtitulo, { color: c.texto }]}>Mis materias en este curso:</Text>
            <ListaMaterias materias={materias} vacio="Ninguna" />
          </View>
          {error ? <Aviso tipo="error" texto={error.message} /> : null}

          {/* Parte de arriba de la tarjeta de la nómina: buscador y cantidad. */}
          <View style={[estilos.tarjetaArriba, lateral, { boxShadow: c.sombra }]}>
            <View
              style={[
                estilos.buscador,
                { borderColor: enfocado ? c.primario : c.borde, backgroundColor: c.superficie },
              ]}
            >
              <Icono nombre="magnify" color={c.tenue} tamano={20} />
              <TextInput
                value={busqueda}
                onChangeText={setBusqueda}
                placeholder="Buscar por nombre, legajo o DNI"
                placeholderTextColor={c.tenue}
                accessibilityLabel="Buscar alumno"
                accessibilityHint="Filtra la nómina por nombre, apellido, legajo o DNI"
                autoCorrect={false}
                clearButtonMode="while-editing"
                returnKeyType="search"
                onFocus={() => setEnfocado(true)}
                onBlur={() => setEnfocado(false)}
                style={[estilos.entrada, { color: c.tinta }]}
              />
            </View>
            <Text
              accessibilityLiveRegion="polite"
              style={[estilos.columnas, { color: c.tenue, borderBottomColor: c.borde }]}
            >
              {cantidad}
            </Text>
          </View>
        </View>
      }
      ListEmptyComponent={
        <View style={[estilos.vacio, lateral]}>
          <Icono nombre="account-group" color={c.tenue} tamano={36} estilo={estilos.iconoVacio} />
          <Text accessibilityRole="header" style={[estilos.vacioTitulo, { color: c.tinta }]}>
            {nomina.length ? 'Sin resultados' : 'El curso no tiene alumnos'}
          </Text>
          {nomina.length ? (
            <Text style={[estilos.chico, { color: c.tenue }]}>
              Ningún alumno coincide con la búsqueda.
            </Text>
          ) : null}
        </View>
      }
      ListFooterComponent={<View style={[estilos.tarjetaAbajo, lateral]} />}
    />
  );
}

const estilos = StyleSheet.create({
  centrado: { flex: 1, justifyContent: 'center', padding: medidas.espacio },
  lista: { padding: medidas.espacio, paddingBottom: 32 },
  cabecera: { gap: 12 },
  subtitulo: { fontSize: 15 },
  materias: { gap: 6 },
  tarjetaArriba: {
    marginTop: 4,
    borderWidth: 1,
    borderBottomWidth: 0,
    borderTopLeftRadius: medidas.radio,
    borderTopRightRadius: medidas.radio,
    paddingTop: medidas.espacio,
    paddingHorizontal: medidas.espacio,
    gap: 12,
  },
  buscador: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    minHeight: medidas.areaTactil,
    borderWidth: 1,
    borderRadius: 9,
    paddingHorizontal: 12,
  },
  entrada: {
    flex: 1,
    minWidth: 0,
    minHeight: medidas.areaTactil - 2,
    fontSize: 16,
    ...(Platform.OS === 'web' ? { outlineWidth: 0 } : null),
  },
  // Como el encabezado de las tablas de la web: chico y en gris.
  columnas: {
    fontSize: 13,
    fontWeight: '700',
    letterSpacing: 0.5,
    textTransform: 'uppercase',
    paddingBottom: 8,
    borderBottomWidth: 1,
  },
  fila: {
    borderLeftWidth: 1,
    borderRightWidth: 1,
    paddingVertical: 10,
    paddingHorizontal: medidas.espacio,
    minHeight: medidas.areaTactil,
    justifyContent: 'center',
  },
  alumno: { fontSize: 16, fontWeight: '600' },
  chico: { fontSize: 14 },
  vacio: {
    borderLeftWidth: 1,
    borderRightWidth: 1,
    alignItems: 'center',
    paddingVertical: 28,
    paddingHorizontal: 16,
    gap: 6,
  },
  iconoVacio: { opacity: 0.55 },
  vacioTitulo: { fontSize: 17, fontWeight: '600' },
  tarjetaAbajo: {
    height: 8,
    borderWidth: 1,
    borderTopWidth: 0,
    borderBottomLeftRadius: medidas.radio,
    borderBottomRightRadius: medidas.radio,
  },
});
