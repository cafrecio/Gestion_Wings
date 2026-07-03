# Definiciones pendientes de implementación

Este archivo documenta funcionalidades cuyo comportamiento fue **definido en conversación** pero aún no están implementadas en el código. Leer ANTES de tocar las áreas relacionadas.

---

## 1. Revisión de alumnos — parte del proceso de generación de deudas mensuales

**Estado:** No implementado. El modelo `AlumnoRevisionCobranza` existe y hay rutas API, pero no hay lógica en el comando ni UI web.

### Regla de detección (se evalúa el 1 de cada mes en `GenerarDeudasMensualesCommand`)

Un alumno activo **NO genera deuda** y pasa a estado `POSIBLE_INACTIVO` si:
- No tuvo asistencias en el mes anterior, Y
- No realizó ningún pago en el mes anterior.

Este criterio cubre todos los casos:
- Alumno nuevo que tuvo clase de prueba y no volvió ni pagó → el mes siguiente cumple la condición.
- Alumno existente que dejó de venir y pagar → ídem.

No hace falta ninguna lógica adicional de "N meses de ausencia" — el comando mensual ya detecta el abandono en el primer mes sin actividad.

### Reactivación automática

El estado `POSIBLE_INACTIVO` se revierte a `ACTIVO` automáticamente si:
- El alumno **asiste a una clase**, O
- El alumno **realiza un pago**.

No hay deadline para la revisión — se resuelve solo cuando el alumno vuelve o cuando admin/operativo lo gestiona manualmente.

### Flujo de revisión manual (admin u operativo)

1. Ver lista de alumnos en estado `POSIBLE_INACTIVO`.
2. Contactar al tutor y registrar resultado con nota libre (medio de contacto + razón).
3. Dos opciones:
   - **Continúa** (ej: "WhatsApp con Margarita — alumno de vacaciones") → genera deuda del mes manualmente con confirmación + nota queda registrada. Estado vuelve a `ACTIVO`.
   - **No continúa** (ej: "Teléfono con Pablo — cambio de actividad") → alumno pasa a `INACTIVO`. No se genera deuda.

### Manejo de deudas previas al confirmar inactivo

- Deuda del **mes inmediato anterior** sin pagar → se **cancela automáticamente** al confirmar inactivo.
- Deudas de **más de un mes atrás** → el admin decide individualmente (condonar, cobrar, dejar pendiente).

### Archivos relacionados

- `app/Models/AlumnoRevisionCobranza.php` — modelo existe
- `app/Console/Commands/GenerarDeudasMensualesCommand.php` — agregar lógica de detección
- `app/Services/CobranzaEstadoService.php`
- Triggers en registro de asistencia y pago para reactivar automáticamente
- No existe controlador web ni vista para gestión de posibles inactivos

---

## 2. Regla de primer pago — no aplicada en PagoCuotaService

**Estado:** Parcialmente implementado. La regla existe y es configurable, pero el servicio actual no la aplica.

**El problema:**  
Cuando un alumno ingresa a mitad de mes, la primera cuota debería prorratearse según los días que quedan. Ejemplo: si ingresa el día 20 de un mes de 30 días, paga ~33% de la cuota mensual.

**Dónde está configurado:**
- Modelo: `app/Models/ReglaPrimerPago.php` — campos `dia_desde`, `dia_hasta`, `porcentaje`
- Configurable en `/configuraciones` via `ReglaPrimerPagoWebController`
- `PagoService` (servicio viejo) sí aplica la regla

**El gap:**  
`PagoCuotaService` (el servicio que se usa para cobrar cuotas hoy) siempre graba:
```php
'regla_primer_pago_id' => null,
'porcentaje_aplicado' => 100,
```
Nunca consulta `ReglaPrimerPago`.

**Lógica a implementar en PagoCuotaService:**
- Detectar si es el primer pago del alumno (no tiene pagos previos).
- Si es primer pago, consultar `ReglaPrimerPago::obtenerReglaPorDia($diaAlta)` donde `$diaAlta` es el día del mes de la fecha de cobro.
- Si hay regla activa, aplicar `porcentaje` al monto base.
- Grabar `regla_primer_pago_id` y `porcentaje_aplicado` en el `Pago`.
- Mostrar el descuento calculado en el formulario de cobro ANTES de confirmar.

---

## 3. Cambio de plan durante el flujo de cobro

**Estado:** Implementado (2026-07-03).

**El problema:**  
Un alumno puede querer cambiar de plan al momento de cobrar (ej. de 2 veces/semana a 1 vez/semana, o de 1 a 3). Esto cambia el monto de la cuota. Hoy el operativo tendría que salir del flujo de cobro, cambiar el plan en la ficha del alumno, y volver a cobrar.

**Flujo definido:**
1. En el formulario de cobro (`caja/cobrar.blade.php`), mostrar el plan actual del alumno.
2. Agregar selector de plan (frecuencia/grupo) con opción de mantener el actual.
3. Si cambia el plan:
   - Recalcular el monto de la deuda en tiempo real (JavaScript).
   - Al confirmar, actualizar `AlumnoPlan` con el nuevo plan/grupo antes de procesar el pago.
   - El `PagoCuotaService` debe usar el nuevo plan para calcular el monto.

**Archivos relacionados:**
- `resources/views/caja/cobrar.blade.php`
- `app/Http/Controllers/CajaWebController.php` — método `cobrar()` y `pagar()`
- `app/Services/PagoCuotaService.php`
- `app/Models/AlumnoPlan.php`
- `app/Models/Grupo.php` — tiene el campo `valor_cuota` por frecuencia

**Nota:** El cambio de plan debe quedar registrado en historial de `AlumnoPlan`, no solo sobreescribir.

---

## 4. Sidebar — reorganización por roles

**Estado:** Definido, no implementado.

**El problema:**  
El sidebar actual es una lista plana sin agrupamiento. Un usuario nuevo no puede orientarse. Items de distintos dominios (caja operativa, configuración del sistema, finanzas) aparecen al mismo nivel sin separación visual ni semántica.

**Estructura definida para ADMIN:**
```
Dashboard
Cobranza          ← nuevo link a /cobranza

── Caja / Finanzas ──
Caja              ← validar cajas operativas
Cashflow          ← historial financiero (/cashflow)
Liquidaciones     ← pago a profesores

── Académico ──
Alumnos
Clases
Grupos
Profesores

── Configuración ──
Deportes
Rubros
Niveles
Tipos de Caja
Configuración
Usuarios
```

**Estructura definida para OPERATIVO:**
```
Inicio            ← dashboard operativo (/operativo)

── Caja ──
Caja              ← historial de cajas propias
[Cobrar también aparece en el dashboard como acción primaria]

── Alumnos ──
Alumnos

── Clases ──
Clases
Grupos            ← solo lectura: ver precios por frecuencia
```

**Decisiones tomadas en conversación:**

- "Cobrar" aparece en DOS lugares: ítem del sidebar bajo Caja, Y como acción primaria en el dashboard operativo.
- Grupos en sidebar del operativo es **solo lectura** — el operativo lo necesita para consultar precios por frecuencia pero no puede editar.
- "Movimientos" y "Mov. directo" se eliminan del sidebar — accesibles desde sus páginas correspondientes.
- El link de Cashflow en el sidebar admin debe apuntar a `web.cashflow.index` (historial), no a `web.cashflow.movimiento` (formulario).
- Archivo a modificar: `resources/views/layouts/ds-app.blade.php`

---

## 5. Dashboard operativo — contenido definido

**Estado:** Implementado básico (muestra stats de caja del día). Falta el contenido completo.

**Contenido definido para el dashboard operativo:**
- Estado de caja hoy (abierta / cerrada / sin caja) con acciones directas
- **Alumnos con deuda** — lista rápida de alumnos con deuda pendiente, clickeable para ir a cobrar
- **Clases del día** — clases asignadas al operativo con indicador si ya tiene asistencia registrada
- **Cajas rechazadas** — si el admin rechazó alguna caja anterior, advertencia visible
- Stats del día: cobrado, cobros registrados, cajas

**Nota:** El dashboard operativo es muy distinto al del admin. El admin ve métricas globales; el operativo ve su estado de trabajo del día.

**Archivos a modificar:**
- `app/Http/Controllers/OperativoDashboardController.php`
- `resources/views/operativo/dashboard.blade.php`

---

## 6. Dashboard admin — estado actual

**Estado:** Implementado básico. Tiene stats globales y accesos rápidos. No documentado como pendiente de cambios por ahora.

---

## Prioridad sugerida

1. **Revisión de alumnos antes de deudas** (impacta integridad de datos — deudas ficticias)
2. **Regla de primer pago en PagoCuotaService** (impacta cobros reales)
3. **Cambio de plan en flujo de cobro** (mejora flujo del operativo)
4. **Dashboard operativo completo** (usabilidad)
5. **Sidebar** (navegación)
