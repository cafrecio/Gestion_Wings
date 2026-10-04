# ENT-01 — Inscripción y primera carga manual

**Aprobado por Carlos el 22/09/2026.** Implementada y verificada localmente para
la versión de prueba PRU-02; sin deploy. [Diseño técnico aprobado](ENT-01-PROPUESTA-CARGOS-ADICIONALES.md).

## MUY IMPORTANTE: regla vigente de primera carga, 26/09 y 04/10/2026

**No hay fecha de corte de inscripción.** El alta manual crea una inscripción por DNI,
independientemente de la fecha de ingreso. La cuota inicial corresponde al mes real de
`alumnos.fecha_alta`, con el porcentaje de ese día, no al día de carga (`created_at`).
No generar cargos retroactivos para registros ya existentes.

**Primera carga por Excel (P1, todavía no implementada):** no cobra inscripción salvo
que la columna de la plantilla lo indique expresamente por alumno. Los catálogos se
preparan antes y toda la carga debe revisarse sin escribir y entrar completa o no entrar.
[Decisiones y maqueta previa](PRIMERA-CARGA-EXCEL.md).

Si no se conoce la fecha real, no inventarla: el procedimiento sigue pendiente.
El manual debe explicar la diferencia entre fecha de ingreso y día de carga.

## Decisiones de inscripción

- Una inscripción **por persona/DNI**, aunque esté en dos deportes. Clave única
  `inscripcion:dni:{dni_normalizado}`. El cargo se consulta/cobra desde cualquiera.
- Importe requerido en Configuración, inicial $5.000, sin parámetro de corte.
  El cargo guarda el importe vigente y no cambia por aumentos posteriores.
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

Se permite con motivo. Sin pagos vigentes de inscripción, corregir la fecha conserva
el cargo, su importe y su estado; registra usuario, fecha y motivo. Sin cargo previo,
la edición no crea inscripción retroactiva. No anula ni reactiva cargos por fechas.
Si tiene algún pago vigente, incluso parcial y desde otro deporte, rechazar la edición
con mensaje claro. Todo es transaccional; no modificar cobros emitidos.
La retirada del corte no recalcula cuotas o cargos históricos.

La corrección de DNI cuando existe inscripción requiere un procedimiento específico;
el sistema la rechaza para no separar una persona de su deuda por una edición casual.
No hay condonación manual de inscripción en este alcance.

## Punitorios y pantallas

Aprobado también el reemplazo de §5 de Punitorios: `cargos_alumno`, tipo `PUNITORIO`,
clave `punitorio:deuda:{id}`. **FIN-14 queda pendiente:** no motor ni configuración de mora.

Autorización escrita de Carlos:

`Diseno-autorizado: Carlos aprueba ENT-01: aviso previo de inscripcion, configuracion y desglose en cobranza, estado de cuenta y recibo, conservando el diseno Wings.`

## ENT-10 — Manuales de primera carga (PENDIENTE)

Preparar guía breve para usuarios y guía de preparación del sistema, una vez aprobada
e implementada P1. No mantener modos temporales ni parámetros de corte.
Explicar en lugar destacado:

- Fecha real de ingreso versus día en que se carga al sistema.
- Primera carga: Excel con alumnos y deuda, revisión sin escritura y errores en Excel.
- Inscripción en Excel: no por defecto; solo cuando su columna diga que sí.
- Alta manual posterior: cuota del mes de ingreso al porcentaje del día más inscripción.
- Catálogos cargados antes: deportes, niveles, grupos y planes con precio.
- Qué hacer si falta la fecha real (no inventarla; procedimiento pendiente).
- Ensayo en test y vuelta atrás sin cobros.

Validar los manuales contra las pantallas realmente implementadas y probar su
comprensión con un usuario no técnico. No publicar capturas o pasos inventados.
Los manuales no se consideran terminados por existir esta orden de trabajo.

[Evidencia de implementación y verificación](../06-pruebas/ENT-01-INSCRIPCION-2026-09-22.md).
