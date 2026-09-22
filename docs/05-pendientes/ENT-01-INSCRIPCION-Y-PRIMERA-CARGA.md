# ENT-01 — Inscripción y primera carga manual

**Aprobado por Carlos el 22/09/2026.** Implementada y verificada localmente para
la versión de prueba PRU-02; sin deploy. [Diseño técnico aprobado](ENT-01-PROPUESTA-CARGOS-ADICIONALES.md).

## MUY IMPORTANTE: fecha real de ingreso

Los usuarios cargarán alumnos manualmente para familiarizarse con Wings. Comparar
`alumnos.fecha_alta`, ingreso real, con **23/09/2026** inclusive. Antes no corresponde;
desde el corte sí. Nunca usar `created_at`, un modo temporal ni preguntar nuevo/antiguo.
No generar inscripción retroactiva por migrar alumnos existentes.

Si no se conoce la fecha real, no inventarla: el procedimiento sigue pendiente.
El formulario propone hoy; el manual debe explicar cambiarlo por la fecha real
para alumnos antiguos, aunque se carguen hoy o dentro de varios años.

## Decisiones de inscripción

- Una inscripción **por persona/DNI**, aunque esté en dos deportes. Clave única
  `inscripcion:dni:{dni_normalizado}`. El cargo se consulta/cobra desde cualquiera.
- Importe requerido en Configuración, inicial $5.000; fecha de corte fija, sin edición
  normal. El cargo guarda el importe vigente y no cambia por aumentos posteriores.
- Alta, plan inicial y cargo en una transacción; reintento no duplica.
- Se cobra junto con la primera cuota. Si no alcanza: **primero inscripción y luego
  cuota**. El saldo restante queda visible. No ocultar la inscripción dentro de la cuota.
- Un pago, un recibo; desglose por concepto. Caja recibe solo lo efectivamente cobrado,
  sin duplicar al separar movimientos o al validar la caja.
- Inscripción y punitorios no integran comisiones docentes. El estado de cobranza de
  cuotas no se altera por deuda o pago de inscripción solos. Pagos históricos preservados.
- Anular el cobro revierte todas sus imputaciones y movimientos en una transacción;
  conserva detalle para el recibo anulado. No elimina la deuda de inscripción.

## Corrección de fecha de ingreso

Se permite con motivo. Sin pagos de inscripción, la corrección se compara con el corte:
si queda antes, se anula el cargo sin borrarlo, con usuario, fecha y motivo; si queda
desde el corte y no existía cargo, se crea. Un cargo anulado se reactiva, conservando
su identidad y monto original, al volver a corregir desde el corte. No generar dos.
Si tiene algún pago vigente, incluso parcial y desde otro deporte, rechazar la edición
con mensaje claro. Todo es transaccional; no modificar cobros emitidos.

La corrección de DNI cuando existe inscripción requiere un procedimiento específico;
el sistema la rechaza para no separar una persona de su deuda por una edición casual.
No hay condonación manual de inscripción en este alcance.

## Punitorios y pantallas

Aprobado también el reemplazo de §5 de Punitorios: `cargos_alumno`, tipo `PUNITORIO`,
clave `punitorio:deuda:{id}`. **FIN-14 queda pendiente:** no motor ni configuración de mora.

Autorización escrita de Carlos:

`Diseno-autorizado: Carlos aprueba ENT-01: aviso previo de inscripcion, configuracion y desglose en cobranza, estado de cuenta y recibo, conservando el diseno Wings.`

## ENT-10 — Manuales de primera carga (PENDIENTE)

Preparar guía breve para usuarios que cargan y guía de preparación del sistema.
La preparación fija el corte; el usuario no debe mantener modos temporales.
Explicar en lugar destacado:

- Fecha de ingreso al club versus día en que se carga al sistema.
- Alumno antiguo cargado hoy: ingreso real anterior al corte, sin inscripción nueva.
- Inscripción nueva desde el corte: deuda inicial al valor configurado.
- Ejemplos antes/en/después del corte y qué revisar antes de guardar.
- Qué hacer si falta la fecha real (no inventarla; procedimiento pendiente de definir).
- Diferencia entre inscripción nueva y deuda inicial del padrón (ENT-09).

Validar los manuales contra las pantallas realmente implementadas y probar su
comprensión con un usuario no técnico. No publicar capturas o pasos inventados.
Los manuales no se consideran terminados por existir esta orden de trabajo.

[Evidencia de implementación y verificación](../06-pruebas/ENT-01-INSCRIPCION-2026-09-22.md).
