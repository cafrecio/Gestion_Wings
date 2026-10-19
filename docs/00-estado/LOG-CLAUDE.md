# Wings — Bitácora activa de CLAUDE

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-CLAUDE.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).
· [Entradas archivadas el 17/09](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE-2.md) · [Entradas archivadas el 21/09](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE-2.md) · [Entradas archivadas el 02/10](../99-archivo/bitacoras/2026-10-02/LOG-CLAUDE.md) · [Entradas archivadas el 10/10](../99-archivo/bitacoras/2026-10-10/LOG-CLAUDE.md) · [Entradas archivadas el 13/10](../99-archivo/bitacoras/2026-10-13/LOG-CLAUDE.md) · [Entradas archivadas el 05/10](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE-2.md) · [Entradas archivadas el 07/10](../99-archivo/bitacoras/2026-10-07/LOG-CLAUDE.md) · [Entradas archivadas el 08/10](../99-archivo/bitacoras/2026-10-08/LOG-CLAUDE.md) · [Entradas archivadas el 12/10](../99-archivo/bitacoras/2026-10-12/LOG-CLAUDE.md) · [Entradas archivadas el 16/10](../99-archivo/bitacoras/2026-10-16/LOG-CLAUDE.md)

## 2026-10-18 — Claude CAB — T39 y T75 cerradas

T39: Gemini la aprobó pero no commiteó (el tablero estaba tomado por Codex); cuando Codex publicó
T75, subí su informe, capturas y bitácora tal como estaban y cerré la tarea. T75: el visual lo
aprobó Carlos; controlé en Chrome la interacción nueva de Revisión: lo que muestra el interruptor
y lo que se envía coinciden al abrir, al apagarlo, al volver con Atrás y al recargar; Inactivo da
de baja sin cuota y Continúa genera la cuota; sin nota no envía. Cerrada.
`VERIFICACION-T75-REVISION.md`.

## 2026-10-18 — Claude CAB — T58 hecha; T63 y T57 verificadas y cerradas

T58 (DU-23): la ficha muestra al dueño los meses pasados sin cuota (desde el alta, con plan) y los
crea al precio del plan, como el generador; la operativa recibe 403. 8 pruebas; recorrido en Chrome
(1366 y 375, confirmación y aviso) en `evidencia-t58-claude/`; suite 858/3 omitidas, solo el
contador, ajustado a 862. A verificar por Gemini; falta el visual de Carlos. T63 y T57 (Gemini):
reglas verificadas (167 pruebas y 2 controles míos: cambiar el medio no toca el arqueo de cajas
existentes; una clase de hoy cuenta como futura), aspecto aprobado por Carlos. Cerradas.

## 2026-10-18 — Claude CAB — T39 hecha; T58 sin la pantalla todavía

T39 (DU-21): la anulación de un cobro del dueño lleva la fecha del cobro original. Carlos decidió
hoy que la deuda (cuota, inscripción, recargo) vuelva en ese mismo mes; se quitó de Reportes la
reconstrucción que seguía viendo pagado el recargo. Dos pruebas viejas (A13, D4) controlaban la
regla anterior y ahora controlan la nueva. 5 pruebas nuevas; suite 856/3 omitidas, solo falló el
contador de documentos (ajustado a 860). A verificar por Gemini. T58: servicio, ruta y 6 pruebas
listos; falta la ficha, que Codex tiene tomada por T75.

**Dos errores míos:** commiteé la toma de T57/T63 de Gemini junto con la mía (encadené
`git status` con el comando del tablero sin mirar), y corrí pruebas sobre `wings_testing_claude`
mientras corría mi propia suite en la misma base. Nunca dos corridas a la vez sobre una base.

## 2026-10-18 — Claude CAB — T36 cerrada; T41 lógica aprobada, espera el visual de Carlos

T36 (Codex, `8a7d102`): tilde por movimiento con huella del contenido; validar exige todos,
también los cancelados, en el servicio. 14 pruebas de Codex, 162 de caja/Cashflow/reportes y 3
mías verdes; aspecto aprobado por Carlos («visualmente están bien»). Cerrada. T41 (Gemini,
`ee65b57`): la regla vive en `cerrarLiquidacion`, el único lugar que cierra; 62 pruebas de
liquidación y 3 mías (diciembre a enero, corte en hora de Buenos Aires con el servidor en UTC)
verdes. Queda para Carlos el texto que reemplaza al botón Cerrar. Mis controles se corren desde
`evidencia-*-claude/`, no desde `tests/`.

**Error mío que no se repite:** el fallo de T38 en la suite de Codex (4000 contra 7000) lo causé
yo al romper código a propósito en la carpeta compartida mientras él corría pruebas. Las
mutaciones van en una copia aparte, nunca en la carpeta que usan los otros agentes.

## 2026-10-17 — Claude CAB — T38 hecha: reactivar genera la cuota del mes

DU-12 y, al empezar, Carlos: la cuota del reingreso tiene el plazo de un alta. Reactivar (ficha o
listado) crea en una transacción la cuota del mes con el porcentaje del día, salvo que ya exista;
sin plan no reactiva. `deuda_cuotas.fecha_reingreso` da el plazo a esa cuota sola (estados y mora,
vía `CobranzaEstadoService::limiteDeLaCuota`). El cobro que reactiva a un inactivo completa lo
mismo. Ajuste en el caso 1 del primer pago para no recalcular un reingreso en el mes del alta.
18 pruebas; mutaciones de las cuatro piezas detectadas. Suite 819/2 omitidas/0 fallidas (con
T36 y T41 sin commitear en la carpeta). Tabla viva y copia sincronizadas (ALU-018, COB-028, y de
paso CAJ-029 y PEN-001). Sin tocar vistas. Después, en `3cef48b`, T38 pasó a verificar (Gemini) y
el número de pruebas de lo publicado quedó en 804, armando el commit desde la versión publicada
de esos archivos para no llevarme lo de Codex y Gemini; en sus copias de trabajo agregué la línea
de T38 y la entrada de ESTADO. Recorrido en Chrome sobre `wings_testing_claude` (ficha, interruptor
del listado, Cobranza, 375 sin desborde): `evidencia-t38-claude/`. Suite final sobre `544ace4`, con
T36 y T41 ya publicadas: 821 aprobadas, 2 omitidas, 0 fallidas. `IMPLEMENTACION-T38.md`.

## 2026-10-17 — Claude CAB — T37 verificada y cerrada; T35 cerrada con el OK de Gemini

T37 (Codex): motivo obligatorio en controlador y servicio, espacios solos cuentan como vacío,
máximo 500 en columna TEXT, error visible arriba de Detalle y Resumen. 8 pruebas de Codex, 22 de
A25 y 3 mías verdes (operativa no rechaza, 500 eñes, recorte). El aspecto ya lo aprobó Carlos.
`VERIFICACION-T37.md`. Queda fuera: el rechazo en el Inicio de la operativa (CAJ-029 c).
T35: Gemini la aprobó en `491dd46` (`VERIFICACION-T35-GEMINI.md`) pero no movió el tablero; la
cierro con su veredicto. T10 pasó a Carlos en el tablero.

## 2026-10-17 — Claude CAB — T10: borradores legales sin abogado

Carlos: «actuá como abogado», buscar lo mínimo exigible; abono acordado USD 30 por mes.
`docs/01-producto/legal/`: condiciones de servicio con anexo de encargo de tratamiento (art. 25),
política de privacidad con la leyenda de la AAIP (Res. 14/2018) y la de la ficha de inscripción
(consentimiento para transferir fuera del país, Decreto 1558/2001 art. 12), y cookies (solo
necesarias, sin cartel). Plazos de los arts. 14, 16 y 25 confirmados en el texto oficial. Escritos
sobre los datos reales de alumnos, profesores y usuarios. Pendientes de Carlos en el checklist:
datos entre corchetes, moneda de pago, IVA, país del servidor, inscripción en la AAIP. El enlace
desde el ingreso queda para otra tarea. Tablero de T10 sin actualizar: tareas.json tenía cambios
sin commitear de Codex y Gemini.

## 2026-10-17 — Claude CAB — T48/T49 verificadas (Gemini); T35 la verifica Gemini

`e4c2424` leído completo. T48 renombra el mismo subrubro en una transacción; T49 frena
desactivar y cambiar rol con caja ABIERTA, por JSON y redirección. El agregado en `ds-app.js`
toca todos los interruptores: solo usuarios responde `{error}`, el resto no cambia. 7 pruebas de
Gemini y 4 mías verdes (Cashflow muestra el sueldo viejo con el nombre nuevo; nombre y rol juntos;
choque solo en mayúsculas; reactivar). T49 cerrada. T48 espera a Carlos: qué pasa si el `Op-`
nuevo ya existe (hoy no guarda y avisa). `VERIFICACION-T48-T49.md`. Por pedido de Carlos, T35
pasa a verificarla Gemini en vez de Codex.

## 2026-10-17 — Claude CAB — T35 hecha: asistencia solo desde el día de la clase

Decisión de Carlos DU-35. `Clase::todaviaNoOcurrio()` frena `storeAsistencias` y
`ClaseService::registrarAsistencia`; la pantalla de una clase futura avisa, deshabilita Presente
y saca Guardar (el botón tiene estilos en línea y deshabilitado se veía igual). Límite por fecha,
00:00 hora de Wings. 8 pruebas verdes. Suite: 762/2 omitidas/15 fallidas, ninguna de T35: 14 por
el `public/build` viejo de CAB (faltaba `alumnos-show.js` de B11; `npm run build` local) y el
contador de documentos movido por Codex y Gemini trabajando en la misma carpeta; repetidas, 73
verdes. **Sin verificar:** la pantalla en navegador (no hay clases futuras en la base local y no
creé datos). Pasa a Codex. `8f2be1f`, `IMPLEMENTACION-T35.md`. En paralelo: Codex T37, Gemini T48/T49.

## 2026-10-16 — Claude CyE — Corte para seguir en CAB

Publicado todo, sitio de prueba en `3773c0b`. Cerradas hoy: T2, T3, T4, T7, T11, B11 (B13 y B7 por
decisión). **Esperan a Carlos:** T35 para Gemini (le recomendé esa, no choca con nada; falta su sí y
el prompt); autorización para leer la base de producción, solo lectura, y armar el paso a producción;
prender o no el porcentaje de mora en el sitio de prueba. **Mía:** T10 (privacidad, tres borradores).
Repaso de tareas sin empezar: de a una, sigue en B12 en adelante del tablero.

## 2026-10-16 — Claude CyE — B11 verificada, publicada y desplegada

Punitorios por mora (Codex, aspecto aprobado por Carlos). Leí todo el código y probé por los
caminos de la gente: porcentaje cargado por pantalla (12,5 % no es 12), una sola vez, congelado,
0 % no existe, D1 a D4, cobro parcial y completo por pantalla con cada importe en su rubro,
anulación y perdón. 6 controles propios y 35 de Codex verdes; suite 761 aprobadas, 2 omitidas.
`PRU-04/VERIFICACION-B11.md`. Publiqué los cuatro commits locales de Codex y lo desplegué al sitio
de prueba con el porcentaje en 0: no cambia nada hasta que Carlos decida prenderlo.

## 2026-10-16 — Claude CyE — T4 cerrada con la repetición de Gemini

Gemini repitió en el sitio los cuatro casos pendientes (`3f0999c`, `REPETICION-T4.md`): ingreso
de varias personas seguidas (D02-04) y clases canceladas (D03-01 a 03). Pasan. Controlé: las dos
cancelaciones están en la base del sitio (clases 20 y 79, 10:10, con sus motivos) y miré las
capturas 06, 13, 14, 16 y 17. Dos reparos que no cambian el resultado: la captura 13 no muestra
la clase cancelada (queda abajo, fuera del cuadro; sí se ve en la 16), y el control de «Sin
asistencia» no prueba nada porque las clases eran de la tarde y todavía no habían ocurrido.
Las capturas repetidas byte a byte son pantallas idénticas. Tabla de pruebas actualizada.

## 2026-10-16 — Claude CyE — T3 segunda vuelta

Codex la devolvió con razón: cambiar el paso de un defecto cerrado movía su fecha de cierre a
la del cambio, y `TableroAlDiaTest` no lo veía porque compara con el mismo generador. Ahora cada
tarea guarda `cerrado_el` aparte de `fecha`; se carga al cerrar y se borra al reabrir. Las 67
fechas de cierre de defectos salieron del encabezado de antes de T3 (66) o del último cambio (B15,
que no tenía fecha). Lo cerrado conserva su encabezado escrito. Dos pruebas nuevas reproducen el
caso de A6 y fallan con el código anterior (comprobado). Suite: 727, 724 aprobadas.

## 2026-10-16 — Claude CyE — T3: el estado de los defectos sale del tablero

El estado de cada defecto se escribía a mano en tres lugares. Ahora `scripts/tablero` reescribe
el encabezado de cada uno en `DEFECTOS.md`, su tilde, sello y etiqueta en `DEFECTOS.html` y el
avance de los dos. El texto de cada defecto se sigue escribiendo a mano. Se quitó `traer`.
`TableroAlDiaTest` (3 pruebas) falla si un archivo generado no coincide con `tareas.json`;
probado: marcar B3 a mano en el HTML la pone en rojo. Suite en mi base: 721 aprobadas,
2 omitidas; fallaron el conteo de pruebas (corregido: 725) y `PerdonConcurrenteT33Test` por
tiempo de espera, que sola pasa 10 de 10. Verifica Codex.

## 2026-10-16 — Claude CyE — Tabla de pruebas al día

Carlos respondió DU-60: el Inicio de la operativa muestra los vencidos y, aparte, los que
están en plazo. Es T74 (caso nuevo CBZ-022). En la tabla, los 39 casos que seguían «a definir»
con su duda ya respondida pasaron a «no implementado» con el esperado reescrito según la
decisión (33) o a «vigente» cuando Carlos aceptó lo que hay (ACC-006, ASI-002, REP-015,
USU-012) o ya está hecho (CBZ-010, T33). Copia del repositorio rehecha. Corregida la dirección
de la primera carga en los documentos vigentes: es `/sistema/primera-carga`, no `/primera-carga`.

## 2026-10-15 — Claude CAB — Pasar a produccion lo de prueba: comparacion hecha, sigue en CyE

Pedido de Carlos (chat del 13–15/10): pasar a produccion lo que hoy funciona en prueba para
que Vanina haga la primera carga, sin esperar el gate completo de `PLAN-PRODUCCION.md`.
**Verificado por `ssh vps` (solo git):** produccion `/home/wings/app` en `314e485` (22/09);
prueba `/home/wingstest/app` en `d4f02d4` (13/10). Son **327 commits y 15 migraciones**, no
solo `create_primera_carga`: una reescribe datos (`clasificar_subrubros_existentes`), suma
`chart.js` y `locale` queda fijo en `es`. Se despliega `d4f02d4`, lo probado, no la punta de main.
**Sin verificar:** si produccion tiene datos reales y su `migrate:status`; la lectura de la base
la bloqueo el permiso automatico. Sigue: leer eso con autorizacion de Carlos, proponer pasos
(respaldo `/root/wings-backup/respaldar.sh`, checkout, composer, npm build, migrate) y esperar su OK.
Tambien: T10/T12 cargadas en el tablero (`8ad2d87`); trabajo de Codex T11 subido sin cambios (`adcb344`).

