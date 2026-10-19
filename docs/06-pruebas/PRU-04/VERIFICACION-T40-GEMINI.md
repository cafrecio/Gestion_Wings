# Verificación de Lógica T40 — Reintento de generación de cuotas mensuales y constancia

**Fecha:** 19/10/2026  
**Verificador:** Gemini (Oficina CyE)  
**Implementó:** Claude CyE (commit `c9624c47`)  
**Decisión:** DU-52 (13/10/2026), caso CBZ-020  
**Contrato:** `docs/02-contratos/Wings-contrato-estadosAlum-cobranza-asistencia-V1.md` §2 («Si el día 1 no corre»)  
**Resultado:** **APROBADA** (sin fallas lógicas, no duplica cuotas, no desborda, avisos tolerantes a fallos)

---

## 1. Contexto y objetivos de la verificación

- **Regla de negocio (Carlos, DU-52 / CBZ-020):**
  - El generador de cuotas automáticas corre todos los días a las 06:00 vía cron/scheduler.
  - El día 1 de cada mes genera las cuotas del mes nuevo.
  - Del día 2 en adelante genera **solo si** el mes actual aún no figura generado **y** el mes anterior sí figura generado (para evitar generar meses salteados tras caídas prolongadas).
  - No duplica cuotas existentes ni envíos a revisión.
  - Avisa al administrador si generó con atraso (día 2+) o si la generación no se pudo realizar porque falta el mes anterior.
  - El resumen diario de las 08:00 muestra la advertencia mientras las cuotas del mes sigan sin generarse.
- **Sin pantallas:** La tarea no incorpora vistas de usuario ni componentes de UI, por lo que no requiere aprobación visual.
- **Entorno de verificación:** Base de datos descartable `wings_testing_gemini` en Oficina CyE. Base de producción y sitio de prueba no tocados.

---

## 2. Verificación detallada punto por punto

### a) `GenerarDeudasMensualesCommand`: núcleo de generación conservado
- La lógica esencial de generación que residía en `handle()` fue extraída limpiamente al método `generar(string $periodo, bool $conAvisoAtraso = false): int`.
- Se verificó que los criterios de negocio no sufrieron alteraciones:
  - Filtro de alumnos activos (`activo = 1`).
  - Criterio de elegibilidad: asistencia registrada en el mes anterior o alta reciente en el mes anterior con pago de inscripción/cuota.
  - Omisión de alumnos que ya cuentan con deuda para el período (`DeudaCuota::where('alumno_id', ...)->where('periodo', ...)->exists()`).
  - Envío a `AlumnoRevisionCobranza` si el alumno es elegible pero no tiene plan asignado o el plan no tiene precio configurado.
  - Creación de deuda con `DeudaCuota::crearDeudaSiNoExiste(...)` respetando tarifas y planes.
- Al término exitoso de la generación, se registra de forma obligatoria la constancia en `GeneracionCuotas::marcarGenerado($periodo)`.

### b) `GeneracionCuotas::faltaGenerar`: reglas de calendario y cambio de año
- Implementado en `app/Models/GeneracionCuotas.php`:
  1. Si `GeneracionCuotas::yaGenerado($periodoActual)` es verdadero, retorna inmediatamente `false`.
  2. Si es el día 1 (`$hoy->day === 1`), retorna `true`.
  3. Si es el día 2 o posterior (`$hoy->day >= 2`), calcula el mes inmediatamente anterior con `$hoy->copy()->startOfMonth()->subMonth()->format('Y-m')`.
     - Si el mes anterior está generado (`yaGenerado($mesAnterior)`), retorna `true`.
     - Si el mes anterior **no** está generado, retorna `false` (no genera automáticamente sin intervención previa).
  4. Resuelve correctamente el cambio de año: en enero (`2027-01-02`), `$mesAnterior` evalúa a `2026-12`, verificando la constancia del mes de diciembre del año anterior.

### c) Idempotencia, cortes abruptos y recuperación
- **No duplicación:** `crearDeudaSiNoExiste` y `AlumnoRevisionCobranza::firstOrCreate` aseguran que una ejecución repetida o reintentada no genere deudas duplicadas ni duplique filas en revisión de cobranza.
- **Tolerancia a fallos:** La constancia en la tabla `generaciones_cuotas` se inserta únicamente al final del método `generar()`. Si el proceso se detiene o se aborta a mitad de camino, la constancia no se persiste. Al día siguiente a las 06:00, `faltaGenerar()` sigue siendo `true`, reintentando el proceso completo y retomando los alumnos pendientes sin duplicar los ya creados el día anterior.

### d) Inmutabilidad del mes ya atendido
- Si el día 1 la corrida finalizó con éxito, la constancia queda grabada. A partir del día 2, `GeneracionCuotas::faltaGenerar()` retorna `false`.
- Altas nuevas o reactivaciones posteriores durante el transcurso del mes no son afectadas por el generador diario automático, manteniéndose el principio de corte mensual.

### e) Ejecución manual por técnico y validación de parámetros
- Un técnico puede forzar la generación manualmente:
  - `php artisan cobranza:generar-deudas` (período actual) o `php artisan cobranza:generar-deudas --periodo=AAAA-MM`.
  - Ambas variantes registran la constancia en `generaciones_cuotas`.
- Si se suministra `--si-falta` combinado con `--periodo`, el comando lo rechaza de inmediato con error: *«La opción --si-falta solo opera sobre el mes corriente; no se puede combinar con --periodo.»* (código de salida 1).

### f) `AvisoAdminService`: resiliencia y resumen diario
- Se verificaron los dos métodos de notificación:
  - `cuotasGeneradasConAtraso(string $periodo, int $dia)`
  - `cuotasNoGeneradas(string $periodo, string $motivo)`
- Ambos utilizan la infraestructura interna `$this->enviar(...)`, protegida por `try / catch (\Throwable $e)` con registro en `Log::warning()`. Si el servicio de correo o Telegram falla o no está configurado, la excepción es capturada y no interrumpe ni hace fallar el comando de generación.
- En `AvisoAdminService::resumenDiario()`, se incluye la advertencia `⚠️ Cuotas del mes sin generar` si `GeneracionCuotas::faltaGenerar(now())` es `true`. Cuando las cuotas se generan, la línea desaparece del resumen matutino de las 08:00.

### g) Programación en `routes/console.php`
- Se programó:
  ```php
  Schedule::command('cobranza:generar-deudas --si-falta')
      ->dailyAt('06:00')
      ->withoutOverlapping()
      ->onOneServer();
  ```
- No existen otras llamadas duplicadas ni conflictos con otros comandos cron.
- `withoutOverlapping()` previene carreras si un proceso se demora y `onOneServer()` asegura unicidad en entornos distribuidos.

### h) Migración `2026_10_19_120000_create_generaciones_cuotas.php` y despliegue a producción
- La migración crea la tabla `generaciones_cuotas` con clave primaria `periodo` (`char(7)`), timestamps y comentarios claros.
- En entornos que no sean de testing (`!app()->runningUnitTests()`), inserta automáticamente el período en curso (`now()->format('Y-m')`).
- **Comportamiento en producción:** Al desplegar la migración a mitad de mes (ej. 19 de octubre), el mes en curso queda marcado como ya generado. Esto evita que el scheduler genere cuotas extemporáneas a mitad de mes para alumnos del mes actual y garantiza que el 1 de noviembre exista la constancia del mes anterior requerida por `faltaGenerar()`.

### i) Compatibilidad con Reloj Simulado
- Tanto `GenerarDeudasMensualesCommand` como `GeneracionCuotas` y `AvisoAdminService` emplean `Carbon::now()` / `now()`.
- Respetan plenamente `Carbon::setTestNow()` y la configuración del reloj simulado de pruebas sin recurrir a llamadas nativas `date()` o `time()` desincronizadas.

### j) Integridad de las pruebas automatizadas
- Las 9 pruebas de `tests/Feature/GeneracionCuotasReintentoT40Test.php` fueron inspeccionadas.
- Todas verifican el estado persistido en base de datos (`DeudaCuota::count()`, `GeneracionCuotas::yaGenerado()`, `assertDatabaseHas('generaciones_cuotas', ...)`) y los eventos de notificación enviados vía `Notification::assertSentOnDemand()`, validando la semántica del negocio de forma rigurosa.

---

## 3. Pruebas ejecutadas y resultados

Ejecutadas sobre la base descartable `wings_testing_gemini`:

1. **Pruebas de reintento T40:**
   ```bash
   DB_DATABASE=wings_testing_gemini php artisan test tests/Feature/GeneracionCuotasReintentoT40Test.php
   ```
   **Resultado:** **9 passed (39 assertions)**, duración: 8.92s.

2. **Pruebas de generación de deudas:**
   ```bash
   DB_DATABASE=wings_testing_gemini php artisan test tests/Feature/GenerarDeudasMensualesCommandTest.php
   ```
   **Resultado:** **1 passed (6 assertions)**, duración: 10.09s.

3. **Control de sintaxis:**
   ```bash
   php -l app/Console/Commands/GenerarDeudasMensualesCommand.php
   php -l app/Models/GeneracionCuotas.php
   php -l app/Services/AvisoAdminService.php
   php -l database/migrations/2026_10_19_120000_create_generaciones_cuotas.php
   php -l tests/Feature/GeneracionCuotasReintentoT40Test.php
   ```
   **Resultado:** Sin errores de sintaxis en ninguno de los archivos.

---

## 4. Dictamen Final

**APROBADA.** La implementación de T40 por Claude CyE resuelve a la perfección la recuperación de caídas del día 1, respeta la idempotencia, asegura la continuidad mensual y notifica correctamente al dueño sin riesgos colaterales.
