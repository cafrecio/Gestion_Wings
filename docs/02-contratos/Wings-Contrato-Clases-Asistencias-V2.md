# Clases y asistencias V2 — creación: A15/A16

**Decisiones y diseño aprobados por Carlos el 06/10/2026.**
Enmienda limitada a crear clases; complementa [V1](Wings-Contrato-Clases-Asistencias-V1.md).
Implementación local, pendiente de verificación independiente y despliegue.

## A15: aviso y confirmación

- Si el inicio o el fin tienen minutos distintos de cero, Guardar informa los
  bloques del reloj ocupados y conserva el formulario **sin crear clases**.
- Ejemplo: 17:30–18:30 dura una hora y ocupa 17–18 y 18–19: dos bloques.
  17:30–18:00 ocupa solo 17–18; el extremo final es exclusivo.
- Confirmar permite guardar esos horarios. El servidor vincula la confirmación
  al usuario, grupo, profesores, fechas y todos los horarios de la carga.
  Una carga modificada exige renovar el aviso; no basta enviar «sí».
- En una serie el aviso identifica cada día que requiere atención. La
  confirmación abarca la carga completa; no se guarda primero una parte.
- Los horarios en punto se guardan directamente. No se crean reservas de cancha,
  no se calcula precio ni se implementa POS-07.

## A16: horarios por día

- Una carga recurrente elige un grupo, profesores y período una sola vez.
  Cada día seleccionado tiene su inicio y su fin obligatorios.
- Un solo identificador de serie reúne todas las clases de esa carga,
  aun cuando los horarios sean distintos. Cancelar la serie conserva el
  alcance existente: todas sus clases futuras y de hoy.
- El horario final debe ser posterior al inicial en cada día. Un día no
  seleccionado no agrega clases. Domingo usa el valor cero.
- La creación sigue siendo transaccional: un conflicto de profesor en
  cualquier fecha revierte todas las clases y asignaciones de la tanda.
- Se conserva la recepción del formato anterior (un horario común para
  los días elegidos), incluyendo el aviso A15 cuando corresponde.
- Ejemplo del 24/09 al 31/10: los seis grupos del
  [cronograma](../06-pruebas/PRU-02/CRONOGRAMA-SEMANAL.md) generan 76 clases
  con seis cargas; Intermedias combina lunes 17–18 y viernes 16–17.

Los roles, bloqueo de creación en fechas pasadas, profesores activos del
deporte, edición, asistencias, liquidaciones y limitaciones de edición de
series conservan las reglas de V1. Esta enmienda no cambia la edición de
una clase existente ni incorpora edición masiva.

[Propuesta visual aprobada](../06-pruebas/PRU-02/PROPUESTA-A15-A16.md).
