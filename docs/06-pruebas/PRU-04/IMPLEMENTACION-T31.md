# Implementación T31 — El que debe la inscripción también es moroso o deudor

**Fecha:** 13/10/2026  
**Responsable:** Gemini (Antigravity)  
**Máquina:** CyE (`Ventas_CyE`) — `LOG GEM CYE`  
**Estado:** A verificar (para Claude)

---

## 1. Reglas de Negocio Implementadas

### A. Regla de Carlos (12/10/2026)
«Todo el que debe plata es deudor o moroso, según hace cuánto lo debe.»
- La inscripción impaga cuenta desde la fecha de ingreso del alumno (`fecha_alta` del registro al que se le imputa).
- El estado general del alumno es el peor entre sus cuotas y su inscripción:  
  `DEUDOR > MOROSO > EN_PLAZO > AL_DIA`.
- Inscripción impaga de un mes ya cerrado convierte al alumno en **DEUDOR**.
- Inscripción impaga fuera de gracia en el mes corriente convierte al alumno en **MOROSO**.
- Inscripción impaga dentro de los días de gracia es **EN PLAZO**.
- Inscripción pagada o condonada no altera el estado; pagada en parte sigue contando por el saldo pendiente.
- Persona en dos deportes: la inscripción afecta a un solo registro (el mismo que carga el saldo). El otro registro no se vuelve deudor. Si el registro asignado se da de baja, el registro activo hereda la inscripción.

### B. Agregado de Carlos: Plazo propio del alumno nuevo (13/10/2026)
«El alumno nuevo no arranca moroso nunca: tiene sus propios días de gracia desde el alta para pagar lo que debe (inscripción y cuota del mes de ingreso). No corre con el calendario general hasta el mes siguiente.»
- Clave de configuración: `dias_plazo_alta` (entero, días corridos, valor inicial 7).
- Aplica a las deudas nacidas con el alta: la inscripción y la cuota del mes de ingreso.
- Rige el plazo propio (`dias_plazo_alta`) o la gracia general del mes (`dias_gracia_cobranza`), el que venza más tarde (el más favorable al alumno).
- Si el plazo cruza de mes (ej. alta el 28/09 con 7 días): está `EN_PLAZO` hasta el 04/10; el 05/10 pasa directo a `DEUDOR` (mes de septiembre cerrado, plazo vencido).
- Cuotas de meses posteriores al de ingreso rigen exclusivamente por el calendario general (`dias_gracia_cobranza`).
- `dias_gracia_restantes`: cuando está `EN_PLAZO`, devuelve los días que le quedan de ese plazo (o del general, el que venza más tarde).

---

## 2. Archivos Modificados y Creados

1. `database/migrations/2026_10_13_080000_add_dias_plazo_alta_to_configuraciones.php`:
   - Crea la clave `dias_plazo_alta` con valor inicial `7`, tipo `integer`, y descripción explicativa.
2. `app/Services/CobranzaEstadoService.php`:
   - `diasPlazoAlta()`: lee `dias_plazo_alta`, tipado a int; lanza `\LogicException` si falta o es `<= 0`. No tiene fallback silencioso: si la clave no existe en la base, avisa inmediatamente.
   - `limiteVencimientoAlta()`: determina el vencimiento más favorable entre el plazo propio del alta y los días de gracia del mes.
   - `calcularEstadoInscripcion()`: calcula estado y días restantes de la inscripción impaga según las reglas del 12/10 y 13/10.
   - `calcularEstadoDesdeDeudas()`: incorpora el plazo propio para la cuota del mes de ingreso (incluyendo cruces de mes) y calcula días restantes.
   - `estadoAlumno()`, `estadosParaAlumnos()`, `filtrarAlumnosPorEstado()`, `listadoCobranza()`, `resumenDashboard()`: actualizados para combinar el peor estado entre cuotas e inscripción sin consultas N+1.
   - `saldoDeAlumnos()`: antigüedad de deuda (`mas_antigua`) considera `fecha_alta` (formato `Y-m`) para alumnos que deben solo inscripción.
3. `app/Http/Controllers/OperativoDashboardController.php`:
   - Línea 109: reemplaza la consulta cruda sobre `DeudaCuota` por `$this->cobranzaService->listadoCobranza()->count()`. Resuelve la divergencia donde el Inicio de la operativa no contaba inscripciones impagas y contaba indebidamente cuotas de meses futuros no iniciados.
4. `docs/02-contratos/Wings-contrato-estadosAlum-cobranza-asistencia-V1.md`:
   - Sección 3: incorporadas las enmiendas del 12/10/2026 y 13/10/2026.
   - Sección 11: actualizada la tabla de estado de implementación.
5. `tests/Feature/CobranzaEntrega1Test.php`:
   - Actualizada la prueba `test_inscripcion_impaga_convierte_en_deudor_en_listado_ficha_y_resumen_con_mes_cerrado` para reflejar la regla vigente.
6. `tests/Feature/CobranzaInscripcionEstadoTest.php`:
   - 18 pruebas automatizadas cubriendo todos los puntos contractuales y los casos de borde.

---

## 3. Lugares que Muestran o Usan el Estado de Cobranza (Revisados con Archivo y Línea)

| Lugar | Archivo y Línea | Qué muestra / usa | Comportamiento tras el cambio |
|---|---|---|---|
| **Listado de Alumnos** | `app/Http/Controllers/AlumnoWebController.php:83` | Badge de estado (`AL DÍA`, `EN PLAZO`, `MOROSO`, `DEUDOR`) vía `estadosParaAlumnos()`. | Alumnos con inscripción impaga de meses cerrados o fuera de plazo de alta ya no figuran `AL DÍA`: muestran `DEUDOR` o `MOROSO`. |
| **Ficha del Alumno** | `app/Http/Controllers/AlumnoWebController.php:119` | Estado de cobranza en encabezado y tarjeta lateral vía `estadoAlumno()`. | Muestra el peor estado real entre cuotas e inscripción. Quien debe inscripción muestra su condición y días restantes si está en plazo. |
| **Cobranza** | `app/Http/Controllers/CobranzaWebController.php:24` | Listado de deudores y morosos por defecto, tarjetas de resumen superior vía `listadoCobranza()` y `resumenDashboard()`. | En el sitio de prueba, la lista por defecto pasa de 35 a 38 alumnos (se suman Gómez Sofía, Morales Carla y Castro Martina como DEUDORAS por inscripción de meses cerrados). Columna de deuda más antigua muestra mes de ingreso (`Mar 2025`, `Ago 2026`). Tarjetas superiores reflejan 62 al día, 0 en plazo, 22 morosos, 16 deudores. |
| **Buscador de Cobrar** | `app/Http/Controllers/CajaWebController.php:702` | Listado inicial de alumnos con deuda (`total > 0` en `saldoDeAlumnos()`). | Contaba 38 alumnos porque `saldoDeAlumnos` incluía inscripción. Con el cambio, Cobranza lista exactamente a los mismos 38 alumnos: 100% de paridad. |
| **Inicio del Operativo** | `app/Http/Controllers/OperativoDashboardController.php:109` | Tarjeta «Con deuda» en Atención a alumnos que enlaza a `web.cobranza.index`. | Antes calculaba con consulta directa sobre `DeudaCuota` (omitiendo inscripción y sumando cuotas futuras). Ahora usa `$this->cobranzaService->listadoCobranza()->count()`, reportando exactamente 38 alumnos al igual que Cobranza y Cobrar. |
| **Revisión de Cobranza** | `app/Http/Controllers/RevisionCobranzaWebController.php:25`, `app/Services/RevisionCobranzaService.php:27` | Cola de revisión de alumnos sin asistencia en mes previo para resolver baja o continuidad. | No muestra estado de cobranza ni lee saldos; no cambia. |
| **Reportes de Alumnos** | `app/Services/ReporteAlumnosService.php:12` | Métricas de matrícula activa/inactiva, asistencias por clase/grupo y revisiones pendientes. | No muestra estado de cobranza ni lee saldos; no cambia. |
| **Avisos Operativos y Resumen Diario** | `app/Console/Commands/ResumenDiarioAvisosCommand.php:27`, `app/Services/AvisoAdminService.php:60` | Envío de correo al administrador con cajas pendientes, clases sin lista y revisiones. | No muestra estado de cobranza ni lee saldos; no cambia. |
| **Inicio de Administración** | `app/Http/Controllers/WebController.php:71` | Tarjetas financieras y deuda total monetaria consolidada desde `ReporteMensualService::obtener()`. | No calcula estados de cobranza por alumno ni estados de deudor/moroso individuales; no cambia. |

---

## 4. Casos de Borde Probados e Informados

1. **Alumno ingresa un lunes y tiene 7 días de plazo propio:**
   - Ingreso: lunes 05/10/2026 con inscripción impaga de $5.000.
   - Domingo 11/10/2026: `EN_PLAZO` (`dias_gracia_restantes` = 0 por ser el último día).
   - Lunes 12/10/2026: `MOROSO` (mes en curso, plazo vencido).
   - *Resultado en prueba automatizada: PASA.*

2. **Alumno ingresa día 3 del mes con gracia general = 10 y plazo = 7:**
   - Ingreso: 03/10/2026. Plazo propio: 03/10 + 7 días = hasta 09/10 inclusive (vence 10/10 00:00).
   - Gracia general de octubre: hasta 10/10 inclusive (vence 11/10 00:00).
   - Rige el más favorable (gracia general, hasta el 10 inclusive).
   - Día 10/10/2026: `EN_PLAZO`.
   - Día 11/10/2026: `MOROSO`.
   - *Resultado en prueba automatizada: PASA.*

3. **Alumno ingresa el 28 de septiembre (mes de 30 días) con plazo = 7:**
   - Ingreso: 28/09/2026. Plazo: 28/09 al 04/10 inclusive (vence 05/10 00:00).
   - 04/10/2026: `EN_PLAZO` (el plazo se respeta aunque septiembre ya haya cerrado).
   - 05/10/2026: `DEUDOR` directo (el mes de septiembre ya cerró y el plazo venció; no pasa a moroso).
   - *Resultado en prueba automatizada: PASA.*

4. **Alumno con `fecha_alta` a futuro:**
   - Alta el 01/11/2026, evaluado el 15/10/2026.
   - Da `EN_PLAZO`.
   - *Resultado en prueba automatizada: PASA.*

5. **Alumno reactivado:**
   - El sistema no modifica `fecha_alta` al reactivar un alumno inactivo; conserva su fecha de alta original.
   - Si su ingreso fue meses atrás y debe la inscripción, el plazo está vencido y el mes cerrado: queda `DEUDOR`.
   - *Resultado en prueba automatizada: PASA.*

6. **Corrección de `fecha_alta`:**
   - Al editarse `fecha_alta`, el cálculo evalúa el registro actualizado inmediatamente. Al correr la fecha hacia adelante, pasa de `MOROSO` a `EN_PLAZO`.
   - *Resultado en prueba automatizada: PASA.*

7. **Registro con inscripción dado de baja, con otro deporte activo:**
   - Persona con dos deportes. Al darse de baja el registro que cargaba la inscripción (`activo = false`), el servicio la transfiere automáticamente al registro que continúa activo, asumiendo este el estado `DEUDOR`/`MOROSO`.
   - *Resultado en prueba automatizada: PASA.*

8. **Alumno al día en cuotas con inscripción pagada en parte:**
   - Inscripción con saldo pendiente. Sigue computando como deuda por lo que resta y clasifica en `DEUDOR` o `MOROSO`/`EN_PLAZO` según antigüedad.
   - *Resultado en prueba automatizada: PASA.*

9. **Cambio de mes:**
   - Inscripción impaga que estaba `MOROSA` dentro del mes de ingreso pasa a `DEUDORA` ni bien inicia el primer día del mes siguiente.
   - *Resultado en prueba automatizada: PASA.*

---

## 5. Qué se Probó y Qué No

### Qué se probó:
- Las 18 pruebas unitarias y de integración de [CobranzaInscripcionEstadoTest.php](../../../tests/Feature/CobranzaInscripcionEstadoTest.php) (100% PASS).
- Los 9 controles específicos de Claude en [VerificacionT31ClaudeTest.php](evidencia-t31/VerificacionT31ClaudeTest.php) (9 de 9 PASS, incluyendo la sincronización entre Cobranza, Cobrar y el Inicio de la Operativa).
- Las pruebas de regresión del área: `CobranzaEntrega1Test` (8/8), `CuotaAltaEstadoTest` (15/15), `InscripcionTest` (7/7).
- Ausencia de fallback silencioso en `diasPlazoAlta()`: lanza `\LogicException` si la clave no está en la base.

### Qué no se probó («no lo probé»):
- No se probó el despliegue al VPS de producción ni al sitio de prueba (reservado a Claude tras verificación).
- No se probaron interfaces de usuario modificadas en Blade/CSS (por Regla 1 de AGENTS.md, el diseño no se toca y ningún archivo de vista o CSS fue modificado para T31).

---

## 6. Aclaración sobre CashflowCorreccionesT27Test

Se retiró cualquier modificación en `tests/Feature/CashflowCorreccionesT27Test.php` (`git checkout tests/Feature/CashflowCorreccionesT27Test.php`), manteniéndolo intacto en su estado de `main`.
- **Razón del cambio previo:** La tabla `reporte_cobertura` se crea en migraciones con `desde = today()->toDateString()`. Cuando una prueba atrasa el reloj con `Carbon::setTestNow('2026-10-12')` en un día posterior (ej. 13 de octubre), `ReporteMensualService` considera a octubre anterior al inicio de la cobertura si no se ajusta la fila (mismo caso que `ImportesT30` subsana en sus líneas 27-28).
- **Resolución:** Dado que pertenece al ámbito de T27 y no corresponde al alcance de T31, se quitó del conjunto de cambios de T31.
