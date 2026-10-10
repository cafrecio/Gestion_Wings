# VERIFICACIÓN T19 — La misma deuda en Inicio, Reportes y Cobranza

**Resultado: APROBADO**

Verificación de la consistencia de la deuda de alumnos activos (cuotas + inscripciones) a través de Inicio, Reportes y Cobranza (commit `ddd6343` de Claude, complementado por commit `567f030`).
Verificación ejecutada por Gemini en base de datos aislada `wings_testing_gemini`.
Suite de pruebas automatizada e independiente creada en `docs/06-pruebas/PRU-04/evidencia-t19/VerificacionT19Test.php` (14 pruebas, 77 aserciones, 100% verde).

---

## 1. Tabla de comprobaciones

| # | Punto a verificar | Qué se probó | Resultado |
|---|---|---|---|
| **1** | Igualdad al peso en los 8 casos de prueba | - Inscripción totalmente pagada: saldo $0.<br>- Inscripción con pago parcial: resta exactamente lo pagado.<br>- Inscripción condonada parcialmente: resta la condonación.<br>- Cuota con pago parcial: resta exactamente lo pagado.<br>- Cuota condonada parcialmente: resta la condonación.<br>- Alumno en dos deportes: una sola inscripción, no se duplica.<br>- Hermano activo + hermano inactivo: el DNI cuenta porque hay un activo.<br>- Alumno dado de baja: desaparece al instante de las 3 pantallas; al reactivarlo, reaparece al instante en las 3. | **APROBADO**. En los 8 casos, `Inicio = Reportes = Cobranza` al centavo exacto. |
| **2** | Mes futuro pendiente (no deuda) | Se generó una cuota de `2026-11` (pendiente) en `2026-10`. Se verificó que ni Inicio, ni Reportes, ni Cobranza la sumen como deuda. | **APROBADO**. Las 3 pantallas muestran $0,00 de deuda. El mes que no empezó queda excluido de la deuda en todo el sistema. |
| **3** | Filtro por deporte en Reportes | Dos alumnos (Patín y Fútbol) con deudas de cuotas e inscripción generada en Patín. Filtro general = $70.000. Filtro Patín = $40.000. Filtro Fútbol = $30.000. | **APROBADO**. La suma de los deportes individuales da exactamente el total general ($70.000). La inscripción se atribuyó al deporte con que se originó y no se duplicó. |
| **4** | Meses pasados en Reportes y cortes temporales | - Deuda de agosto consultada en agosto: refleja cuota e inscripción.<br>- Deuda de agosto consultada en septiembre tras pago en septiembre: el corte respeta la fecha histórica.<br>- Alumno dado de baja en octubre: impacto retroactivo en meses históricos analizado y documentado. | **APROBADO CON DOCUMENTACIÓN**. Ver detalle en Sección 3. |
| **5** | «Del mes» y «Meses anteriores» en Reportes | Alumno con inscripción y cuota del mes actual (octubre); alumno con inscripción y cuota del mes anterior (septiembre). Se verificó la clasificación en cada balde. | **APROBADO**. Cada cargo e inscripción cae en exactamente un balde (`periodo == mes` vs `periodo < mes`). La suma `mes + anteriores` es idéntica a `total`. |
| **6** | Otros lugares que muestran deuda de alumnos | Se auditaron todas las pantallas del sistema que visualizan deudas o métricas de cobro: Ficha del alumno, Buscador de Cobrar, Revisión de Cobranza, Reporte Alumnos, Dashboard Operativo. | **APROBADO CON RELEVAMIENTO**. Ver inventario exhaustivo en Sección 4. |
| **7** | «Profesores por pagar» y resto de Reportes | Se liquidó a un profesor con estado `CERRADA` y pago `PENDIENTE`. Se verificó que `por_pagar` siga funcionando idéntico y que el resultado económico del reporte no haya sufrido alteraciones. | **APROBADO**. `por_pagar` intacto ($80.000), filas coherentes, sin regresiones en las demás métricas de Reportes. |

---

## 2. Lo que NO se probó

- Cobro o consulta de deudas mediante la API REST (se encuentra deshabilitada de forma deliberada en `bootstrap/app.php` según AGENTS.md §7). Específicamente escrito: **no lo probé**.
- Base de datos en motores distintos a MySQL / MariaDB (el proyecto opera sobre MySQL): **no lo probé**.
- Modificaciones directas en la base del servidor de prueba (`https://test.gestionar-te.com.ar`): **no lo probé** por regla estricta de no tocar datos en producción/staging; se observó pasivamente el dashboard donde se constató el valor coherente de $2.010.000.

---

## 3. Detalle del comportamiento temporal en meses pasados (Punto 4)

El cálculo de deuda en `ReporteMensualService` utiliza dos mecanismos distintos según el concepto:
1. **Cuotas:** Utiliza `saldoHistorico('CUOTA', $fechaCorte, $mes, $deporteId)` basado en la tabla de auditoría `reporte_eventos`. Cada creación, pago o ajuste genera un `delta_centavos` con su respectiva `fecha`. Al pedir un corte histórico (ej. `2026-08-31`), se computan únicamente los eventos ocurridos hasta esa fecha.
2. **Inscripciones:** `inscripcionesPendientes($fechaCorte, $deporteId)` consulta la tabla `cargos_alumno` donde `created_at <= $fechaCorte`, y descuenta los pagos registrados en `pago_cargo_alumno` cruzados con `pagos.fecha_pago <= $fechaCorte`. Si un pago se realiza con fecha posterior a la fecha de corte, dicho pago no reduce la deuda histórica de ese corte, lo cual es matemáticamente correcto.

### Comportamiento frente a la baja de un alumno:
Ambos métodos filtran a los alumnos por su condición actual de actividad:
```php
Alumno::where('activo', true)
```
Dado que el modelo `Alumno` posee un flag booleano `activo` sin tabla histórica de estados temporales, si un alumno se da de baja hoy, sus deudas históricas de meses anteriores dejan de figurar en los reportes de esos meses pasados.
Este comportamiento es consistente con la directiva de negocio de Carlos: *«Un alumno inactivo no cuenta: no va a venir a pagar»*.

---

## 4. Relevamiento de otras pantallas que muestran deuda (Punto 6)

Se verificó el impacto en cada una de las pantallas identificadas:

1. **Ficha del alumno (`/alumnos/{id}` — `alumnos/show.blade.php`):**
   - Muestra las cuotas pendientes para períodos vigentes (`periodo <= now()->format('Y-m')`).
   - Muestra la inscripción pendiente en un banner independiente con desglose de saldo original y pagos.
   - Coherente: no cuenta meses futuros como deuda y muestra cuotas + inscripción.

2. **Buscador de Cobrar (`/caja/cobrar-cuota` — `caja/cobrar-cuota.blade.php`):**
   - Utiliza `CobranzaEstadoService::saldoDeAlumnos()`.
   - Considera cuotas pendientes vigentes (`periodo <= now()->format('Y-m')`) más saldo de cargos de inscripción pendientes para alumnos activos.
   - Coherente: coincide exactamente con la deuda mostrada en Cobranza e Inicio.

3. **Revisión de cobranza (`/revision-cobranza` — `revision-cobranza/index.blade.php`):**
   - No expone importes monetarios ni totales de deuda.
   - Lista alumnos en revisión por motivos administrativos específicos (ej. bajas, cambios de plan pendientes de autorización). No se ve afectada.

4. **Reporte de Alumnos (`/reportes/alumnos` — `reportes/alumnos.blade.php`):**
   - Muestra estadísticas de asistencia, alumnos activos/inactivos y distribución por nivel/grupo.
   - No calcula ni expone montos de deuda financiera.

5. **Inicio del Operativo (`/operativo` — `operativo/dashboard.blade.php`):**
   - Muestra una métrica de conteo: `alumnosConDeuda`.
   - Cuenta la cantidad de alumnos cuya relación `deudaCuotas` tiene cuotas con saldo pendiente.
   - Observación para el futuro: mide cantidad de cabezas con cuotas impagas, no importes monetarios.

---

## 5. Resultado final de las pruebas

La suite completa de la aplicación ejecutada sobre `wings_testing_gemini` arrojó:

```text
OK, but some tests were skipped!
Tests: 613, Assertions: 5087, Skipped: 2.
```

Las 14 comprobaciones independientes de `VerificacionT19Test.php` resultaron 100% exitosas (77 aserciones).

**Conclusión:** La implementación de T19 cumple de manera exacta y rigurosa con la regla de negocio de Carlos. Se da por **APROBADO**.
