# Wings — Bitácora activa de CODEX

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-CODEX.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).

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

## 2026-09-11 — extracto documental de Codex CyE — FIN-03

Implementación c1bef8a y contrato Recibos V2, según el cierre original.
Anulaciones nuevas conservan detalle; no reconstruye imputaciones borradas antes.
166 pruebas / 1026 aserciones y PDF revisado el 11/09; no reejecutados hoy.
Migración probada solo en base descartable; pendiente revisión cruzada y deploy.
Evidencia completa: archivo histórico, encabezados FIN-03 y Pase a CAB.

## 2026-09-11 — extracto documental de Codex CyE — COB y SEG

COB-05, COB-09 y FIN-02 verificadas sobre e921e5d; 15 cobros en navegador.
Evidencia: ../06-pruebas/COB-05-CIERRE-2026-09-11.md.
SEG-01 verificada con retiro de Axios, audit y build al 11/09; sin deploy.
No asumir que datos locales o servidor siguen iguales al corte.
