# POS-06 — Clases particulares

**Estado: PENDIENTE de implementación.** Contrato documentado el 13/09/2026 por
pedido de Carlos; no autoriza ejecutar funcionalidad en este turno.

- Fuente: [Contrato de clases particulares V1](../02-contratos/Wings-Contrato-Clases-Particulares-V1.md).
- Agenda, deuda, cobro, crédito y recibos; asistencia y confirmaciones; pago del
  profesor y privacidad; rentabilidad, correo y Telegram.
- Incluye enmienda general: bloqueo de asistencia por cierre para todos;
  cancelación de cerrada no pagada solo ADMIN; pagada intacta y ajuste posterior.
- Antes de programar: contrastar código actual, definir detalles pendientes del
  §12 y diseñar cambios sin reinterpretar decisiones. Aplicar los criterios del §11.
- Mantener pruebas financieras en base descartable y autorización concreta de diseño.
- No declarar implementado por existir este documento. Sin despliegue en este turno.

## Alcance adelantado para hoy

Carlos separó [FIN-12: cancelar cerrada no pagada](FIN-12-CANCELAR-LIQUIDACION-CERRADA.md) como tarea pendiente prioritaria del 13/09. El resto de particulares continúa en POS-06.

## Siguiente conversación: diseño de Reportes

Carlos pidió continuar después con una entrevista: una pregunta abierta por vez,
respuesta corta, en lenguaje cotidiano. Se pausó FIN-04 para definir particulares.

Decisiones iniciales recuperadas de la entrevista, aún sin cerrar Reportes:

- ADMIN quiere saber si el negocio es rentable y su evolución mensual.
- Mostrar gastos realizados, cuotas cobradas, sueldos a pagar, cuotas a cobrar y
  alumnos activos, con evolución por deporte y comparación con el mes anterior.
- Indicador planteado: último mes cerrado, ingresos de cuotas menos egresos;
  otros ingresos pueden no representar resultado del negocio.
- Para OPERATIVO se plantearon cobranzas y su propia caja diaria en Reportes;
  ese recorte no cambia permisos generales de operación ni los contratos existentes.
- Falta completar y contrastar el diseño con el [contrato previo de Reportes](../02-contratos/Wings-Contrato-Reportes-V1.md).
  No tomar estas notas como contrato final ni ejecutar el tablero anterior sin esa revisión.
