# Wings — Bitácora activa de CLAUDE

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-CLAUDE.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).
· [Entradas archivadas el 17/09](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE-2.md) · [Entradas archivadas el 21/09](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE-2.md) · [Entradas archivadas el 02/10](../99-archivo/bitacoras/2026-10-02/LOG-CLAUDE.md) · [Entradas archivadas el 10/10](../99-archivo/bitacoras/2026-10-10/LOG-CLAUDE.md) · [Entradas archivadas el 13/10](../99-archivo/bitacoras/2026-10-13/LOG-CLAUDE.md) · [Entradas archivadas el 05/10](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE-2.md) · [Entradas archivadas el 07/10](../99-archivo/bitacoras/2026-10-07/LOG-CLAUDE.md) · [Entradas archivadas el 08/10](../99-archivo/bitacoras/2026-10-08/LOG-CLAUDE.md) · [Entradas archivadas el 12/10](../99-archivo/bitacoras/2026-10-12/LOG-CLAUDE.md) · [Entradas archivadas el 16/10](../99-archivo/bitacoras/2026-10-16/LOG-CLAUDE.md) · [Entradas archivadas el 19/10](../99-archivo/bitacoras/2026-10-19/LOG-CLAUDE.md) · [Entradas archivadas el 21/10](../99-archivo/bitacoras/2026-10-21/LOG-CLAUDE.md) · [Entradas archivadas el 20/10](../99-archivo/bitacoras/2026-10-20/LOG-CLAUDE.md)

## 2026-10-25 — Claude CAB — T77 a T80 cerradas y desplegadas en el sitio de prueba

- Carlos: «si están bien, subilas». Verificadas por otro agente cada una; cerradas en el tablero.
- Sitio de prueba de `56379295` a `b816d181`: respaldo `/root/respaldos/wingstest-antes-despliegue-2026-10-25.sql.gz`,
  down → merge --ff-only → npm ci/build → 0 migraciones → cachés → up. /login 200, CSP bloqueando,
  reloj simulado intacto (15/10). Producción no se tocó.

## 2026-10-25 — Claude CAB — Control cruzado de la verificación de T77 (Gemini)

- De acuerdo con el veredicto. Matiz: el rechazo de un mes ya pagado (test_06) no lo hace la regla nueva de T77
  sino el cobro, que ya lo frenaba; la regla nueva solo mira el mes en curso.
- Comprobado aparte: un mes pasado sin deuda mandado a mano también se rechaza (T58: «La crea el dueño desde la
  ficha»), sin cuota ni pago. Sin hueco.

## 2026-10-25 — Claude CAB — Verificación de T78 (Gemini): aprobada

- Motivo del cancelado visible en todo estado de caja, para operativa y dueño, escapado. Ficha: total igual a
  Cobranza (también con la misma persona en dos deportes), vencimiento desde el servicio, un solo Cobrar.
- Rápida 922/6 omitidas, 0 fallidas; 5 pruebas propias. 4 observaciones para Carlos, no defectos.
- Asignado por Carlos: T77 la verifica Gemini, T79 y T80 Codex; prompts pasados. [Informe](../06-pruebas/PRU-04/VERIFICACION-T78-CLAUDE.md).

## 2026-10-25 — Claude CAB — T79: Reportes y Cashflow rápidos con dos años de datos

- Reportes releía la historia 8 veces por pedido: `enLote()` la lee una. Cashflow calculaba el reporte
  entero para los saldos: `saldosPorCaja()` suma en la base y lo usan los dos. Cobranza no relee deudas.
- Dos años de datos: Reportes 22,3 → 2,6 s; Cashflow 3,0 → 0,1 s; Cobranza ~1,15 s; Inicio 1,4 s.
- Mismos números: volcado completo antes/después (52 reportes, saldos con pendientes, Cobranza), igual.
- Completa 1037/6 omitidas, 0 fallidas; +4 pruebas (lento). Corte 1047. Sin desplegar. [Informe](../06-pruebas/PRU-04/IMPLEMENTACION-T79.md).

## 2026-10-25 — Claude CAB — T80: la política de contenido bloquea

- `SecurityHeaders`: misma política, ahora `Content-Security-Policy`. Pasos de AGENTS §2 cumplidos: en el
  sitio, cero reportes desde que T73 se desplegó (21/10 14:04; de los anteriores, 10 de `/login` y 11 de otras
  vistas que T73 limpió; corregido por Codex, yo había dicho «todos de login» sin mirar la página);
  local en bloqueo, Chrome sobre 59 pantallas de tres roles con dos años de datos: 0 bloqueos, 0 errores JS.
- Encontrado de paso: texto donde va un número (`/alumnos/abc`) daba 500. `Route::patterns` → 404. +2 pruebas.
- Rápida 922/6 omitidas, 0 fallidas. Corte 1043. Sin desplegar. [Informe](../06-pruebas/PRU-04/IMPLEMENTACION-T80.md).

## 2026-10-25 — Claude CAB — Días 5 y 6 revisados; prueba técnica hecha

- Días 5 y 6 (Codex): plata exacta. Defectos: fecha mínima con el reloj real en Clases y Alumnos
  (`date('Y-m-d')`), y el detalle de caja sin el motivo del cobro cancelado. «Sin asistencia 34» fue error
  de mi guion. Subidos a GitHub con mi revisión.
- Prueba técnica (`PRU-04/prueba-tecnica/`): interno 16/16 en el sitio (solo lectura); seguridad 7/7 en base
  descartable, con la política de contenido solo en modo informar; performance con dos años de datos:
  Reportes 22 s (evolución recalcula 6 veces cargando toda la historia), Cashflow 3 s, otras 1,4–2,8 s.
- La copia de la base del sitio la frenó el control de permisos (credenciales del servidor); no se forzó.
- Error mío: no había hecho la prueba técnica pedida el 24/10 por esperar un permiso sin volver a pedirlo.

## 2026-10-24 — Claude CAB — Dónde está la prueba por días

- Pedido de Carlos: saber qué se hizo en los días 1 a 4, qué dio, qué se hizo con eso, qué casos no se
  probaron, y armar el día 5. `ESTADO-PRUEBA.md` en PRU-04.
- 4 días, 35 de 331 casos ejercitados. Defectos de los días 1 a 3 corregidos (T16, T17, T19, T29, T18,
  T21, reloj). Sitio de prueba en `56379295`, igual a `main` en código (leído por SSH).
- D4-OBS-01 medido en el sitio (solo lectura): 63 de 101 activos sin cuota de octubre guardada; Cobrar
  les ofrece octubre y muestra «Total pendiente» en rojo (COB-028 + cuota en memoria). Espera decisión.
- Día 5 propuesto (15 situaciones) y plan de prueba interna mía; esperan aprobación de Carlos.

## 2026-10-22 — Claude CyE — Prueba manual: no se podía ingresar al sitio de prueba

- Causa comprobada: reloj simulado en 13/10 18:25 y sesión de 120 min → cookie `wings-session` con
  vencimiento 13/10 y `Max-Age=0`; el navegador (22/10) la borraba y el login volvía mudo.
- Arreglo solo en el sitio de prueba: `SESSION_EXPIRE_ON_CLOSE=true` en `.env` (copia previa en
  /root/respaldos/wingstest-env-2026-10-22) + config:cache. Mismo recurso que en T74.
- Comprobado: la cookie ya no vence; un ingreso con clave equivocada vuelve con «credenciales ingresadas no
  son válidas» (antes, sin mensaje). `XSRF-TOKEN` sigue vencida; el formulario no la usa.
- Anotado en INSTRUCCIONES-PRUEBA-GEMINI §4. Producción no usa reloj simulado: no le afecta.

## 2026-10-21 — Claude CAB — Cierre de sesión: lo que queda abierto

**Resuelto, no pendiente**
- T42 (asientos viejos): decisión de Carlos ya tomada (conservar el mes original solo en lo no
  identificable), implementada y verificada; T42 cerrada. Error mío haberlo vuelto a preguntar.
- T61: las observaciones que no pidió Carlos no se hacen.

**En curso de otros**
- **T50:** Codex verifica la segunda vuelta de Gemini (H2 y H3; H1 lo resolvió Carlos).

**Para revisar**
- `ClaseSinAsistenciaYDniT34T67Test` reescribe `evidencia-correccion-t34/sin-presentes.html` en cada
  suite: ensucia la carpeta de todos. Hasta corregirlo, devolverlo con `git checkout --`.
- Al integrar T61 corrí `DocumentacionNoMienteTest` sin `DB_DATABASE`: no usa base y `phpunit.xml`
  apunta a `wings_testing`, así que no hubo daño; igual, siempre con la base propia.

Bitácora archivada: [entradas del 19/10 y 20/10](../99-archivo/bitacoras/2026-10-21/LOG-CLAUDE.md).

## 2026-10-21 — Claude CAB — T61 verificada, integrada y cerrada

- Segunda vuelta de Codex: mis controles de la primera vuelta, sin cambiarlos, sobre el código de su
  rama: H1 y H2 rechazados, H3 rechazado sin tocar el gasto. Sus 16 pruebas de T61 en verde.
- Carlos aprobó el visual y pidió integrar. Rama `codex/t61-segunda-vuelta` unida a `main` con la
  segunda vuelta de T50 de Gemini (que estaba en la carpeta sin subir). Conflictos solo en el
  contador (cada lado contaba lo suyo: real 1028) y en el tablero (regenerado).
- Pendiente de nadie: T50 sigue a verificar por Codex.

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

## 2026-10-21 — Claude CyE — Instrucciones para que Gemini escriba la prueba por días

- Pedido de Carlos: un archivo (no un prompt) con lo que hicimos y cómo, los objetivos y el calendario.
  `docs/06-pruebas/PRU-04/INSTRUCCIONES-PRUEBA-GEMINI.md`: roles, tabla de pruebas, días 1–3, tareas
  cerradas desde el día 3 (sacadas del tablero), estado del sitio, calendario 13/10–2/11 con lo que debe
  pasar, recorrido previo de casos, formato de cuentos y de OBJETIVOS-PRUEBA.md, reglas.
- Quedan para Carlos y Gemini: alcance del recorrido, registro escribible, producción, verificador,
  mora en 0% en el sitio de prueba y avisos reales (Telegram y correo de Carlos configurados en prueba).

## 2026-10-21 — Claude CyE — Sitio de prueba desplegado en 56379295

- Pedido de Carlos, tras cerrar Codex T50. Antes: 3773c0b (16/10), 173 cambios atrás.
- Respaldo previo en el servidor: /root/respaldos/wingstest-antes-despliegue-2026-10-21.sql.gz.
- down → merge --ff-only → composer --no-dev → npm ci/build → migrate (8 nuevas, todas DONE) →
  cachés → up. /login 200. generaciones_cuotas anotó 2026-10; 100 alumnos con grupo_desde vacío
  (vale el ingreso); 3 reglas de primer pago; 0 migraciones pendientes.
- Reloj del sitio: hora real (21/10); la prueba lo fija en 13/10 con `wings:fecha-simulada`.
  El scheduler de prueba corre cada minuto (crontab de wingstest) con ese reloj.
- Incluye T52 sin la segunda revisión de Codex (Carlos lo sabe). Producción no se tocó.

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
