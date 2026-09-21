# Wings — Bitácora activa de CLAUDE

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-CLAUDE.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).
· [Entradas archivadas el 17/09](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE-2.md) · [Entradas archivadas el 21/09](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE.md)

## 2026-09-21 — Claude CyE — FIN-08 cerrada: la revision la hace el operativo

Carlos: si solo el admin puede hacer cosas, los otros roles no tienen sentido.
Ruta de revision (ver y resolver) pasada de `ensure.admin.web` al grupo que comparten
ADMIN y OPERATIVO, como Cobranza. Enlace del menu movido de "Plata" (admin) a "Dia a dia",
mismo `ds-nav-link` e icono; diseno autorizado por Carlos. Condonar sigue solo ADMIN.
Parcial y precio: Carlos acepta que ya no aplican (nada pisa un parcial; precio vigente, igual
que la generacion mensual). Se retiro la prueba que exigia "la revision es del admin".
`RevisionCobranzaOperativoTest` 6 pruebas, 4 fallan con la ruta vieja. Suite 279/1585.
PERMISOS-ROLES con el caso nuevo. Sin deploy. Enfasis de Carlos: todo retoque de diseno de
la prueba grande respeta siempre el design system (anotado en PRU-02).

## 2026-09-21 — Claude CAB — FIN-12 verificada, y el sistema de avisos

**FIN-12 (Gemini).** Suite completa 274/1567 verde. Dientes comprobados sacando la
logica y dejando la migracion puesta: **las 11 pruebas fallan**, incluidas las dos
de concurrencia con conexiones reales en los dos ordenes (pago contra cancelacion).
Se eligio un estado `CANCELADA` con auditoria (quien, cuando, motivo) y vinculo a
la liquidacion que la reemplaza; no borra nada. `validarNoExisteLiquidacion` excluye
canceladas, asi se puede rehacer el mes, y `obtenerResumenPeriodo` no suma sus
importes. Cancelar desbloquea las asistencias para corregirlas; mientras siga
cerrada siguen bloqueadas para todos.
**Toco dos vistas de liquidaciones sin pedir la autorizacion de diseno**, que el
prompt le pedia: etiqueta Cancelada, filtro, bloque de auditoria y boton. Carlos lo
autorizo el 21/09 despues de ver el detalle, al pedir que se subiera todo.

**Avisos operativos (mio).** Wings no mandaba ningun aviso: lo unico que avisaba
eran los scripts del servidor, y esos son fallos de infraestructura que le llegan a
Carlos. El destino de estos otros vive en `configuraciones` y no en el `.env`,
porque es del club: el ADMIN lo cambia desde la pantalla sin entrar al servidor. El
token del robot si queda en el `.env`, que es credencial y no destinatario.
Un aviso nunca voltea la operacion: se avisa despues de registrar la plata y todo
va dentro de un try con 4 segundos de limite. Sincronico y no por cola porque hoy
no hay worker: una notificacion encolada no saldria nunca. Enganchado en los cuatro
puntos de FIN-09. Una prueba encontro un defecto antes de subirlo: con tres
administradores salian cuatro mensajes al mismo chat.

**FIN-08 parcial.** "Inactivo" ya no condona el mes anterior. Quedan: abrirle la
pantalla al OPERATIVO (decidido el 17/09, la ruta sigue en `ensure.admin.web`) y
dos definiciones de Carlos, precio de que mes y pago parcial.

**FIN-14 registrada.** Punitorios: el contrato estaba escrito desde el 06/09 y la
tarea nunca habia entrado al plan.

## 2026-09-17 — Claude CyE — cierres y decisiones de Carlos del dia

PRU-01 cerrada: la base de la prueba es `PrimeraCargaCompletaSeeder` (60 alumnos, sin deudas);
el primer paso de PRU-02 es cargar la deuda con el padron, que son comandos de consola.
FIN-12 asignada a Gemini. Reportes: buscada la entrevista que Codex dejo pendiente el 13/09
en ramas, commits, bitacoras y sesiones locales de Codex; **no existe registro**, y las
sesiones de Codex de esta maquina sobre Wings terminan el 11/09. Si ocurrio, fue en CAB.
FIN-09 decidida por Carlos: sin fechas futuras; mas viejo se carga con su fecha real y se
liquida con la caja de hoy, avisando al cargar y por mail y Telegram; el reporte del mes
viejo cambia y eso se acepta. Laravel hoy no envia mail ni Telegram: solo el servidor.
FIN-08: Carlos define la revision de cobranza como tarea del OPERATIVO y el codigo la tiene
solo para ADMIN; registrado en ESTADO-ACTUAL §8. Traba: "Inactivo" condona, y condonar es
solo ADMIN. Propuesta a Carlos, sin respuesta todavia: que "Inactivo" de de baja sin condonar.
Prompt de la parte de diseno de FIN-13 pasado a Gemini. Sin codigo nuevo en estos turnos.

## 2026-09-17 — Claude CyE — orden: test.gestionar-te se actualiza al final

Carlos: actualizar test deja de ser la tarea 1. Se hace una sola vez, cuando este la
version que se va a probar. Antes: FIN-12 y la parte de diseno de FIN-13 (prompt pasado a
Gemini: recibo y pantalla con "1 h 20 min", centavos y "Valor por hora"). Esto reemplaza
el orden de la entrada "PENDIENTES comunes" del 16/09. Checklist §0 actualizado.

## 2026-09-17 — Claude CyE — FIN-13: profesores por hora cobran por duracion

Decision de Carlos: clase de 1,5 h a $5.000 = $7.500; base generica para cualquier club.
Un solo calculo (`montoPorClase`: tarifa x minutos / 60) y una sola consulta de clases para
liquidacion y vista previa, que antes eran dos copias. Tarifa congelada en
`liquidaciones.valor_hora_aplicado`, minutos en `liquidacion_detalles.minutos`. Clase sin
duracion valida frena con fecha y grupo, no inventa una hora. Migracion no recalcula lo
liquidado: tarifa = importe pagado por clase. Recibo y pantalla toman la tarifa congelada.
`LiquidacionHoraPorDuracionTest` 6 pruebas; 5 fallan con el servicio anterior. Suite 235/1402.
Pendiente: OK de diseno para mostrar "1 h 20 min" y centavos (recibo dice "1.3 hs"). Sin deploy.

## 2026-09-17 — Claude CyE — codebase-memory-mcp instalado y obligatorio para buscar

Carlos pidio usar el MCP para analizar y buscar en el repo. El instalador se leyo antes de
correrlo: baja de las publicaciones oficiales, verifica SHA-256 y no pide administrador.
Instalado 0.11.0 en CyE (`VENTAS_CYE`) para Claude Code, Codex y VS Code; Gemini no detectado.
Repo indexado: 5.075 nodos, 13.013 relaciones; una consulta real coincidio con el archivo.
Limite: no parsea completas `caja/detalle`, `caja/resumen` y `configuraciones/index`.
Regla en `AGENTS.md` §6e y `CLAUDE.md`; paso por maquina en checklist §1; indice ignorado por Git.
Pendiente: reiniciar sesiones para que lo tomen, e instalarlo en CAB.
Esta bitacora superaba lineas, caracteres y entradas: 13 entradas viejas archivadas intactas.
Correccion de firma: las entradas del 09 al 11/09 de esta sesion como Claude CAB corrieron en `VENTAS_CYE`.

## 2026-09-16 — PENDIENTES comunes (registrado por Claude CAB a pedido de Carlos)

Misma entrada en los tres logs, para que cada agente arranque con la lista.
Corte: `main` con todo subido; suite 229 pruebas / 1374 aserciones, verde el 16/09.

**Cerrado desde el 13/09:** FIN-06 (Gemini), verificacion cruzada de FIN-10 y FIN-03
(Gemini), ENT-02 recibos de cuota y liquidacion (Gemini, commiteado el 16/09),
SEG-06, SEG-07 y `report-uri` de CSP (Claude), `test.gestionar-te` montado (Claude).

**Pendiente, por orden:**

1. **Actualizar `test.gestionar-te`** — Claude. Esta en `2fccacb`, **sin los recibos
   nuevos**: ENT-02 no estaba commiteado cuando se actualizo. Correr
   `montar-test.sh` y `montar-test-https.sh`.
2. **FIN-12** cancelar liquidacion cerrada no pagada — sin asignar (propuesta:
   Gemini). Contrato enmendado en Liquidaciones §2.4; instrucciones en
   `docs/05-pendientes/FIN-12-CANCELAR-LIQUIDACION-CERRADA.md`.
3. **Prueba grande PRU-02** en `test.gestionar-te` — Gemini, despues de 1 y 2.
   Entrar con `admin@wings.test` / `PruebaWings2026`. No resetear la base por su
   cuenta: pedirlo a Claude.
4. **Ensayo de restauracion contra un respaldo real del servidor** — Claude, no
   toca nada. Hasta hacerlo, que el respaldo sirva no esta demostrado.
5. **Desplegar en wings** — despues de que pase 3. Respaldo manual antes y con
   Carlos presente. Migraciones pendientes: `detalle_anulacion`,
   `motivo_cambio_horario`, `porcentaje_comision_aplicado` (ya probadas en test).

**Clases particulares** — Codex. Contrato en `Wings-Contrato-Clases-Particulares-V1.md`.
Va en rama aparte; **falta que Carlos decida si entra antes o despues de la prueba grande**.

**Decisiones de Carlos que no bloquean la prueba:** FIN-04 (balance), FIN-08
(revision con parciales), FIN-09 (limites de fechas), PRU-03 (DEUDOR sin pagos),
ENT-01 (inscripcion), liquidacion por hora: por clase o por duracion, `monto_base`.

**Sin asignar, no bloquean:** SEG-10 integracion continua; SEG-11 resto (22 bloques
`<script>`, uno por archivo segun DESIGN-RULES §8); ENT-06/07/08 despues de la prueba.
