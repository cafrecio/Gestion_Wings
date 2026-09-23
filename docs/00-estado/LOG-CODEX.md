# Wings — Bitácora activa de CODEX

## 2026-09-23 — Codex CAB — PRU-02 cron de test probado; avisos pendientes

Solo wingstest; cron preexistente reemplazado por ejecutor aislado de producción.
Ejecuciones reales 03:28, 03:29 y 03:30 UTC correctas; nologin y password bloqueada.
Instalación repetible integrada a montar-test; sin heartbeats, destinos CSP propios.
Email destino guardado en Configuración; Telegram sin chat verificado, sin envío.
Auto-review bloqueó token/transporte: autorización pendiente; Carlos debe iniciar bot.
Suite aislada 315/1804, 93,45 s; bash -n correcto, sin cambios visuales ni producción.
[Evidencia](../06-pruebas/PRU-02-AUTOMATIZACION-TEST-2026-09-23.md). Siguiente: probar recepción real de ambos avisos.
[Entrada antigua preservada](../99-archivo/bitacoras/2026-09-23-LOG-CODEX-TEST-CRON.md).

## 2026-09-22 — Codex CAB — ENT-01 implementada y verificada

Carlos aprobó DNI por persona, inscripción primero, sin comisión y edición auditada.
Alta/cargo atómicos, reintentos y concurrencia; caja, estado de cuenta y PDF desglosados.
§5 de Punitorios usa cargos; FIN-14 y manuales ENT-10 siguen pendientes. Sin deploy.
Pruebas iniciales rojas; suite final aislada: **313 / 1793**, 122,92 s, MariaDB.
Aviso previo, cobro parcial/completo y PDF vistos con datos ficticios; build y Blade correctos.
[Evidencia y límites](../06-pruebas/ENT-01-INSCRIPCION-2026-09-22.md); código guardado en 11623b6 por sesión paralela.
Siguiente: revisión cruzada de Claude y actualización del servidor para PRU-02.
[Entrada antigua preservada](../99-archivo/bitacoras/2026-09-22-LOG-CODEX-ENT01-CIERRE.md).

## 2026-09-22 — Codex CAB — ENT-01 propuesta previa a implementación

Pull inicial sin novedades; corte confirmado 23/09/2026, por fecha real de ingreso.
[Propuesta revisable](../05-pendientes/ENT-01-PROPUESTA-CARGOS-ADICIONALES.md): cargos comunes,
imputaciones separadas, un pago/recibo y movimientos por concepto, sin duplicar caja.
Contraste de fuentes: punitorios §5 requiere enmienda; pagos afectan estado y comisión.
Decisiones abiertas: parciales, identidad por persona/deporte, fecha y permiso visual.
Índice actualizado y cuerpos reales leídos; enlaces/diff revisados, sin suite ni base.
Sin código ni deploy. Siguiente: revisión de Carlos antes de pruebas en rojo e implementación.
[Entrada antigua archivada](../99-archivo/bitacoras/2026-09-22-LOG-CODEX-ENT01-PROPUESTA.md).

## 2026-09-22 — Codex CAB — FIN-04 definición funcional cerrada

Carlos confirma gastos del club separados, sin descontarlos del resultado por deporte.
Sí se descuentan del total del negocio; enmienda en el contrato de Reportes V1.
Saldo acumulado, resultado del período y proyecciones separados; confirmados/sin confirmar.
Ambos tableros, checklist, estado, plan de producción, resumen y encuesta actualizados.
Cierre documental de definición; POS-01 sigue pendiente, sin código ni despliegue.
Verificación de enlaces, coherencia de estado, diff y ausencia de cambios de diseño.
[Entrada anterior archivada](../99-archivo/bitacoras/2026-09-22-LOG-CODEX-FIN04.md).
Siguiente: continuar diseño e implementación de Reportes según pedido de Carlos.

## 2026-09-22 — Codex CAB — MUY IMPORTANTE: inscripción por fecha real de ingreso

Carlos decide carga manual por usuarios; no modo temporal ni pregunta nuevo/antiguo.
Ingreso anterior al corte fijo no genera inscripción; desde el corte sí, una sola vez.
Valor obligatorio configurable inicial $5.000; deuda al alta, cobro con primera cuota.
[ENT-01 y ENT-10](../05-pendientes/ENT-01-INSCRIPCION-Y-PRIMERA-CARGA.md): decisión y manuales pendientes.
Manuales deben destacar ingreso real versus fecha de carga, con ejemplos claros.
Falta confirmar fecha concreta de inicio; no inventarla. Propuesta técnica, sin código.
Tableros y resumen enlazados; verificación documental, sin suite ni base.
[Entrada antigua conservada](../99-archivo/bitacoras/2026-09-22-LOG-CODEX.md).

## 2026-09-21 — Codex CyE — POS-07: plan de canchas y liquidaciones

Entrevista preservada en [plan v2026-09-21](../07-evaluacion/PLAN-CANCHAS-LIQUIDACIONES-v2026-09-21.md): ocho etapas y matriz de aceptación.
Ubicaciones/canchas/tarifas, bloques completos, manual protegido y costos solo ADMIN.
Liquidar mes o fechas elegidas: solo clases dictadas al corte, sin repetir liquidadas aunque estén sin pagar.
Cancelación operativa conserva su permiso; decisión de pagar pendiente del ADMIN, sin exponer costos.
Índices IA/HTML, encuesta, estado y resumen sincronizados; Reportes V1 conserva antecedente señalado.
Fuente revisada hasta 49f66e9; codebase-memory como mapa, cuerpos reales cotejados. Sin código ni base.
Verificación documental: enlaces, IDs, diff y archivo íntegro de la entrada antigua; no suite ni migraciones.
Siguiente: Carlos ordena implementación por etapas; Reportes general conserva su pregunta pendiente.

## 2026-09-21 — Codex CyE — Reportes: gastos del club y mejora futura

Carlos decidió mostrar gastos generales aparte como «Gastos del club» al filtrar
por deporte, sin repartirlos ahora. Costo de cancha por clase: mejora futura POS-07,
sin fórmula ni implementación definida. La propuesta distinta del contrato V1 queda
como antecedente; encuesta provisional, plan IA, HTML y resumen actualizados.
Pendiente: decidir si esos gastos se restan del resultado de un deporte filtrado.
Solo documentación; sin pruebas de aplicación ni operación sobre bases.

## 2026-09-21 — Codex CAB — encuesta de Reportes preservada

Carlos pausó la entrevista y pidió guardar, commitear, subir y actualizar desde GitHub.
Decisiones en [archivo provisional removible](../05-pendientes/ENCUESTA-REPORTES-PROVISIONAL.md).
Incluye períodos, indicadores, roles, caja real/proyecciones y fechas de movimiento/rendición.
Pendiente: cómo mostrar gastos generales al filtrar por deporte; no decidir reparto.
Documentación únicamente; cambios FIN-12 ajenos quedan fuera del commit.
Verificación: enlaces, diff y conservación íntegra de dos entradas antiguas archivadas.
[Archivo de entradas del 11/09](../99-archivo/bitacoras/2026-09-21-LOG-CODEX.md).
Siguiente: retomar desde la pregunta guardada cuando Carlos indique; sin implementación.


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

## 2026-09-13 — Codex CAB — contrato de particulares y pendiente POS-06

Objetivo: conservar la entrevista de Carlos en documentación compartida.
Contrato V1 redactado; POS-06 pendiente en ambos tableros y ficha de pendientes.
Incluye crédito consumible con recibos, permisos, asistencia, cancelaciones y avisos privados.
Enmienda expresa: ADMIN cancela cerrada no pagada; pagada solo ajuste posterior.
Contratos relacionados, índice, estado y resumen enlazan la decisión, sin afirmar implementación.
Verificación documental de enlaces, diff y correspondencia de POS-06; sin suite ni base.
Se conservan cambios previos en LOG-CODEX, evidencia FIN-10 y configuración ajena.
Seguimiento: FIN-12 priorizada para hoy por Carlos, pendiente en ambos tableros con instrucciones de implementación. Se cotejó eliminarLiquidacion con fuente local; quitar el rechazo y borrar no preserva historia. Siguiente: FIN-12 cuando se ejecute; entrevista de Reportes pendiente. Sin código en estos turnos.

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-CODEX.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).
· [Entrada de continuidad archivada el 21/09](../99-archivo/bitacoras/2026-09-21-LOG-CODEX-CONTINUIDAD.md).
· [Entrada antigua preservada al guardar POS-07](../99-archivo/bitacoras/2026-09-21-LOG-CODEX-PLAN-CANCHAS.md).
