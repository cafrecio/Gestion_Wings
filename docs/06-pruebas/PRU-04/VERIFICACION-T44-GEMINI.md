# Verificación Independiente de T44 — Historial de Tarifas y Vigencia por Alumno y Mes

**Fecha:** 19/10/2026  
**Agente verificador:** Gemini (Antigravity) — LOG GEM CAB (en reemplazo de Claude por falta de créditos)  
**Implementado por:** Codex CAB (commits `10a42cd` y `9826e49`, visto bueno visual en `e04d89c`)  
**Base de verificación:** `wings_testing_gemini`  
**Estado resultante:** APROBADA / CERRADA  

---

## 1. Veredicto y Decisiones de Carlos

Se verificó de forma exhaustiva e independiente la implementación de **T44** contra el código fuente, la base de datos y la totalidad de las reglas de negocio establecidas por Carlos:

1. **Aumento rige desde el mes siguiente para alumnos existentes:**
   - Comprobado: un cambio de tarifa en el catálogo durante el mes no altera la tarifa de los alumnos existentes para ese período. Rige a partir del primer día del mes siguiente.
   - Las altas nuevas en el mes toman la tarifa vigente al momento exacto de su fecha real de ingreso (`fecha_alta`), aplicando el porcentaje correspondiente según el día de alta.
2. **Múltiples aumentos en un mismo mes:**
   - Comprobado el caso exacto de Carlos ($40.000 → $45.000 el 20/10 → $50.000 el 25/10):
     - Alumnos existentes: pagan $40.000 en octubre y $50.000 en noviembre.
     - Alta del 22/10: paga $45.000 en octubre y $50.000 en noviembre.
     - Alta del 26/10: paga $50.000 en octubre y $50.000 en noviembre.
3. **Inmutabilidad de cuotas generadas y pagos adelantados:**
   - Comprobado: ningún aumento en el catálogo recalcula deudas ya generadas (incluidas las emitidas el día 1 por `GenerarDeudasMensualesCommand`) ni pagos adelantados registrados bajo T18.
4. **Reglas para el cambio individual de frecuencia (`CambioPlanService`):**
   - **Bajar de plan:** siempre se difiere al primer día del mes siguiente (`addMonthNoOverflow()->startOfMonth()`), tanto si el alumno asistió a clases como si no tuvo asistencias. El mes actual conserva el plan y la cuota vigentes.
   - **Subir de plan con cuota pendiente o parcial:** es inmediato (`fecha_desde = today()`); ajusta el `monto_original` de la cuota al nuevo valor y conserva íntegramente lo ya pagado. Ejemplo comprobado: cuota de $40.000 con $10.000 pagados ajustada a un plan de $60.000 deja un saldo pendiente exacto de $50.000 por cobrar.
   - **Subir de plan con mes saldado:** se difiere al primer día del mes siguiente sin modificar la cuota del mes.
   - **Cuotas de alta/reingreso al subir de plan:** conservan su `porcentaje_alta` original sin recalcularse con la fecha tardía del cambio.

---

## 2. Separación entre lo Verificado y lo Inferido (Protocolo AGENTS §6c)

### Lo Verificado

1. **Migración `2026_10_18_230000_create_grupo_plan_precios.php`:**
   - La tabla `grupo_plan_precios` define `precio_mensual DECIMAL(12, 2)` y `vigente_desde DATE NULL` con índice `['grupo_plan_id', 'vigente_desde', 'id']`.
   - La migración inserta los planes existentes en bloques de 500 con `vigente_desde = null`, preservando el valor inicial desde siempre sin pérdida de centavos.
2. **Cuerpo de `GrupoPlan`:**
   - Método `save()`: envuelto en transacción con `lockForUpdate()` para serializar escrituras concurrentes.
   - Eventos Eloquent: en `created` registra `vigente_desde = null`. En `updated`, evalúa `wasChanged('precio_mensual')`; si el precio no cambió, no inserta registros redundantes.
   - Método `precioParaPeriodo(string $periodo, $fechaAlta)`:
     - Si `$fechaAlta` coincide con `$periodo`, corta en la fecha del alta (`$alta->toDateString()`).
     - Para meses posteriores o alumnos existentes (`$fechaAlta === null`), corta en el último día del mes anterior (`$inicio->subDay()->toDateString()`).
     - Ordena por `vigente_desde DESC, id DESC`, resolviendo de forma determinista múltiples aumentos en el mismo día.
3. **Cuerpo de `CambioPlanService`:**
   - `seDifiere(Alumno $alumno, GrupoPlan $nuevo)`: devuelve `true` si el nuevo plan tiene menos clases por semana, o si la deuda del período está condonada o saldada (`saldo_pendiente <= 0`).
   - `precioDelMes`: mantiene el `porcentaje_alta` original si existe.
   - `cambiar`: si el cambio es futuro y ya existía un plan programado futuro, lo actualiza en lugar de duplicarlo. Si la cuota ya tenía pagos mayores al precio del nuevo plan, arroja `ValidationException`.
4. **Relaciones en `Alumno`:**
   - `planActivo()`: filtra `fecha_desde <= today()->endOfDay()` y (`fecha_hasta IS NULL OR fecha_hasta >= today()`), ordenando por `fecha_desde DESC, id DESC`.
   - `planProgramado()`: filtra `activo = true` y `fecha_desde > today()->endOfDay()`.
   - En `AlumnoWebController::update()`: no ejecuta `cambiar()` si el formulario envía el id del plan programado o del activo, impidiendo que la edición de datos personales (celular, etc.) adelante o anule un cambio futuro.
5. **Consumo unificado en el sistema:**
   - `GenerarDeudasMensualesCommand:104`: usa `precioParaPeriodo`.
   - `PagoCuotaService`:
     - `previsualizarAltaYPrimerPago:448`
     - `crearDeudaAlta:513`
     - `mesesPasadosSinCuota:574` (T58)
     - `crearCuotaMesPasado:614` (T58)
     - `crearDeudaSiNoExiste:736`
     - `previsualizarCobroAdelantado:1005` (T18)
   - `RevisionCobranzaService:52`: resolución `CONTINUA` usa `precioParaPeriodo`.
   - `PrimeraCargaExcelService:34`: exporta catálogo con `precioParaPeriodo`, e importa conservando el saldo declarado en cuotas (`monto_original = $par['monto']`).
   - `CajaWebController`: pantalla `cobrar()` y acción `pagar()` delegan íntegramente en `CambioPlanService`.
6. **Búsqueda de lecturas directas de `precio_mensual`:**
   - Comprobado mediante `Select-String`: en `app/` no existen lecturas de `precio_mensual` para cobros ni deudas fuera de `GrupoPlan`. En `resources/views/`, solo se lee en `grupos/index`, `grupos/show` y `grupos/_form` para la definición y edición del catálogo.
7. **Seguridad y API:**
   - En `bootstrap/app.php:17`, la ruta `routes/api.php` permanece comentada y deshabilitada.
8. **Aspecto visual:**
   - El botón `Nuevo` separado del título en Crear y Editar planes (celular y escritorio) cuenta con la aprobación expresa de Carlos registrada el 19/10/2026: *«Ok, pasan»* ([GALERIA.html](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-t44-titulo/GALERIA.html)).

### Lo Inferido

- No se observan inferencias subjetivas: todas las reglas de negocio fueron validadas contra aserciones directas de base de datos y respuestas HTTP del framework.

---

## 3. Pruebas Automatizadas y Resultados

Todas las corridas se ejecutaron exclusivamente sobre la base de datos descartable `wings_testing_gemini`:

1. **Pruebas principales de T44 (`PrecioPlanVigenciaT44Test`):**
   - 19 pruebas ejecutadas y aprobadas (89 aserciones).
2. **Pruebas de cambio de plan y matrices (`CambioPlanCobroTest` y `CambioPlanConDescuentoMatrizTest`):**
   - 17 pruebas ejecutadas y aprobadas (105 aserciones).
3. **Suite completa del proyecto:**
   - **882 aprobadas, 3 omitidas, 0 fallidas (7.823 aserciones)** en 935,82 s.
   - Las 3 omitidas corresponden a pruebas intencionalmente deshabilitadas por contratos previos sin relación con T44.

---

## 4. Conclusión y Cierre

La implementación entregada por Codex es robusta, cubre todos los casos límite y respeta estrictamente los contratos y decisiones de Carlos. Con la validación de código y suite completa finalizada por Gemini, y la aprobación visual previamente otorgada por Carlos, **la tarea T44 queda cerrada**.
