# Wings — Bitácora activa de CODEX

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

## 2026-09-13 — Codex CAB — FIN-10 revisión visual completada

Chrome sobre base descartable wings_testing_fin10_visual_20260913 y puerto 8097.
Seeder autorizado y dos clases: pasada y de hoy. Pantallas revisadas con capturas.
Motivo visible solo en pasada; profesores deshabilitados allí y editables hoy.
Error sin motivo visible; con motivo guarda y muestra el nuevo horario en la ficha.
Evidencia actualizada en FIN-10-EDICION-2026-09-13.md. Sin deploy ni suite nueva.
Conteos 214/1311 intactos. Siguiente: revisión cruzada y despliegue cuando Carlos indique.

## 2026-09-13 — Codex CAB — FIN-10 implementada y probada

Carlos confirmó fecha pasada fija, horario con motivo y bloqueo por liquidación CERRADA.
Update transaccional: revalida todos los profesores y presentes al mover fecha/horario.
Clases pasadas ignoran profesores enviados; actualizarProfesores y cálculo de liquidación intactos.
Migración motivo_cambio_horario y ajuste autorizado de vista, sin JS ni CSS nuevos.
Suite completa única: 202 pruebas / 1275 aserciones, 48,87 s en wings_testing; 12 casos nuevos.
Sintaxis y compilación Blade correctas; tableros, contrato y tres conteos actualizados.
Sin deploy ni migración en base del club. Revisión visual en navegador pendiente.
Cambio ajeno .claude/settings.json excluido. Siguiente: revisión cruzada y despliegue.

## 2026-09-13 — Codex CAB — FIN-11 revalidada, pausa levantada

Carlos pidió actualizar y cerrar FIN-11; git pull confirmó repo actualizado en 18aa14a.
5f65dbd ya integrado. Revisados cuerpos de servicios, contrato Caja/Cashflow V4 y seis casos.
Suite reejecutada en wings_testing: 187 pruebas / 1206 aserciones, 42,40 segundos.
Sintaxis de cuatro PHP y compilación/limpieza de vistas correctas; sin cambios de diseño.
Ambos tableros, estado, checklist, resumen y evidencia actualizados para retirar la pausa.
Alcance: cobrar/cancelar/validar; recálculo/cierre de liquidaciones sigue pendiente separado.
Sin deploy ni operaciones sobre datos del club. Siguiente: revisión cruzada y despliegue.

## 2026-09-12 — Codex CAB — FIN-11 probada, cierre pausado por suite compartida

Seis cruces reales: inicialmente cuatro fallaron y dos pasaron sin revelar defecto.
Corregidos bloqueos de cobro/cancelación frente a deuda y caja; MoneyLockingTest descrito como estructural.
Cancelar/validar antes dejaba cashflow 10.000 con pago ANULADO y deuda pagada 0, en ambos órdenes.
Ahora los seis pasan: 103 aserciones aisladas en wings_testing_fin11_20260912.
Suite compartida: 181 pasan / 5 fallan, 1187 aserciones; fallas en fixtures nuevos SEG de otros trabajos.
Evidencia: ../06-pruebas/FIN-11-CONCURRENCIA-2026-09-12.md. Sin commit ni deploy aún.
Ambos tableros con pausa y sin checked: falta resolver las fallas externas y repetir suite completa.

## 2026-09-12 — Codex CAB — FIN-07 implementada y probada

Carlos resolvió la pausa: solo ADMIN perdona el saldo pendiente y conserva lo cobrado.
Condonación transaccional con lectura bloqueada; contrato complementario V1 y ambos tableros actualizados.
Tres casos con dos conexiones MariaDB reales: parcial, completo y condonación primero.
Prueba negativa: quitar el bloqueo hace fallar el caso parcial; código restaurado.
Suite completa: 169 pruebas / 1065 aserciones en wings_testing_fin07_20260912.
Evidencia y alcance: ../06-pruebas/FIN-07-CONCURRENCIA-2026-09-12.md.
Sin cambios propios de diseño, sin deploy; ajustarDeuda/API apagada queda fuera del alcance.
Pendiente: verificación cruzada y despliegue.

## 2026-09-12 — Codex CAB — FIN-07 pausada por regla de parcial (resuelta arriba)

Leídos contratos y cuerpos: condonarDeuda acepta PENDIENTE sin mirar monto_pagado.
Un parcial conserva PENDIENTE; no se encontró regla contractual que decida si
se perdona solo el saldo o se rechaza condonar cuando hay pagos previos.
Carlos pidió frenar expresamente en ese caso. Pendiente su definición.
Sin cambios funcionales, pruebas con escrituras ni commit de cierre.
ajustarDeuda tiene consumidor en API apagada; no es otra pantalla web actual.
FIN-07 permanece sin completar en ambos tableros; no se marcó checked.

## 2026-09-12 — Codex CAB — continuidad compacta para los tres agentes

Carlos autorizó un protocolo común, resumen, logs cortos y archivo íntegro.
Se archivaron sin pérdida las tres bitácoras y el plan anterior; se conservan sus huellas.
Guías y enlaces actualizados; sin cambios de aplicación, base ni despliegue.
Verificación documental: integridad byte por byte, enlaces y límites de tamaño.
Pendiente: cada agente mantiene este formato y lee historia solo por tarea.
