# P0 — entrega del 04/10/2026 · Codex CAB

**Implementado localmente; pendiente de verificación por Gemini. Sin despliegue.**

- A43 reescrito con la decisión más nueva: cuota del mes real de ingreso al porcentaje
  configurado para ese día, sin corte ni pregunta. El plan inicial empieza en esa fecha
  para que su cuota histórica pueda cobrarse. El cobro respeta el importe congelado.
- Inscripción manual única por DNI, al importe vigente. Sin comparar fechas ni crear
  cargos retroactivos al editar alumnos existentes. Corrección sin pagos conserva cargo,
  importe y estado con auditoría; con pagos vigentes conserva el rechazo anterior.
- Retirados lectura, control y campo de `inscripcion_fecha_corte`. La migración original
  conserva las tablas de cargos/pagos y ya no crea esa clave. La migración nueva elimina
  solo el parámetro legado; su rollback no vuelve a introducir una regla retirada.
- Cargos anulados, pagos y deudas existentes no se recalculan. Se mantienen ambos
  importadores antiguos hasta que P1 funcione. No se implementó el importador nuevo.

## Evidencia conductual

Antes de implementar, las pruebas de cuota e inscripción dejaron **9 fallos / 20 correctas**:
mes de carga en vez de ingreso, inscripción condicionada al corte y parámetro persistente.
Después: **32 pruebas / 182 aserciones** en ambos archivos de esta entrega.
Suite completa MariaDB `wings_testing`: **338 pruebas / 1932 aserciones**, todas verdes.
Incluye meses antiguos y futuros, porcentaje configurable, cobro sin segundo descuento,
alta atómica/reintento, retiro del parámetro legado sin tocar cargos, corrección auditada
y ausencia de cargo retroactivo al editar un registro anterior.

Sintaxis PHP y compilación Blade verificadas. El único cambio visual de Wings es retirar
el campo del corte de Configuración, autorizado expresamente en P0. Sin CSS nuevo.

## Límites y siguiente paso

No se modificó la base local de uso ni los datos de servidor. La migración se ejecutó
solamente en pruebas descartables. No se afirma que esté desplegado.
Gemini debe revisar pantalla y código antes de cerrar A43/P0.
P1 requiere maqueta estática en navegador y aprobación de Carlos antes de programar.
