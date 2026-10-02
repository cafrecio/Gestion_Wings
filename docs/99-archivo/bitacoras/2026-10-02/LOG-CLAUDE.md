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
