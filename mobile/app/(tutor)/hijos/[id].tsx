// Detalle del alumno (M3.2–M3.5): inscribir y dar de baja cursado, comedor, transporte y deportes,
// con el mismo diseño que autogestion/show.blade.php.
// Las reglas las valida el servidor: acá solo se muestra el mensaje que devuelve la API.
import { useLocalSearchParams } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';

import { mensajeDeError } from '../../../src/api/cliente';
import type { Estado, RespuestaInscripcion, Servicio } from '../../../src/api/tipos';
import { darDeBaja, inscribir, obtenerCatalogo, obtenerHijo } from '../../../src/api/tutor';
import { Aviso } from '../../../src/componentes/Aviso';
import type { TipoAviso } from '../../../src/componentes/Aviso';
import { useAnunciarCiclo } from '../../../src/componentes/BarraSuperior';
import { Boton } from '../../../src/componentes/Boton';
import { AvisoServidorLento, Cargando } from '../../../src/componentes/Cargando';
import { Encabezado } from '../../../src/componentes/Encabezado';
import { EstadoVacio } from '../../../src/componentes/EstadoVacio';
import { Etiqueta } from '../../../src/componentes/Etiqueta';
import { Horarios, textoHorarios } from '../../../src/componentes/Horarios';
import { Icono, ICONOS_SERVICIO } from '../../../src/componentes/Icono';
import type { NombreIcono } from '../../../src/componentes/Icono';
import { OpcionRadio } from '../../../src/componentes/OpcionRadio';
import { Tarjeta } from '../../../src/componentes/Tarjeta';
import { useCarga } from '../../../src/hooks/useCarga';
import { medidas, useColores } from '../../../src/tema/colores';
import type { Acento } from '../../../src/tema/colores';
import { confirmar } from '../../../src/utilidades/confirmar';
import { volverA } from '../../../src/utilidades/navegacion';

type Seccion = 'cursado' | 'comedor' | 'transporte' | 'deportes';

interface AvisoSeccion {
  seccion: Seccion;
  tipo: TipoAviso;
  texto: string;
}

const volverAMisHijos = () => volverA('/hijos');

export default function DetalleAlumno() {
  const c = useColores();
  const { id } = useLocalSearchParams<{ id: string }>();
  const alumnoId = Number(id);

  // Estado devuelto por el último POST/DELETE; tiene prioridad sobre el de la carga.
  const [estadoNuevo, setEstadoNuevo] = useState<Estado | null>(null);
  const [enviando, setEnviando] = useState<string | null>(null);
  const [aviso, setAviso] = useState<AvisoSeccion | null>(null);
  const [recorridoElegido, setRecorridoElegido] = useState<number | null>(null);
  const [deporteElegido, setDeporteElegido] = useState<number | null>(null);

  const cargar = useCallback(async () => {
    const [hijo, catalogo] = await Promise.all([obtenerHijo(alumnoId), obtenerCatalogo()]);
    setEstadoNuevo(null);
    return { hijo, catalogo };
  }, [alumnoId]);

  const { datos, error, cargando, refrescando, recargar } = useCarga(cargar);
  useAnunciarCiclo(datos ? (datos.hijo.ciclo?.anio ?? null) : undefined);

  useEffect(() => {
    void recargar();
  }, [recargar]);

  if (cargando && !datos) return <Cargando />;

  if (!datos) {
    return (
      <View style={[estilos.centrado, { backgroundColor: c.fondo }]}>
        <EstadoVacio
          icono="alert-circle-outline"
          titulo="No se pudo abrir el alumno"
          texto={error?.message}
        >
          <Boton titulo="Reintentar" onPress={() => recargar()} />
          <Boton titulo="Volver" icono="arrow-left" variante="contorno" onPress={volverAMisHijos} />
        </EstadoVacio>
      </View>
    );
  }

  const { ciclo, alumno } = datos.hijo;
  const { catalogo } = datos;
  const estado = estadoNuevo ?? alumno.estado;
  const ocupado = enviando !== null;

  /** Ejecuta un POST o DELETE y refresca la pantalla con el `estado` de la respuesta. */
  async function ejecutar(
    seccion: Seccion,
    clave: string,
    accion: () => Promise<RespuestaInscripcion>,
  ) {
    setEnviando(clave);
    setAviso(null);
    try {
      const respuesta = await accion();
      setEstadoNuevo(respuesta.estado);
      setRecorridoElegido(null);
      setDeporteElegido(null);
      setAviso({ seccion, tipo: 'exito', texto: respuesta.message });
    } catch (e) {
      // Por ejemplo un 422 con regla RN-04: se muestra el mensaje tal cual.
      setAviso({ seccion, tipo: 'error', texto: mensajeDeError(e) });
    } finally {
      setEnviando(null);
    }
  }

  async function confirmarBaja(
    seccion: Seccion,
    servicio: Servicio,
    inscripcionId: number,
    descripcion: string,
  ) {
    const confirmado = await confirmar({
      titulo: 'Dar de baja',
      mensaje: `¿Confirmás la baja de ${descripcion}?`,
      textoConfirmar: 'Dar de baja',
    });
    if (!confirmado) return;
    await ejecutar(seccion, `baja-${servicio}-${inscripcionId}`, () =>
      darDeBaja(alumnoId, servicio, inscripcionId),
    );
  }

  const avisoDe = (seccion: Seccion) =>
    aviso?.seccion === seccion ? <Aviso tipo={aviso.tipo} texto={aviso.texto} /> : null;

  const textoTenue = [estilos.textoChico, { color: c.tenue }];
  const texto = [estilos.texto, { color: c.texto }];
  const fuerte = [estilos.fuerte, { color: c.tinta }];

  const deportesDisponibles = catalogo.deportes.filter(
    (d) => !estado?.deportes.some((i) => i.deporte.id === d.id),
  );
  const deporteSeleccionado = deportesDisponibles.find((d) => d.id === deporteElegido);

  return (
    <ScrollView
      style={{ backgroundColor: c.fondo }}
      contentContainerStyle={estilos.contenido}
      refreshControl={
        <RefreshControl
          refreshing={refrescando}
          onRefresh={() => recargar({ refrescar: true })}
          colors={[c.primario]}
          tintColor={c.primario}
        />
      }
    >
      <Encabezado
        titulo={`${alumno.nombre} ${alumno.apellido}`}
        subtitulo={`Legajo ${alumno.legajo} · ${alumno.curso?.nombre_completo ?? 'Sin curso asignado'}`}
      >
        <Boton
          titulo="Volver"
          icono="arrow-left"
          variante="contorno"
          compacto
          onPress={volverAMisHijos}
        />
      </Encabezado>

      {alumno.horarios_cursado.length ? (
        <Horarios horarios={alumno.horarios_cursado} prefijo="Horario de cursado" conIcono />
      ) : null}

      {error ? <Aviso tipo="error" texto={error.message} /> : null}
      <AvisoServidorLento />

      {!ciclo || !estado ? (
        <Aviso
          tipo="advertencia"
          texto="Las inscripciones están cerradas: no hay un ciclo lectivo activo."
        />
      ) : (
        <>
          {/* Cursado (RF-08) */}
          <SeccionServicio
            titulo={`Cursado ${ciclo.anio}`}
            icono={ICONOS_SERVICIO.cursado}
            acento={c.acentoAzul}
            activo={!!estado.cursado}
            aviso={avisoDe('cursado')}
          >
            {estado.cursado ? (
              <>
                <Text style={texto}>
                  Inscripto en <Text style={fuerte}>{estado.cursado.curso.nombre_completo}</Text>.
                </Text>
                <Boton
                  titulo="Dar de baja"
                  etiqueta="Dar de baja del cursado"
                  variante="peligro"
                  compacto
                  cargando={enviando === `baja-cursado-${estado.cursado.id}`}
                  deshabilitado={ocupado}
                  onPress={() =>
                    confirmarBaja(
                      'cursado',
                      'cursado',
                      estado.cursado!.id,
                      'la inscripción al cursado',
                    )
                  }
                />
              </>
            ) : alumno.curso ? (
              <>
                <Text style={texto}>
                  Confirmá la inscripción al cursado de {alumno.curso.nombre_completo}.
                </Text>
                <Boton
                  titulo="Inscribir al cursado"
                  compacto
                  cargando={enviando === 'inscribir-cursado'}
                  deshabilitado={ocupado}
                  onPress={() =>
                    ejecutar('cursado', 'inscribir-cursado', () => inscribir(alumnoId, 'cursado'))
                  }
                />
              </>
            ) : (
              <Text style={[estilos.texto, { color: c.tenue }]}>
                La administración todavía no le asignó un curso.
              </Text>
            )}
          </SeccionServicio>

          {/* Comedor (RF-10) */}
          <SeccionServicio
            titulo="Comedor"
            icono={ICONOS_SERVICIO.comedor}
            acento={c.acentoAmbar}
            activo={!!estado.comedor}
            aviso={avisoDe('comedor')}
          >
            {estado.comedor ? (
              <>
                <Text style={texto}>Tiene el servicio de comedor activo.</Text>
                <Boton
                  titulo="Dar de baja"
                  etiqueta="Dar de baja del comedor"
                  variante="peligro"
                  compacto
                  cargando={enviando === `baja-comedor-${estado.comedor.id}`}
                  deshabilitado={ocupado}
                  onPress={() =>
                    confirmarBaja(
                      'comedor',
                      'comedor',
                      estado.comedor!.id,
                      'la inscripción al comedor',
                    )
                  }
                />
              </>
            ) : (
              <Boton
                titulo="Inscribir al comedor"
                compacto
                cargando={enviando === 'inscribir-comedor'}
                deshabilitado={ocupado}
                onPress={() =>
                  ejecutar('comedor', 'inscribir-comedor', () => inscribir(alumnoId, 'comedor'))
                }
              />
            )}
          </SeccionServicio>

          {/* Transporte (RF-11, RN-06) */}
          <SeccionServicio
            titulo="Transporte"
            icono={ICONOS_SERVICIO.transporte}
            acento={c.acentoRosa}
            activo={!!estado.transporte}
            aviso={avisoDe('transporte')}
          >
            {estado.transporte ? (
              <>
                <Text style={texto}>
                  Recorrido <Text style={fuerte}>{estado.transporte.recorrido.nombre}</Text>.
                </Text>
                <Text style={textoTenue}>
                  Solo se permite un recorrido. Para cambiarlo, dalo de baja y elegí otro.
                </Text>
                <Boton
                  titulo="Dar de baja"
                  etiqueta="Dar de baja del transporte"
                  variante="peligro"
                  compacto
                  cargando={enviando === `baja-transporte-${estado.transporte.id}`}
                  deshabilitado={ocupado}
                  onPress={() =>
                    confirmarBaja(
                      'transporte',
                      'transporte',
                      estado.transporte!.id,
                      `el recorrido ${estado.transporte!.recorrido.nombre}`,
                    )
                  }
                />
              </>
            ) : catalogo.recorridos.length ? (
              <>
                <View accessibilityRole="radiogroup" accessibilityLabel="Recorridos">
                  {catalogo.recorridos.map((recorrido) => (
                    <OpcionRadio
                      key={recorrido.id}
                      titulo={recorrido.nombre}
                      detalles={[recorrido.descripcion]}
                      elegido={recorridoElegido === recorrido.id}
                      deshabilitado={ocupado}
                      onPress={() => setRecorridoElegido(recorrido.id)}
                    />
                  ))}
                </View>
                <Boton
                  titulo="Inscribir al recorrido"
                  compacto
                  cargando={enviando === 'inscribir-transporte'}
                  deshabilitado={ocupado || recorridoElegido === null}
                  pista={recorridoElegido === null ? 'Primero elegí un recorrido' : undefined}
                  onPress={() =>
                    recorridoElegido !== null &&
                    ejecutar('transporte', 'inscribir-transporte', () =>
                      inscribir(alumnoId, 'transporte', { recorrido_id: recorridoElegido }),
                    )
                  }
                />
              </>
            ) : (
              <Text style={textoTenue}>No hay recorridos disponibles.</Text>
            )}
          </SeccionServicio>

          {/* Deportes (RF-09, RN-03, RN-04, RN-05) */}
          <SeccionServicio
            titulo="Deportes"
            icono={ICONOS_SERVICIO.deportes}
            acento={c.acentoVerde}
            activo={estado.deportes.length > 0}
            etiqueta={`${estado.deportes.length} de ${catalogo.max_deportes}`}
            aviso={avisoDe('deportes')}
          >
            {estado.deportes.length ? (
              <View>
                {estado.deportes.map((inscripcion) => (
                  <View key={inscripcion.id} style={[estilos.fila, { borderBottomColor: c.borde }]}>
                    <View style={estilos.flex}>
                      <Text style={fuerte}>{inscripcion.deporte.nombre}</Text>
                      <Horarios horarios={inscripcion.deporte.horarios} />
                    </View>
                    <Boton
                      titulo="Baja"
                      etiqueta={`Dar de baja de ${inscripcion.deporte.nombre}`}
                      variante="peligro"
                      compacto
                      cargando={enviando === `baja-deporte-${inscripcion.id}`}
                      deshabilitado={ocupado}
                      onPress={() =>
                        confirmarBaja(
                          'deportes',
                          'deporte',
                          inscripcion.id,
                          inscripcion.deporte.nombre,
                        )
                      }
                    />
                  </View>
                ))}
              </View>
            ) : null}

            {estado.deportes.length < catalogo.max_deportes ? (
              deportesDisponibles.length ? (
                <>
                  <View accessibilityRole="radiogroup" accessibilityLabel="Deportes">
                    {deportesDisponibles.map((deporte) => (
                      <OpcionRadio
                        key={deporte.id}
                        titulo={deporte.nombre}
                        detalles={[
                          deporte.horarios.length ? textoHorarios(deporte.horarios) : 'Sin horario',
                        ]}
                        elegido={deporteElegido === deporte.id}
                        deshabilitado={ocupado}
                        onPress={() => setDeporteElegido(deporte.id)}
                      />
                    ))}
                  </View>
                  <Boton
                    titulo="Inscribir"
                    etiqueta={
                      deporteSeleccionado
                        ? `Inscribir en ${deporteSeleccionado.nombre}`
                        : 'Inscribir en el deporte elegido'
                    }
                    compacto
                    cargando={enviando === 'inscribir-deporte'}
                    deshabilitado={ocupado || !deporteSeleccionado}
                    pista={deporteSeleccionado ? undefined : 'Primero elegí un deporte'}
                    onPress={() =>
                      deporteSeleccionado &&
                      ejecutar('deportes', 'inscribir-deporte', () =>
                        inscribir(alumnoId, 'deporte', { deporte_id: deporteSeleccionado.id }),
                      )
                    }
                  />
                  <Text style={textoTenue}>
                    No se permiten deportes con horarios superpuestos entre sí ni con el cursado.
                  </Text>
                </>
              ) : (
                <Text style={textoTenue}>No hay otros deportes disponibles.</Text>
              )
            ) : (
              <Text style={textoTenue}>Alcanzó el máximo de {catalogo.max_deportes} deportes.</Text>
            )}
          </SeccionServicio>
        </>
      )}
    </ScrollView>
  );
}

interface PropsSeccion {
  titulo: string;
  icono: NombreIcono;
  acento: Acento;
  activo: boolean;
  /** Texto de la etiqueta; por defecto "Inscripto" o "Sin inscribir". */
  etiqueta?: string;
  aviso: ReactNode;
  children: ReactNode;
}

/** Tarjeta de un servicio: ícono de color, título e insignia de estado, como la web. */
function SeccionServicio({
  titulo,
  icono,
  acento,
  activo,
  etiqueta,
  aviso,
  children,
}: PropsSeccion) {
  const c = useColores();
  return (
    <Tarjeta estilo={estilos.seccion}>
      <View style={estilos.seccionCabecera}>
        <View style={[estilos.iconoServicio, { backgroundColor: acento.fondo }]}>
          <Icono nombre={icono} color={acento.color} tamano={24} />
        </View>
        <View style={estilos.seccionTitulos}>
          <Text accessibilityRole="header" style={[estilos.seccionTitulo, { color: c.tinta }]}>
            {titulo}
          </Text>
          <Etiqueta activo={activo} texto={etiqueta ?? (activo ? 'Inscripto' : 'Sin inscribir')} />
        </View>
      </View>
      {aviso}
      {children}
    </Tarjeta>
  );
}

const estilos = StyleSheet.create({
  centrado: { flex: 1, justifyContent: 'center', padding: medidas.espacio },
  contenido: { padding: medidas.espacio, gap: medidas.espacio, paddingBottom: 32 },
  texto: { fontSize: 16, lineHeight: 23 },
  textoChico: { fontSize: 14, lineHeight: 20 },
  fuerte: { fontSize: 16, fontWeight: '600' },
  flex: { flex: 1 },
  seccion: { gap: 14 },
  seccionCabecera: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  iconoServicio: {
    width: 46,
    height: 46,
    borderRadius: 11,
    alignItems: 'center',
    justifyContent: 'center',
  },
  seccionTitulos: { flex: 1, gap: 4, alignItems: 'flex-start' },
  seccionTitulo: { fontSize: 20, fontWeight: '600' },
  fila: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingVertical: 8,
    borderBottomWidth: 1,
  },
});
