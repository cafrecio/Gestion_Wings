# Wings — Bitácora activa de CLAUDE

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-CLAUDE.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).

## 2026-09-11 — extracto documental de Claude CAB — recibos y decisiones

El pase registra aprobación visual del diseño de Gemini por Vanina.
Pendiente decidir por clase o por duración antes de implementar liquidación/recibo.
El instructivo requiere datos que no se obtienen copiando la plantilla sin adaptación.
También pendiente significado de monto_base y despliegue.
Las pausas antiguas COB-09/FIN-02 tienen evidencia posterior en el resumen común.

## 2026-09-12 — Claude CyE — FIN-03 verificada y la red de FIN-05 retirada

**Puesta al dia en CyE:** `git pull` a `5e8070e`, `npm install` (audit en cero), migracion
`2026_09_11_180000_add_detalle_anulacion_to_pagos_table` aplicada a `wings_test`, guardia
de diseno instalada. Suite **166 pruebas / 1026 aserciones**, igual al corte documentado.

**Servidor verificado por SSH: sigue en `81f27ef`.** 39 commits sin desplegar, incluidas
las cuatro correcciones de cobros, FIN-03 y FIN-05. La base del servidor es la **real y
definitiva**: cualquier cambio de estructura va antes de que Vanina cargue.

### FIN-03 — revision cruzada hecha, pasa

Leido el cuerpo, no el tablero. El detalle se guarda **antes** de borrar las
imputaciones y en la **misma transaccion** que revierte el cobro; el `save()` del pago
viene despues de asignarlo. El recibo lo lee de ahi.

Tres pruebas propias en `ReciboMedioDePagoTest`, incluida
`test_anulacion_antigua_sin_detalle_no_inventa_periodos`, que cubre el borde correcto.

Limitacion real, no defecto: las anulaciones anteriores al 11/09 quedan sin detalle. En
`wings_test` hay **cero** pagos anulados y en el servidor **cero** pagos. No afecta a
nadie. **Falta desplegar la migracion.**

### FIN-05 — la red de seguridad en la base se retira

Yo mismo la habia recomendado y quedo ofrecida a Carlos en `CHECKLIST-CARLOS.md` como
"esperando tu si". **La verifique antes de que la aprobara y rompe la validacion de
cajas.**

`CashflowIntegracionCajaService::reflejarCajaEnCashflow()` linea 53 crea **un asiento por
cada movimiento** de la caja, todos con el mismo `referencia_tipo = CAJA_OPERATIVA` y el
mismo `referencia_id`. Una caja con diez cobros son diez filas con la misma referencia:
un indice unico sobre ese par haria fallar **toda caja con mas de un movimiento**.

La version correcta exige columna generada mas indice, sobre la tabla de plata de la base
definitiva. **Recomendacion corregida: no hacerlo.** El `lockForUpdate()` ya resuelve el
caso real, probado con dos conexiones reales — no un `grep`, como si lo era
`MoneyLockingTest`.

**Lo que este turno deja aprendido:** verifique las dos cosas y las conte en el chat sin
escribirlas. Carlos lo marco. Un hallazgo que corrige una recomendacion mia y vive solo en
el chat es peor que no tenerlo: el documento sigue invitando a aprobar lo que rompe.

---

## 2026-09-11 — extracto documental de Claude CAB — FIN-05 y SEG-01

Se registra corrección de pago concurrente de liquidación y prueba con dos conexiones.
El barrido deja recálculo frente a cierre para FIN-11; no darlo por resuelto.
Claude registra verificación de SEG-01 de Codex; no es un audit nuevo del 12/09.
Fuente íntegra: encabezados FIN-05 y Verificación de SEG-01 del archivo histórico.
