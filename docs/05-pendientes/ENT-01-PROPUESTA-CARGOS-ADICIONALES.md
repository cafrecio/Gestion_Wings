# ENT-01 — Diseño técnico aprobado: cargos adicionales

**Aprobado por Carlos el 22/09/2026**, con correcciones a la propuesta inicial.
Esta versión incorpora las decisiones: DNI por persona, inscripción primero,
exclusión de comisiones y edición de ingreso transaccional. Implementada y verificada localmente; no desplegada. [Regla completa](ENT-01-INSCRIPCION-Y-PRIMERA-CARGA.md).

## Modelo e integridad

`deuda_cuotas` conserva exclusivamente cuotas, con UNIQUE alumno/período.
`cargos_alumno` admite INSCRIPCION y PUNITORIO. Guarda origen único, alumno de origen,
DNI para inscripción, cuota de origen para punitorio, subrubro, importe decimal
congelado, cálculo congelado, condonación y estado VIGENTE/ANULADO.

- Inscripción: `inscripcion:dni:{dni_normalizado}`, una por persona en todo el club.
  Se normalizan espacios, puntos y guiones para identificarla. No depende del deporte.
- Punitorio: `punitorio:deuda:{id}`, una vez por cuota. Motor reservado para FIN-14.
- `pago_cargo_alumno`: pago/cargo e importe aplicado; UNIQUE pago/cargo. El saldo se
  obtiene del original menos aplicaciones vigentes y condonación. No duplicar acumuladores.
- `cargo_alumno_eventos`: acción, usuario, fecha, motivo y detalle. Anular no borra.
- `inscripcion_personas`: fila estable por DNI para serializar altas, cobros y
  correcciones incluso entre deportes. Lecturas bloqueantes de cargo e imputaciones
  después de esperar: no decidir con un saldo anterior al bloqueo.
- Alumno y plan inicial se crean junto con el cargo. Token de alta persistente y huella
  de datos protegen reintentos. La unicidad sigue garantizada por base.

## Fecha y configuración

Configurar `inscripcion_importe` (requerido, inicial 5000.00) y
`inscripcion_fecha_corte` (2026-09-23, ineditable en uso normal). Crear esas claves
por migración, no depender del seeder sobre instalaciones existentes. No backfill.
Validar importe por su clave; no usar la validación de días 1..31 para dinero.

Servidor compara el ingreso real al guardar. Guarda importe/corte/fecha de ingreso
en el cargo. Si el importe anunciado cambió antes de guardar, rechaza para revisar.
Corregir ingreso requiere motivo: sin pagos permite crear, anular o reactivar según
corte, auditando; con pagos vigentes rechaza. Mantener monto original al reactivar.
Corrección de DNI con cargo y fecha desconocida: procedimiento fuera del alcance,
no mover cargos ni inventar fechas. Condonación manual de inscripción no implementada.

## Pago y estados

Un `Pago` por total entregado, con `monto_cuota` explícito para nuevos cobros y
NULL en históricos (equivale a su `monto_final`). Imputaciones mensuales y de cargos
separadas. La suma debe coincidir con total y movimientos. Inscripción primero;
lo que resta se aplica a cuotas seleccionadas en orden. Saldo insuficiente visible.

Descuento de primer mes se aplica exclusivamente a cuota. Pagos de cargos solos no
cuentan como pagos de cuota para estado de cobranza ni para descuento. Comisiones,
cálculo y previsualización usan solo `monto_cuota`; no reinterpretar históricos.
Conservar política vigente de deudas anteriores y avisos. No implementar motor de mora.

## Caja, rubros y recibo

| Concepto | Rubro / subrubro |
|---|---|
| Cuota | Cuotas / Cuota Mensual |
| Inscripción | Inscripciones / Inscripción al club |
| Punitorio (FIN-14) | Punitorios / Punitorio Cuota |

Inscripción: INGRESO, reservado, ADMIN y OPERATIVO (`permitido_para=OPERATIVO`),
`afecta_caja=true`. No reutilizar las inscripciones de Torneos. Catálogo por migración
y coherente con seeder de bases nuevas.

Deuda creada no mueve caja. Cobro de cuota 30.000 + inscripción 5.000: un pago de
35.000, dos movimientos por 30.000 y 5.000, un recibo con ambos renglones. No crear
otro movimiento por el total. Al validar caja, conservar subrubros y fecha real;
no contar dos veces movimiento operativo y reflejo confirmado.

Cancelar desde cualquiera de sus movimientos revierte todo el cobro, ambas clases
 de imputación y todos sus movimientos, con las restricciones de caja vigentes.
El recibo anulado conserva sus conceptos. Los importes históricos no se recalculan.

## Diseño autorizado

Lecturas: habilidad Wings, DESIGN-RULES y vista canónica alumnos/index. Mantener
componentes y tokens; ningún CSS nuevo. JavaScript propio de pantalla por Vite.
Aviso previo al guardar por DNI/fecha; importe en Configuración; saldo por persona
en ficha y cobro; recibo con ambos conceptos.

`Diseno-autorizado: Carlos aprueba ENT-01: aviso previo de inscripcion, configuracion y desglose en cobranza, estado de cuenta y recibo, conservando el diseno Wings.`

## Punitorios: enmienda aprobada

El §5 del [contrato](../02-contratos/Wings-Contrato-Punitorios-Mora-V1.md) usa ahora
cargos y sus imputaciones. No columnas recargo_* en cuotas. Se conserva el resto:
porcentaje/importe congelados, ADMIN condona con motivo, mes en recibo y rubro propio.
FIN-14 implementará el motor; no publicar configuración sin motor.

## Verificación y entrega

Primero 11 pruebas en rojo contra el código previo; implementación y regresiones
posteriores incluyen PDF real, comisión y dos conexiones MariaDB. La suite completa,
recorrido visual, conteos finales y limitaciones se registran en la evidencia de cierre.
Solo bases descartables; sin deploy. Claude actualiza el servidor.

[Evidencia de implementación y verificación](../06-pruebas/ENT-01-INSCRIPCION-2026-09-22.md).
