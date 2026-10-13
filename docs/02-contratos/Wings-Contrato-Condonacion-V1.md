# Wings — Condonación y reactivación de cuotas e inscripción V1

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

## T33 — decisiones de Carlos, 13/10/2026 (DU-25 y DU-58)

**Inscripción:** «Sí, con motivo, igual que una cuota» (DU-25).
Solo ADMIN condona desde la ficha que tiene el cargo, con motivo de 10–500 caracteres.
Perdona únicamente el saldo pendiente: `monto_condonado` guarda ese saldo; original,
pagos e imputaciones se conservan. Cargo VIGENTE, sin perdón previo y con saldo positivo.
Ya pagada, ANULADA o ya perdonada se rechaza sin escribir.
El evento CONDONAR conserva usuario, fecha, motivo e importe en `cargo_alumno_eventos`.
La otra ficha de la misma persona conserva el aviso de inscripción única y dónde consultarla.

**Reactivar:** «Reactivar la perdonada: en la ficha, la cuota perdonada se puede volver
a cobrar, con motivo, y queda el historial (perdonada tal día, reactivada tal día).
Vale igual para la inscripción» (DU-58; reemplaza crear de nuevo a mano).
Solo ADMIN, con motivo de 10–500 caracteres. Cuota CONDONADA vuelve a PENDIENTE,
sin modificar original, parciales ni imputaciones; observaciones conserva el perdón
y agrega fecha, autor, motivo y saldo de la reactivación. Inscripción VIGENTE con
importe perdonado vuelve a `monto_condonado=0` y suma evento REACTIVAR con ambos valores.
No se duplica cuota ni inscripción. Las tres acciones también admiten alumnos dados
de baja; no reactivan su matrícula. Las cuotas futuras no suman hasta que empieza su mes.

Perdonar/reactivar no emite recibos ni mueve caja o cashflow. Transacción y bloqueo
en el orden del cobro: persona, alumno, cuota/cargo; estado y saldo leídos bajo bloqueo.
Inscripción $5,000.00 con parcial $2,000.00: perdón $3,000.00. Si luego se anula
ese pago, debe $2,000.00; reactivar el perdón deja $5,000.00 pendientes.

Reportes: las cuotas conservan variaciones históricas fechadas. La inscripción usa
el importe condonado actual, incluso al consultar un mes anterior; su perdón baja
ese reporte y reactivarla lo vuelve a subir. T33 informa ese comportamiento y no lo cambia.

[Implementación, bordes, pruebas y capturas](../06-pruebas/PRU-04/IMPLEMENTACION-T33.md).
