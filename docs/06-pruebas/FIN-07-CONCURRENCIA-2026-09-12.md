# FIN-07 — Cobrar y condonar

Codex CAB, 12/09/2026. Regla confirmada por Carlos: solo ADMIN condona el saldo
pendiente; el dinero ya cobrado y sus imputaciones se conservan.

## Resultado real

| Caso | Resultado comprobado | Estado |
|---|---|---|
| Cobro parcial primero | Cuota 60.000, cobro 10.000; condonación espera en SELECT FOR UPDATE. Tras confirmar y reintentar: CONDONADA, original 60.000, pagado 10.000, observación de saldo perdonado 50.000; imputaciones intactas y movimiento por 10.000 | PASA |
| Condonación primero | Cobro espera en SELECT FOR UPDATE. Tras confirmar y reintentar se rechaza con “fue condonada, no admite pagos”; cero pagos, imputaciones, movimientos y cajas del caso | PASA |
| Cobro completo primero | Condonación espera; tras confirmar y reintentar por ruta ADMIN se rechaza. Deuda PAGADA, pagado e imputado 60.000 | PASA |
| Segundo no escribe mientras espera | Timeout real de InnoDB, sin INSERT/UPDATE/DELETE exitosos del segundo; nivel transaccional vuelve a cero | PASA |
| Sensibilidad al defecto | Se retiró temporalmente el bloqueo de lectura: el caso parcial falló porque el SQL que esperaba era UPDATE. Se restauró el archivo original | PASA |

Pruebas nuevas: `tests/Feature/CobrarCondonarConcurrenteTest.php`. Se ejecutan los
servicios reales en dos conexiones. Una transacción externa mantiene sin confirmar
el primer servicio; la segunda conexión usa `innodb_lock_wait_timeout = 1`.
La espera se prueba hasta timeout; la lectura posterior se prueba en un nuevo
intento después del commit. No se simuló una solicitud HTTP que se reanude sola.
Los casos de condonación posterior usan la ruta ADMIN. La suite existente
`CondonarDeudaWebTest` conserva la cobertura de permisos y motivo.

Base descartable y exclusiva: `wings_testing_fin07_20260912`. No se usaron los
alumnos ni las deudas de `wings_test`.

- Prueba nueva aislada: **3 pruebas / 40 aserciones** (incluye preparación de esquema).
- Suite completa: **169 pruebas / 1065 aserciones**, 36,32 segundos, salida 0.
- Mutación sin bloqueo: **1 falla esperada**, sobre el SQL real bloqueado; restaurada.
- Sintaxis de ambos PHP sin errores; vistas compiladas y caché retirada correctamente.
- `git diff --stat -- resources/views resources/css`: vacío al cierre.

El cambio funcional se limita a `condonarDeuda`: transacción, bloqueo compartido
con el cobro y registro del saldo perdonado. `ajustarDeuda` permanece sin cambios:
su consumidor está en la API deshabilitada. Sin modificaciones propias de vistas
ni CSS y sin despliegue. Pendiente verificación cruzada.
