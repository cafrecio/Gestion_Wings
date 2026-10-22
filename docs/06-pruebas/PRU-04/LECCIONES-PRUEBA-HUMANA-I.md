# Prueba Humana I — Lecciones Aprendidas y Preparación del Día 4 (PRU-04)

> **Documento de enlace técnico y metodológico.**  
> **Fecha:** 22/10/2026 — Oficina CyE.  
> **Propósito:** Registrar las correcciones, acuerdos y lecciones aprendidas durante el diseño y ajuste del **Día 4 (martes 13/10/2026)** para la prueba manual de caminos felices en Wings.

---

## 1. Contexto y premisa de trabajo

La preparación de las pruebas funcionales humanas (PRU-04) tiene como objetivo simular el trabajo diario del club desde la perspectiva de personas reales que operan mediante la interfaz gráfica del navegador.

- **Premisa rectora:** *Pensar, cuestionar lo que pensamos, volver a pensar y recién ahí hacer.*
- **División de roles:**
  - **Carlos:** Dueño de producto. Revisa, aprueba y resuelve decisiones de negocio y diseño.
  - **Gemini (este chat):** Prepara la historia del día (`DIA-04.md`), la matriz técnica de objetivos (`OBJETIVOS-PRUEBA.md`), las instrucciones operativas y la matriz de seguimiento temporal. **No ejecuta la prueba ni altera producto/base/reloj.**
  - **Codex (en chat «Prueba humana»):** Ejecuta la prueba en el navegador Chrome real, siguiendo el cuento, sin consolas ni consultas a base de datos.
  - **Verificador independiente:** Controla la base de datos y el código con posterioridad a la ejecución.

---

## 2. Lecciones aprendidas y correcciones aplicadas

El intercambio entre Codex y Gemini sobre los borradores del Día 4 permitió identificar supuestos no comprobados y errores conceptuales que fueron corregidos rigurosamente:

### Lección 1 — No inventar contadores: partir de la base comprobada y exigir verificación previa
- **El error:** El borrador previo partió de una distribución no acreditada de `62 Al día / 0 En plazo / 15 Morosos / 23 Deudores`, declarándola erróneamente como «auditada» sin respaldo documental ni evidencia fechada.
- **La realidad comprobada:** Los antecedentes certificados de los días anteriores en `DIA-02.md` y `DIA-03.md` (con capturas y verificación de Claude sobre `wingstest` al 10/10 y 12/10) registraron inequívocamente:
  - **65 Al día / 0 En plazo / 22 Morosos / 13 Deudores** (100 alumnos activos, deuda de $2.010.000).
- **La corrección aplicada:**
  1. Se reestructuró la conciliación de cobranza partiendo estrictamente de la base comprobada:
     - **Al día:** 65 inicial + 2 (Renata y Mateo saldan octubre) = **67 Al día**.
     - **En plazo:** 0 inicial + 1 (Mora Benítez alta 13/10 con 7 días de plazo) = **1 En plazo**.
     - **Morosos:** 22 inicial - 2 (Renata y Mateo salen) + 2 (Emma y Dante entran desde Deudores al saldar septiembre) = **22 Morosos**.
     - **Deudores:** 13 inicial - 2 (Emma y Dante pasan a Morosos) = **11 Deudores**.
     - **Total:** 67 + 1 + 22 + 11 = **101 alumnos activos**. Deuda total: **$1.899.000**.
  2. Se incorporó en `DIA-04.md` e `INSTRUCCIONES-EJECUTOR-DIA-04.md` un **Paso previo de comprobación**: antes de la primera operación, el ejecutor debe contrastar en pantalla los contadores reales en el servidor contra los certificados. Si hubiese desvíos basales, se anotan antes de escribir; no se fuerzan datos.

### Lección 2 — Comportamiento real del cobro adelantado (T42 / DU-19)
- **El error:** El borrador anterior afirmó que el cobro adelantado de noviembre «no crea deuda previa; cobro registrado a cuenta».
- **La realidad del código:** Al inspeccionar `PagoCuotaService::obtenerOcrearDeuda` y `aplicarPagoADeudas`:
  - El sistema **sí crea** la fila en `deuda_cuotas` para el período `2026-11` con el precio del plan ($30.000), le imputa el pago completo y actualiza su estado a `PAGADA`.
  - Registra el pago en `pagos`, la imputación en `pago_deuda_cuota` y el movimiento en `movimientos_operativos`.
  - **Separación de las 3 dimensiones financieras (T42):**
    1. *Caja / Cashflow:* El dinero ingresa efectivamente en la caja del 13/10 (+$30.000).
    2. *Ingreso económico (Reportes):* Por `periodo_ingreso = max(mesCobro, periodo_cuota)`, el ingreso devenga en **noviembre 2026**; no infla octubre.
    3. *Comisión docente (Liquidaciones):* Por `periodo_comision = max(mesCobro, periodo_cuota)`, pertenece a **noviembre 2026**, y comisionará únicamente si la alumna registra al menos una asistencia presente en noviembre.
  - **No duplicación el 01/11:** En `GenerarDeudasMensualesCommand::generar`, el comando evalúa `DeudaCuota::where('alumno_id', $id)->where('periodo', '2026-11')->exists()`. Al hallar la cuota pagada, saltea al alumno (`$contSkipped++`) sin duplicar deuda.

### Lección 3 — Separar requisito decidido, comportamiento implementado y cobertura pendiente
- **Distinción de casos del catálogo:**
  - `CBZ-004` evalúa pagar el mes nuevo dejando pendiente el viejo (excepción a FIFO). No aplica a Dante Ramos, quien sigue la regla FIFO general (paga septiembre y el remanente a octubre). Se retiró `CBZ-004` de esa operación.
  - `COB-009` exige probar el intento de pago parcial *sin motivo* y verificar su rechazo. Ese comportamiento está `no_implementado` (tarea `T25` pendiente en el tablero). Escribir voluntariamente «Pago a cuenta» en observaciones no acredita `COB-009`. Se consignó como cobertura pendiente.
- **Rutas y controladores reales:**
  - El cobro operativo se ejecuta en `POST /caja/cobrar/{alumnoId}` atendido por `CajaWebController::pagar` (`web.caja.pagar`); no existe `POST /cobrar` ni atiende `CobranzaWebController`.
  - Los movimientos de caja se envían a `POST /caja/movimiento` (`CajaWebController::movimientoStore`), no a `POST /movimientos`.
  - La apertura se procesa en `POST /caja/apertura` (`CajaWebController::abrir`) y el cierre en `POST /caja/{id}/cerrar` (`CajaWebController::cerrar`).
- **Seguridad y menús:**
  - Recorrer la barra de navegación acredita **visibilidad y segregación visual en la interfaz**; no demuestra el rechazo ante peticiones HTTP directas (que constituye una comprobación de seguridad independiente).

### Lección 4 — Necesidad de una Matriz de Seguimiento Temporal
- Preparar un caso con efecto futuro en el Día 4 no lo acredita hoy.
- Se incorporó formalmente en `OBJETIVOS-PRUEBA.md` la **Matriz de Seguimiento Temporal**:
  - **Mora Benítez:** Último día en plazo (20/10) y primer día moroso (21/10).
  - **Sofía Gómez:** Verificación de no duplicación el 01/11 (06:00 hs) y liquidación de comisiones el 02/11 (octubre excluida, noviembre condicionada a asistencia).
  - **Asistencias:** Impacto en liquidaciones por hora y comisiones del 02/11.
  - **Procesos automáticos:** Advertencia de que arrancar a las 18:15 hs no demuestra las ejecuciones matutinas (06:00 cuotas, 06:15 punitorios, 08:00 resumen); requiere prueba supervisada o arnés específico.
  - **Ventana de 15 días (CBZ-017):** Se aclaró que el alta del 13/10 dista 19 días del 1/11, por lo que no acredita la regla de los 15 días de `CBZ-017`. Debe prepararse un alta a fines de octubre (ej. 24/10).

---

## 3. Estado de la entrega

- Los cuatro documentos involucrados ([DIA-04.md](DIA-04.md), [OBJETIVOS-PRUEBA.md](OBJETIVOS-PRUEBA.md), [INSTRUCCIONES-EJECUTOR-DIA-04.md](INSTRUCCIONES-EJECUTOR-DIA-04.md) y [LECCIONES-PRUEBA-HUMANA-I.md](LECCIONES-PRUEBA-HUMANA-I.md)) se encuentran plenamente alineados y corregidos.
- Las columnas de resultados de ejecución se mantienen **estrictamente vacías**.
- La propuesta del Día 4 queda **pendiente de revisión y autorización por parte de Carlos** antes de coordinar su ejecución con Codex en el chat *Prueba humana*.
