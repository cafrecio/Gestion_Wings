# T61 — Transferir entre cuentas propias

Codex CyE, 20/10/2026. **Corrección implementada; visual aprobado por Carlos; lógica a verificar por Claude.**
La entrega anterior resolvía dos cargas independientes. Carlos necesita un formulario con cuenta de salida, cuenta de destino e importe; también pidió rubros y subrubros exclusivos del sistema, sin renombrar ni editar.

## Funcionamiento solicitado

Una transferencia registra dos movimientos vinculados por una cabecera: egreso en la cuenta de salida e ingreso en la de destino, con igual importe y fecha. Una transacción confirma ambos o revierte todo. Las cuentas deben ser distintas y activas; importe positivo con hasta dos decimales. El doble envío de la misma operación no duplica movimientos.

El sistema asigna los conceptos de transferencia. El formulario no pide rubro ni subrubro. El catálogo queda reservado y el formulario de movimiento común no permite registrar una pata sola. La corrección individual de esos movimientos queda bloqueada para conservar el par; no se implementa aquí otro circuito de anulación de transferencias.

La salida y entrada cuentan en el libro Cashflow; su efecto neto sobre el disponible global y los indicadores del club es cero. Cada cuenta cambia por el importe transferido. Los movimientos anteriores no se emparejan por conjetura ni se reescriben.

## Cambios actuales

- Migración `2026_10_20_160000_crear_transferencias_cashflow.php`: cabecera y protección del catálogo existente.
- `TransferenciaCashflowService`: validación, permisos, cuentas bloqueadas en orden estable, clave de operación única y escritura atómica.
- `TransferenciaCashflowWebController`, rutas ADMIN, formulario y acceso Transferir desde Cashflow.
- Catálogo compartido por seeder y migración: reserva automática de los dos rubros y subrubros dedicados.
- Pruebas T61 adaptadas al funcionamiento solicitado: 12 casos, incluyendo rollback de la segunda escritura, reenvío, cuentas inválidas, catálogo protegido e invariancia de reportes.

## Revisión de botones solicitada por Carlos

Medición real: Volver y Transferir ya tenían 96 × 32 px, pero estaban alineados al borde exterior. Ahora quedan dentro de la tarjeta, alineados con el borde derecho de los campos. En Rubros, Subrubro medía 83,09 × 32 y Editar/Eliminar 76 × 32; los tres se fijaron en 96 × 32 en las nueve botoneras de escritorio, sin cambiar el CSS compartido. Capturas actualizadas 1366/375; **Carlos aprobó el visual corregido:** «Apruebo el visual corregido» (20/10/2026).

## Verificación actual

Dirigidas: **22 aprobadas, 287 aserciones, 11,26 s, salida 0** en `wings_testing_codex`. Suite completa: **976 aprobadas, 6 omitidas, 8527 aserciones, 804,11 s, salida 0**, corte 982. [Salida](evidencia-t61-transferencia/suite-completa.txt). [Dirigidas finales con CSP](evidencia-t61-transferencia/dirigidas-finales.txt): **24 aprobadas, 289 aserciones, 10,15 s, salida 0**. Sintaxis PHP y compilación/limpieza de vistas correctas. Recorrido real: un envío guardó salida y entrada de $1.233.545 en base ficticia. Once capturas 1366/375 en [galería actual](RECORRIDO-T61.html). Pendiente: verificación independiente de lógica por Claude (visual aprobado por Carlos el 20/10: «Apruebo el visual corregido»). La galería anterior se conserva solo como antecedente.

[Antecedente íntegro y resultados de la entrega anterior](IMPLEMENTACION-T61-ANTECEDENTE-DOS-CARGAS.md). Sin despliegue ni escritura en bases remotas.

## Para la ayuda

### Llevar efectivo al banco

Una transferencia mueve plata del club entre cuentas. No es una venta, un gasto, un aporte ni un retiro.

1. En Cashflow, tocá **Transferir**. Seleccioná **Efectivo** como cuenta de salida y **Banco** como destino. Indicá **$300.000** y la fecha.
2. Tocá **Transferir**. Wings guarda juntas la salida y la entrada: Efectivo baja $300.000 y Banco sube $300.000. El resultado del club no cambia.

Los conceptos **Salida a otro medio** y **Entrada desde otro medio** los asigna el sistema. No se editan ni renombran desde Rubros. Texto para T13; no se construye una pantalla de ayuda.
