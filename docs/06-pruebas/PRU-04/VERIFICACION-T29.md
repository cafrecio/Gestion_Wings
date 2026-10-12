# Informe de Verificación — T29: Buscador de Cobrar lista solo a quienes deben

**Fecha:** 12/10/2026  
**Verificador:** Gemini CAB (**LOG GEM CAB**)  
**Commit evaluado:** `8a8a6b8` (Claude Opus 5.5)  
**Resultado:** **APROBADO (CERRADO)**  

---

## 1. Alcance y objetivo de la verificación

Verificar la regla de negocio aprobada por Carlos el 12/10/2026 para el buscador de cobrar (`web.caja.cobrar-cuota`, método `CajaWebController::cobrarCuotaSelect`):
1. **Sin buscar:** La lista muestra exclusivamente a los alumnos que deben, coincidiendo exactamente con el número anunciado en el encabezado («X alumnos con deuda pendiente» o «Sin deudas pendientes»).
2. **Buscando por nombre, apellido o DNI:** Se puede encontrar a cualquier alumno activo (aunque esté al día o no tenga cuota generada) y cobrarle el mes en curso o por adelantado.
3. **Definición de "deber":** Se basa en el cálculo canónico de saldo (`CobranzaEstadoService::saldoDeAlumnos`): cuotas impagas de meses ya iniciados (`periodo <= now()->format('Y-m')`) más inscripción pendiente (`CargoAlumno` vigente con saldo > 0). Un mes futuro no cuenta como deuda.

---

## 2. Metodología y pruebas ejecutadas

Se diseñó e implementó una suite completa de verificación independiente en:  
`docs/06-pruebas/PRU-04/evidencia-t29/VerificacionT29GeminiTest.php`  
(11 pruebas, 113 aserciones, **100% PASS** en `wings_testing_gemini`).

### Detalle de cada caso verificado:

| # | Requisito / Caso | Resultado | Evidencia |
|---|---|---|---|
| 1 | **Solo deudores sin buscar y coincidencia con encabezado:** 15 deudores y 5 al día. Página 1 muestra 12 deudores; página 2 muestra 3 deudores. Ningún alumno al día aparece en ninguna página. Encabezado dice «15 alumnos con deuda pendiente». | **PASS** | `test_01_sin_buscar_lista_solo_deudores_coincide_con_encabezado_y_pagina_correctamente` |
| 2 | **Alumno al día alcanzable por búsqueda y cobrado por camino real:** Alumno al día no aparece sin buscar; buscando por apellido o DNI aparece con punto verde y botón Cobrar. Se ejecutó el POST real a `web.caja.pagar`: pago registrado en base, cuota pagada, movimiento operativo asentado y recibo generado. | **PASS** | `test_02_alumno_al_dia_no_aparece_sin_buscar_aparece_buscando_y_se_le_cobra_por_camino_real` |
| 3 | **Alumno recién dado de alta con cuota del mes:** Alumno dado de alta en mes vigente con cuota pendiente generada al alta: aparece sin buscar con punto rojo y se cuenta en el encabezado. | **PASS** | `test_03_alumno_recien_dado_de_alta_con_cuota_del_mes_aparece_sin_buscar` |
| 4 | **Alumno sin cuota del mes generada:** Alumno activo sin deuda cargada para el mes actual: no aparece sin buscar, aparece buscándolo con punto verde y saldo $0, y la pantalla de cobro le ofrece el mes en curso. | **PASS** | `test_04_alumno_sin_cuota_del_mes_generada_no_aparece_sin_buscar_y_buscando_se_ofrece_el_mes` |
| 5 | **Deuda solo de inscripción vs mes futuro pendiente:** El que debe solo inscripción aparece sin buscar con punto rojo y saldo $5.000. El que tiene cuota de mes futuro pendiente (noviembre) no aparece ni suma en el encabezado. | **PASS** | `test_05_deuda_solo_inscripcion_aparece_sin_buscar_y_mes_futuro_pendiente_no_aparece` |
| 6 | **Alumno inactivo con deuda:** Alumno dado de baja con deuda vencida: no aparece sin buscar, no suma al contador de deudores y tampoco aparece buscándolo por DNI o nombre. | **PASS** | `test_06_alumno_inactivo_con_deuda_no_aparece_ni_se_cuenta_ni_buscandolo` |
| 7 | **Dueño (ADMIN) sin caja abierta:** Accede a Cobrar sin abrir caja: ve solo deudores sin buscar, busca al alumno al día y le cobra directamente (impactando en Cashflow sin caja operativa). | **PASS** | `test_07_duenio_sin_caja_abierta_ve_mismo_comportamiento_y_cobra_directo` |
| 8 | **Permisos de PROFESOR:** Usuario con rol profesor recibe HTTP 403 Forbidden al intentar acceder a la pantalla. | **PASS** | `test_08_profesora_no_puede_entrar_a_cobrar_cuota` |
| 9 | **Accesos desde otras pantallas:** Enlaces válidos y funcionales verificados desde Caja index (`web.caja.index`), Inicio Operativo (`/operativo`) y navegación de retorno. | **PASS** | `test_09_pantallas_de_origen_tienen_enlaces_validos_hacia_el_buscador` |
| 10 | **Comparación matemática exacta con Cobranza:** Verificada la correlación entre deudores en Cobrar y el módulo Cobranza. | **PASS** | `test_10_comparacion_exacta_buscador_cobrar_vs_pantalla_cobranza` |
| 11 | **Casos no cubiertos por Claude:** DNI parcial, mayúsculas/minúsculas (`zAbAlEtA`), pago parcial (saldo $28.000), deuda 100% condonada (no aparece), paginación de búsqueda preservando parámetro `search`, y búsqueda con espacios en blanco (`"   "`). | **PASS** | `test_11_casos_adicionales_no_cubiertos_por_claude` |

---

## 3. Revisión de las 3 pruebas viejas adaptadas por Claude

Se inspeccionaron minuciosamente los cambios introducidos por Claude en commit `8a8a6b8`:
1. `tests/Feature/CobrarPrimeraCuotaWebTest.php`:
   - *Cambio:* Se agregó la búsqueda explícita `['search' => 'Primera']` para ubicar al alumno al día y cobrarle la primera cuota.
   - *Evaluación:* No tapa ningún defecto; antes la prueba asumía que cualquier alumno activo sin cuota generada aparecía sin buscar, lo cual provocaba el listado masivo indebido (100 alumnos).
2. `tests/Feature/P2Entrega2FichaCobroAdelantadoTest.php`:
   - *Cambio:* Para cobrarle a Martina Stoessel (al día), el admin entra a `web.caja.cobrar-cuota` y busca `Stoessel`.
   - *Evaluación:* Correcto; la prueba verifica el flujo de cobro adelantado de A17 sin alterar la regla.
3. `tests/Feature/SaldoUnicoPorAlumnoTest.php`:
   - *Cambio:* Ana Pérez tiene cuota pendiente con saldo 0; se verifica que sin buscar no aparece y buscándola se la encuentra.
   - *Evaluación:* Correcto; preserva la regla de saldo único y elimina la contradicción de listar a quien no debe nada.

---

## 4. Comparación entre Buscador de Cobrar y Pantalla de Cobranza

La regla y los números entre ambas pantallas se comportan con coherencia:

- **Buscador de Cobrar (`web.caja.cobrar-cuota`):**
  - Objetivo: saber a quién se le puede cobrar plata hoy.
  - Cuenta a todo alumno activo con `saldo['total'] > 0`.
  - Incluye:
    1. Cuotas vencidas de meses anteriores (`DEUDOR`).
    2. Cuota del mes en curso vencida tras los días de gracia (`MOROSO`).
    3. Cuota del mes en curso emitida dentro del período de gracia (`EN_PLAZO`).
    4. Inscripción al club pendiente (`INSCRIPCION`), aun si sus cuotas mensuales están al día.
- **Pantalla de Cobranza (`web.cobranza.index`):**
  - Objetivo: gestión y seguimiento de cobranza punitoria / seguimiento administrativo.
  - La pestaña por defecto **«Con deuda (Deudores y morosos)»** lista únicamente a los alumnos con plazo vencido (`DEUDOR` y `MOROSO`). No lista en la tabla a quienes están dentro de los días de gracia (`EN_PLAZO`) ni a quienes deben únicamente la inscripción (su estado de cuota es `AL_DIA`).
  - Sin embargo, en el resumen de tarjetas de Cobranza:
    - `total_adeudado` en pesos ($) es **exactamente idéntico** a la suma de todas las deudas (cuotas + inscripciones).
    - La suma de alumnos `DEUDOR` + `MOROSO` + `EN_PLAZO` + `alumnos AL_DIA con inscripción pendiente` iguala **exactamente** la cantidad de alumnos mostrados en el encabezado de Cobrar.

---

## 5. Pruebas de la suite completa

Corrida completa en `wings_testing_gemini`:
- **664 tests pasando (6.233 aserciones).**
- Los 27 tests específicos de Cobrar, cuotas, cobro adelantado y saldo único pasaron al 100%.
- El guardián de documentación y el detector de divergencia de tableros pasaron sin observaciones.

---

## 6. Qué no se probó

- **Interacción visual manual con clicks humanos en navegador:** No se probó abriendo Chrome manualmente en un servidor web local; la totalidad de los flujos se validó mediante tests HTTP automatizados de Laravel Feature.
- **Entorno VPS / Producción:** No se tocó el sitio de prueba (`test.gestionar-te.com.ar`) ni se alteró su reloj simulado.
- **Medios de pago externos reales:** No se probaron APIs de terceros (Mercado Pago, transferencias bancarias externas), validándose los tipos de caja internos configurados (Efectivo, etc.).
