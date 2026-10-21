# Wings — Bitácora activa de CLAUDE

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-CLAUDE.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).
· [Entradas archivadas el 17/09](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE-2.md) · [Entradas archivadas el 21/09](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE-2.md) · [Entradas archivadas el 02/10](../99-archivo/bitacoras/2026-10-02/LOG-CLAUDE.md) · [Entradas archivadas el 10/10](../99-archivo/bitacoras/2026-10-10/LOG-CLAUDE.md) · [Entradas archivadas el 13/10](../99-archivo/bitacoras/2026-10-13/LOG-CLAUDE.md) · [Entradas archivadas el 05/10](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE-2.md) · [Entradas archivadas el 07/10](../99-archivo/bitacoras/2026-10-07/LOG-CLAUDE.md) · [Entradas archivadas el 08/10](../99-archivo/bitacoras/2026-10-08/LOG-CLAUDE.md) · [Entradas archivadas el 12/10](../99-archivo/bitacoras/2026-10-12/LOG-CLAUDE.md) · [Entradas archivadas el 16/10](../99-archivo/bitacoras/2026-10-16/LOG-CLAUDE.md) · [Entradas archivadas el 19/10](../99-archivo/bitacoras/2026-10-19/LOG-CLAUDE.md) · [Entradas archivadas el 20/10](../99-archivo/bitacoras/2026-10-20/LOG-CLAUDE.md)

## 2026-10-21 — Claude CAB — T50: decisión de Carlos sobre la inscripción y tabla al día

- Codex devolvió T50 con H1–H3. H1 (la inscripción de un alumno con ingreso el mes que viene ya
  cuenta como deuda) no es falla: Carlos decidió «Inscripción a partir de hoy». H2 y H3 van a Gemini.
- ALU-007 c corregido en las dos copias de la tabla. La viva estaba atrasada respecto del repo
  (Gemini había actualizado solo la copia); se subió la del repo con la corrección.
- Con este commit suben los 5 locales de Gemini (cierre T42/T43) y Codex (devolución T50), con
  autorización de Carlos. Codex también los dejó en la rama `codex/t50-verificacion-cab-20261021`.

## 2026-10-20 — Claude CAB — T61 verificada y devuelta a Codex

- La transferencia en sí está bien: control propio $300.000 Efectivo → Banco, doble envío sin
  duplicar, resultado del club igual, saldos −/+ $300.000, patas no corregibles sueltas.
  Pruebas de Codex 19/19 en `wings_testing_claude`.
- H1–H3, un mismo origen: TRASPASO sigue en el formulario de cualquier subrubro (resto de la
  primera versión). Con eso la operativa carga en caja un gasto de $50.000 que no cuenta, y
  corregir un gasto real de $40.000 a ese subrubro lo borra de los egresos. Reproducido.
- Antes de correr mis controles verifiqué que no hubiera otra suite activa en la máquina.
- `VERIFICACION-T61-CLAUDE.md`. Devuelta a Codex en el tablero.

## 2026-10-20 — Claude CAB — T52: la clase vieja conserva a quien tiene marca

- DU-09: «los cambios son hacia adelante». `ClaseWebController::show` suma a la lista del grupo de hoy
  a quien ya tiene marca en la clase; la tarjeta dice «Cambió de grupo» o «De baja».
- Hallazgo al leer: `storeAsistencias` rechazaba al que cambió de grupo, así que corregir una lista vieja
  daba error. Ahora acepta a quien ya tiene marca; al resto lo sigue rechazando.
- 5 pruebas; Chrome 1366/375 con corrección por clics (4 presentes, consola limpia). Suite rápida:
  835/4 omitidas; 2 fallidas de T61 de Codex sin subir. Contrato V1 y tabla (ALU-013, ASI-009) al día.
- Para Carlos: quien entró al grupo después sigue apareciendo en clases viejas; no se decidió.
- A verificar (lógica) y visual de Carlos. Sin despliegue.
- **Error mío:** lancé la suite rápida de T52 mientras Codex corría la completa de T61 en la misma
  máquina (bases distintas). Su nota dice que su primera completa se interrumpió por otra suite
  iniciada después: fue la mía. La regla (AGENTS §6-ter) es una sola a la vez por máquina; antes de
  lanzar una suite hay que mirar si hay otra corriendo. Codex tiene que repetir la suya.
- Al rearmar los documentos de estado conservé lo de T61 de Codex sin subir; en el contador dejé su
  texto con 956 (sus 951 más mis 5).

## 2026-10-20 — Claude CyE — La tabla de pruebas en línea la escriben solo Carlos y Claude

- Carlos: la tabla se hizo externa para que las otras IAs no la toquen. En T42/T43 Codex mandó cambios
  de COB-012 y LIQ-002 a la conversación de Claude de la tabla, con un OK de Carlos que no entendió así.
- Regla AGENTS §5-ter y aviso en el README de la copia: Codex y Gemini leen la copia y proponen en su
  informe; Claude pasa los cambios. COB-012 comprobado: tabla en línea = GitHub = carpeta.
- Pendiente mío: verificar T50 (Gemini). T52 espera la reverificación de Codex.

## 2026-10-20 — Claude CyE — T64 cerrada; T76 auditoría de uso guardada en el repo

- T64 segunda vuelta (520bafd1): H1–H3 corregidos; mis 5 pruebas exigiendo rechazo + 16 de Gemini, 21/21.
  Observación sin bloqueo: el editor redondea el porcentaje (ya pasaba). Sin aprobación visual. Cerrada.
- Carlos: no escribir prompts para más tarde, se pierden al compactar. La orden de auditoría de uso quedó
  como T76 + docs/06-pruebas/PRU-04/ORDEN-AUDITORIA-USO.md.

## 2026-10-20 — Claude CyE — Regla: decidir sobre una fila no recarga la página

- Carlos vio T43 de Codex: cada validación recargaba toda la pantalla (vuelve arriba, destello, repite
  consultas, total mal rotulado). «Hacer esa mierda no es admisible.»
- Regla para los tres agentes: DESIGN-RULES §8-bis + punto en el checklist del revisor; AGENTS §5-bis
  apunta ahí. Modelos: caja-detalle.js y clases-show.js. No toqué T43: la está corrigiendo Codex.
- También: no pedir OK visual si no cambian vistas ni estilos (T64 y T50 no lo necesitaban).

## 2026-10-20 — Claude CyE — T64 verificada: devuelta a Gemini

- Validador y lote bien (transacción, solo ADMIN, cambio de tramos en un envío). 13/13 de Gemini.
- H1 alcanzable: Cancelar no repone los valores y el próximo guardado manda la edición abandonada
  (leído en configuraciones.js, no reproducido en navegador). H2 id repetido → días 1–15 sin cubrir,
  200 (reproducido). H3 id inexistente → 404 en inglés. Los dos últimos solo con pedido armado.
- Pruebas independientes fuera de tests/: 5, 1 fallida (H2). Informe VERIFICACION-T64-CLAUDE.md.

## 2026-10-20 — Claude CyE — T52 H5: el cambio de grupo no adelanta un ingreso futuro

- Codex reverificó d148a241: H1–H4 bien, H5 nuevo (cambio de grupo antes de ingreso futuro → asistencia
  anticipada). `enGrupoElDia` exige siempre el ingreso. 12 pruebas T52; dirigidas 77/77.
- Sin suite completa: carpeta con T42 de Codex sin subir. Commit solo con lo mío; contador 984.

## 2026-10-20 — Claude CyE — T52 tercera vuelta: H1–H4 de Codex corregidos

- Codex devolvió la segunda parte con 4 hallazgos reales. Causa común: suposiciones sin probar.
- H1 guardado exige activo como la pantalla; H2/H3 `grupo_desde` vacío mientras no cambia de grupo
  (vale el ingreso, aunque se corrija o sea futuro; la migración solo completa a quien muestra cambio);
  H4 `ClaseService::contarAsistenciasSemanaDeVarios`, dos consultas para toda la lista.
- Antes de entregar busqué yo los casos que lo rompen: mismo grupo guardado como texto (no cuenta como
  cambio), Excel guarda después del alta sin tocar el grupo. 11 pruebas T52.
- Suite completa: 965 aprobadas, 3 fallidas ajenas (T64 de Gemini sin subir). Commit solo con lo mío,
  sobre las versiones publicadas de los documentos compartidos. Carlos, con razón, molesto por la calidad.

## 2026-10-20 — Claude CyE — Tablero: las cuatro respuestas de la encuesta como etiquetas

- Carlos vio 10 tareas «sin categorizar»: eran «Antes de producción» del 18/10, que el tablero no podía
  mostrar (solo tenía Prioritaria / Muy a futuro). Carlos: «Es un tablero... tiene que ser útil».
- `scripts/tablero/lib.php`: prioridad = Ahora, Antes del 1/11, Antes de producción, Después o Sin decidir,
  con color propio; «Sin empezar» separado por categoría, en ese orden.
- Datos migrados desde la etiqueta del 18/10 en cada tarea; sin etiqueta, alta→Ahora y baja→Después.
  T12 pasa a Antes de producción (respuesta del 18/10 sobre la marca del 10/10); T50 recupera la suya.
- Pruebas del tablero: 9 verdes. Solo herramienta: Wings no cambia.

## 2026-10-20 — Claude CyE — T52 segunda parte: quien entró después no aparece

- Carlos: «Los cambios son siempre para adelante» (DU-09). Visual de T52 aprobado: «Las vistas están OK».
- El plan no sirve para la fecha en el grupo (cambio inmediato, plan diferido al mes siguiente):
  columna nueva `alumnos.grupo_desde`, completada por el modelo `Alumno` en alta, cambio de grupo y
  corrección del ingreso; migración que la completa para los existentes. `enGrupoElDia` en show y guardado.
- `wingstest` (solo lectura): a los 100 les quedaría su fecha de ingreso; nadie desaparece.
- 2 pruebas nuevas (7 en T52). Suite completa `wings_testing_claude`: 952 aprobadas, 4 omitidas,
  2 fallidas: contador (corregido a 958) y `CancelarLiquidacionCerradaTest`, cuyo alumno de prueba no
  tenía ingreso anterior a la clase (fixture corregido, 9/9).
- Esperé a que Codex terminara su fusión de T61 antes de tocar nada. Requiere migrar al desplegar.

## 2026-10-19 — Claude CyE — Producción y prueba: dos bases que se confundieron

- Carlos: «En test tiene que haber 100». Comprobado por SSH, solo lectura: producción `wings` 0 alumnos;
  prueba `wingstest` 100 activos (muestra 241–245 mirada).
- T66 (Codex): su relevamiento «sitio de prueba» consultó `wings` (producción, vacía). Rehecho en
  `wingstest`: 0 clases sin profesora en 79, filas revisadas. Informe corregido con nota.
- T56 (Gemini): estaba bien, consultó también `wingstest` (0 de 100 sin plan); comprobado de nuevo: 0.
- `SERVIDOR.md` documentaba solo producción: agregada la tabla de los dos sitios.
