# Wings — Condonación de cuotas V1

Decisión expresa de Carlos, 12/09/2026, FIN-07. Complementa el contrato de cuotas:
no redefine registro de pagos, FIFO ni ajustes de deuda.

- Solo ADMIN puede condonar desde la web; OPERATIVO y PROFESOR no.
- Se condona únicamente el saldo pendiente de la cuota seleccionada.
- Ejemplo: original 60.000, pagado 10.000; se perdonan 50.000.
- Original, monto pagado, pago completado, imputaciones y movimiento de caja se conservan.
  No es devolución, cancelación del pago ni nuevo ingreso/egreso.
- La deuda pasa de PENDIENTE a CONDONADA; deja de ser deuda exigible.
  La diferencia original menos pagado se conserva como importe histórico perdonado.
- Observaciones conserva autor, fecha, motivo de 10–500 caracteres y saldo condonado.
- Si el cobro completo ganó primero, la deuda PAGADA no admite condonación.
- Si ganó un parcial, se condona solo el saldo actualizado, conservando el parcial.
- Si ganó la condonación, el cobro posterior se rechaza sin pago ni imputación ni caja nueva.
- La decisión de estado y saldo se hace dentro de transacción y bajo bloqueo de la deuda,
  compartido con el cobro. Esperar un bloqueo no autoriza operar con la lectura anterior.
- Ante timeout se revierte la operación que esperaba; no se da por realizada.

Aceptación: dos conexiones MariaDB reales, un primer movimiento sin confirmar y un
segundo que espera sin escribir. Después de confirmar el primero, el segundo relee
estado y saldo; probar parcial, pago completo y condonación primero. No basta buscar
lockForUpdate en el código. No se habilita la API ni se modifica ajustarDeuda.
