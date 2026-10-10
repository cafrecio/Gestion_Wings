# T17 — Implementación: Inicio cuenta cuotas + inscripciones en la deuda

10/10/2026 · Gemini CyE · desarrollo local, sin despliegue.

## Qué se cambió

1. **`app/Services/CobranzaEstadoService.php`**:
   - Se extrajo el cálculo de inscripciones pendientes de alumnos activos a un método público reutilizable: `totalInscripcionesPendientes(?Collection $alumnos = null): float`.
   - Criterio canónico idéntico al de Cobranza: busca los cargos de tipo `INSCRIPCION` en estado `VIGENTE` asociados a los DNI de los alumnos activos (`Alumno::where('activo', true)`), calculando `max(0, monto_original - monto_pagado - monto_condonado)`.
   - `resumenDashboard()` delega en este método.

2. **`app/Http/Controllers/WebController.php` (`adminDashboard`)**:
   - Si `reporte['deuda']['total']` no es nulo (es decir, el registro y cobertura histórica están disponibles para el mes en curso), se obtienen las inscripciones pendientes mediante `$cobranzaService->totalInscripcionesPendientes()`.
   - Se convierte a centavos y se suma al total:
     - `reporte['deuda']['cuotas']`: almacena el total de cuotas históricas.
     - `reporte['deuda']['inscripciones']`: almacena los centavos correspondientes a inscripciones vigentes pendientes.
     - `reporte['deuda']['total'] += $inscripcionesCentavos`: asegura que la tarjeta «Alumnos por cobrar» de Inicio coincida al peso con Cobranza.
   - Si el historial no está disponible (`reporte['deuda']['total'] === null`), no se inventa saldo y se preserva `null` como antes.

3. **`tests/Feature/ReporteMensualTest.php`**:
   - Se ajustó la aserción de conciliación entre Inicio y Reportes para contemplar la clave `cuotas` e `inscripciones` en el array de deuda de Inicio.

4. **`tests/Feature/InicioDeudaCuotasEInscripcionesTest.php`**:
   - Se agregaron 7 pruebas automatizadas que cubren exhaustivamente la conciliación al peso entre Inicio y Cobranza:
     1. Solo cuotas.
     2. Cuotas + inscripción pendiente.
     3. Inscripción pagada.
     4. Inscripción pagada en parte.
     5. Alumna en dos deportes con una sola inscripción.
     6. Inscripción condonada.
     7. Alumno inactivo con inscripción impaga (excluido en ambas pantallas).

5. **Documentación sincronizada**:
   - `docs/00-estado/ESTADO-ACTUAL.md`: actualizado a 611 pruebas.
   - `docs/00-estado/CHECKLIST-CARLOS.md`: actualizado a 611 pruebas.
   - `docs/00-estado/PLAN-PRODUCCION.md`: actualizado a 611 pruebas.

---

## Hallazgo sobre Reportes histórico

Tal como se indicó en las condiciones de la tarea:
- **Inicio** muestra exclusivamente el mes en curso y ahora incluye cuotas + inscripciones.
- **Reportes** (`ReporteMensualService::obtener($mes)` / `saldoHistorico('CUOTA', ...)`) calcula exclusivamente el saldo histórico acumulado de la tabla `reporte_eventos` para eventos de tipo `CUOTA`. Por tanto, los cortes de meses pasados y la pantalla `/reportes` siguen mostrando solo cuotas pendientes y no suman los cargos de inscripción. Esto no se modificó para respetar la condición 1 del alcance.

---

## Qué se probó y qué no

### Probado:
- Suite específica `InicioDeudaCuotasEInscripcionesTest` (7 tests, 27 aserciones, 100% PASS en `wings_testing_gemini`).
- Regresiones de `CobranzaEntrega1Test`, `CobranzaEstadoServiceTest` y `ReporteMensualTest` (31 tests, 237 aserciones, 100% PASS en `wings_testing_gemini`).
- `DocumentacionNoMienteTest` (1 test, 7 aserciones, PASS).
- Compilación de vistas Blade: `php artisan view:cache` y `view:clear` sin errores.
- Verificación de sintaxis `php -l` en todos los archivos modificados.
- Diff de vistas y CSS: 0 archivos modificados (`git diff --stat -- resources/views resources/css` vacío).
- Suite completa en `wings_testing_gemini`:
  `Tests: 2 skipped, 609 passed (5053 assertions) Duration: 496.29s`

### No probado:
- No se probó interacción manual en el sitio de prueba remoto `https://test.gestionar-te.com.ar` (corresponde a Claude tras verificar, sin despliegue en este turno).
