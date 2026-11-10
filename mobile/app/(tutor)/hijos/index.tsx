// Mis hijos (M3.1): una tarjeta por hijo con su curso y el estado de los cuatro servicios,
// con el mismo diseño que autogestion/index.blade.php.
import { router, useFocusEffect } from 'expo-router';
import { useCallback } from 'react';
import { FlatList, RefreshControl, StyleSheet, Text, View } from 'react-native';

import type { AlumnoResumen, Estado } from '../../../src/api/tipos';
import { obtenerHijos } from '../../../src/api/tutor';
import { Aviso } from '../../../src/componentes/Aviso';
import { useAnunciarCiclo } from '../../../src/componentes/BarraSuperior';
import { Boton } from '../../../src/componentes/Boton';
import { Cargando } from '../../../src/componentes/Cargando';
import { Encabezado } from '../../../src/componentes/Encabezado';
import { EstadoVacio } from '../../../src/componentes/EstadoVacio';
import { Etiqueta } from '../../../src/componentes/Etiqueta';
import { Icono, ICONOS_SERVICIO } from '../../../src/componentes/Icono';
import type { NombreIcono } from '../../../src/componentes/Icono';
import { Tarjeta } from '../../../src/componentes/Tarjeta';
import { useCarga } from '../../../src/hooks/useCarga';
import { medidas, useColores } from '../../../src/tema/colores';

interface FilaServicio {
  nombre: string;
  icono: NombreIcono;
  activo: boolean;
  detalle?: string;
}

function serviciosDe(estado: Estado): FilaServicio[] {
  return [
    { nombre: 'Cursado', icono: ICONOS_SERVICIO.cursado, activo: !!estado.cursado },
    { nombre: 'Comedor', icono: ICONOS_SERVICIO.comedor, activo: !!estado.comedor },
    {
      nombre: 'Transporte',
      icono: ICONOS_SERVICIO.transporte,
      activo: !!estado.transporte,
      detalle: estado.transporte?.recorrido.nombre,
    },
    {
      nombre: 'Deportes',
      icono: ICONOS_SERVICIO.deportes,
      activo: estado.deportes.length > 0,
      detalle: estado.deportes.map((d) => d.deporte.nombre).join(', ') || undefined,
    },
  ];
}

function TarjetaHijo({ alumno }: { alumno: AlumnoResumen }) {
  const c = useColores();
  const iniciales = `${alumno.nombre.charAt(0)}${alumno.apellido.charAt(0)}`;
  const nombre = `${alumno.nombre} ${alumno.apellido}`;

  return (
    <Tarjeta estilo={estilos.tarjeta}>
      <View style={estilos.cabecera}>
        <View
          style={[estilos.avatar, { backgroundColor: c.primarioSuave }]}
          accessibilityElementsHidden
          importantForAccessibility="no-hide-descendants"
        >
          <Text style={[estilos.iniciales, { color: c.primario }]}>{iniciales}</Text>
        </View>
        <View style={estilos.flex}>
          <Text accessibilityRole="header" style={[estilos.nombre, { color: c.tinta }]}>
            {nombre}
          </Text>
          <Text style={[estilos.chico, { color: c.tenue }]}>
            Legajo {alumno.legajo} · {alumno.curso?.nombre_completo ?? 'Sin curso asignado'}
          </Text>
        </View>
      </View>

      {alumno.estado ? (
        <View style={estilos.servicios}>
          {serviciosDe(alumno.estado).map((s) => (
            <View
              key={s.nombre}
              style={estilos.servicio}
              accessible
              accessibilityLabel={`${s.nombre}: ${s.activo ? 'inscripto' : 'sin inscribir'}${
                s.detalle ? `, ${s.detalle}` : ''
              }`}
            >
              <Icono nombre={s.icono} color={c.tenue} tamano={19} />
              <Text style={[estilos.servicioTexto, { color: c.texto }]} numberOfLines={2}>
                {s.nombre}
                {s.detalle ? (
                  <Text style={[estilos.detalle, { color: c.tenue }]}> — {s.detalle}</Text>
                ) : null}
              </Text>
              <Etiqueta
                conIcono
                activo={s.activo}
                texto={s.activo ? 'Inscripto' : 'Sin inscribir'}
              />
            </View>
          ))}
        </View>
      ) : null}

      <Boton
        titulo="Gestionar inscripciones"
        icono="pencil-box-outline"
        etiqueta={`Gestionar inscripciones de ${nombre}`}
        onPress={() => router.push(`/hijos/${alumno.id}`)}
        estilo={estilos.boton}
      />
    </Tarjeta>
  );
}

export default function MisHijos() {
  const c = useColores();
  const { datos, error, cargando, refrescando, recargar } = useCarga(obtenerHijos);
  useAnunciarCiclo(datos ? (datos.ciclo?.anio ?? null) : undefined);

  // Se recarga cada vez que se vuelve a la pantalla (por ejemplo, después de inscribir).
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
          titulo="No se pudieron cargar tus hijos"
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
      data={datos.hijos}
      keyExtractor={(alumno) => String(alumno.id)}
      renderItem={({ item }) => <TarjetaHijo alumno={item} />}
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
            titulo="Mis hijos"
            subtitulo={
              datos.ciclo
                ? `Inscripciones del ciclo lectivo ${datos.ciclo.anio}`
                : 'Inscripciones cerradas'
            }
          />
          {error ? <Aviso tipo="error" texto={error.message} /> : null}
          {datos.ciclo ? null : (
            <Aviso
              tipo="advertencia"
              texto="Las inscripciones están cerradas porque no hay un ciclo lectivo activo. Consultá en la escuela."
            />
          )}
        </View>
      }
      ListEmptyComponent={
        <EstadoVacio
          icono="account-group"
          titulo="No tenés alumnos vinculados"
          texto="No tenés alumnos vinculados a tu cuenta. Comunicate con la administración de la escuela."
        />
      }
    />
  );
}

const estilos = StyleSheet.create({
  centrado: { flex: 1, justifyContent: 'center', padding: medidas.espacio },
  lista: { padding: medidas.espacio, gap: medidas.espacio, paddingBottom: 32 },
  encabezado: { gap: 12, marginBottom: 4 },
  tarjeta: { gap: 14 },
  cabecera: { flexDirection: 'row', alignItems: 'center', gap: 14 },
  avatar: {
    width: 46,
    height: 46,
    borderRadius: 23,
    alignItems: 'center',
    justifyContent: 'center',
  },
  iniciales: { fontSize: 16, fontWeight: '600' },
  flex: { flex: 1 },
  nombre: { fontSize: 20, fontWeight: '600' },
  chico: { fontSize: 14 },
  servicios: { gap: 2 },
  servicio: { flexDirection: 'row', alignItems: 'center', gap: 8, minHeight: 34 },
  servicioTexto: { flex: 1, fontSize: 16 },
  detalle: { fontSize: 14 },
  boton: { marginTop: 4 },
});
