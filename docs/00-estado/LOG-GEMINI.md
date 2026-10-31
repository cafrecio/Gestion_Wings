# Wings — Bitácora activa de GEMINI

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-GEMINI.md)
· [Entradas archivadas el 06/10](../99-archivo/bitacoras/2026-10-06/LOG-GEMINI.md) · [Entradas archivadas el 19/10](../99-archivo/bitacoras/2026-10-19/LOG-GEMINI.md)
· [Entradas archivadas el 30/10](../99-archivo/bitacoras/2026-10-30/LOG-GEMINI.md)

## 2026-10-31 — LOG GEM CAB — T96: Control del botón Configurar en Caja (9 casos, manual, fichas, pruebas de mutación, suite rápida y hallazgo H1 en Cashflow con cambio retenido)

- **Objetivo:** Ejecutar el control independiente de `T96` (implementada por Claude en `c38e38fa`) siguiendo `docs/06-pruebas/PRU-04/PROMPT-GEMINI-T96.md`.
- **Acciones y resultados ([VERIFICACION-T96-GEMINI.md](../06-pruebas/PRU-04/VERIFICACION-T96-GEMINI.md)):**
  1. **Casos 1 a 9 (navegador Chrome + pedidos directos en `wings_testing_gemini`):** Comprobados los 9 casos en orden (`Configurar` y `Abrir` habilitados sin caja abierta; cambio de cuenta persiste autor/fecha y aplica a la siguiente apertura; `Configurar` inhabilitado con `title` explicativo cuando hay caja abierta de cualquier operativo; `/caja/configuracion` con selector `disabled`, aviso y sin `Guardar`; `POST` directo y apertura intercalada rechazados sin cambiar la cuenta; sin configurar solo muestra `Configurar`; operativa y profesora reciben `403`).
  2. **Hallazgo H1 (Grave) — Cashflow al peso al cambiar la cuenta con `cambio_retenido > 0`:** Probado al peso (`Caja #1` en `Cuenta A` ingresa `$30.000`, retiene `$10.000` de cambio y retira `$20.000` &rarr; cambio de mostrador a `Cuenta B` &rarr; `Caja #2` hereda `$10.000` bajo `Cuenta B`, ingresa `$5.000`, gasta `$8.000` y cierra cuadrada con `$7.000` retenidos). Tras validar ambas cajas, en Cashflow (`saldosPorCaja`, `saldoAcumuladoHastaFecha` y `/cashflow`) `Cuenta A` queda en **`$30.000,00`** (`+$10.000,00` de más) y `Cuenta B` queda en **`-$3.000,00`** (negativa, cuando el arqueo validado de `Caja #2` tiene `$7.000,00` en `Cuenta B`). Causa en `CajaService.php:42-74, 127-135, 196-206, 609-616` y `CashflowIntegracionCajaService.php:23-90`.
  3. **Manual, fichas, mutaciones y suite rápida:** `resources/manual/admin/configurar-mostrador.md` y fichas `admin-configurar-mostrador.md` / `admin-administrar-profesores.md` verificadas línea por línea contra el código; `php scripts/manual/verificar-manual.php` &rarr; **59 páginas revisadas, 0 problemas**; comprobado que las 4 pruebas nuevas de `MedioPagoCajonTest` fallan al quitar el botón o la condición de caja abierta (y código restaurado); suite rápida completa en `wings_testing_gemini` &rarr; **1178 passed, 6 skipped (8850 assertions, 262.29s)**.
- **Tablero y próximo paso:** `T96` pasada a `estado=devuelto`, `verifica=Gemini`, `tiene=Claude`. Queda en manos de Claude resolver el descuadre de `cambio_retenido` en Cashflow al cambiar la cuenta del mostrador.

## 2026-10-30 — LOG GEM CAB — Actualización de bitácora y sincronización con GitHub

- **Objetivo:** Registrar la finalización del control independiente de la Etapa C de T12/T13, actualización de la bitácora activa conforme a las reglas de AGENTS.md y GEMINI.md (archivando entradas históricas de 19/10 a 28/10 en `docs/99-archivo/bitacoras/2026-10-30/LOG-GEMINI.md` para mantener la bitácora activa dentro del límite de tamaño) y confirmación de la sincronización completa con GitHub.
- **Estado de ramas y remoto:** `origin/main` y `main` local sincronizados en `df5535ae` (incorporados los commits de Claude `18a8bc8d` con T94–T98 y de Codex `df5535ae` con la entrega de días 34–40 de PRU-04).
- **Entregables de T12 y T13:** Control final documentado en `docs/06-pruebas/PRU-04/VERIFICACION-T12-T13-ETAPA-C-GEMINI.md` (0 hallazgos en las 59 páginas, 33 pantallas con botón `?` probadas por rol, 5 vueltas del revisor subsanadas y ejecución real del caso 6 en base propia). Tablero en `a_verificar`, `hizo=Claude`, `verifica=Gemini`, `tiene=Carlos`, paso *«Etapa C controlada por Gemini: OK de Carlos»*.
- **Comprobaciones técnicas:** Suite rápida `wings_testing_gemini` y pruebas automáticas de ayuda en verde; árbol de trabajo limpio sin modificaciones espurias; cero archivos tocados en `resources/views/` ni `resources/css/`.
- **Próximo paso:** Esperar la revisión y visto bueno de Carlos para dar por cerradas T12 y T13, o nueva asignación según tablero.

## 2026-10-30 — LOG GEM CAB — T12 y T13: Control final de la Etapa C hecha por Claude (auditoría de páginas contra código, botón ? por rol en 33 pantallas, capturas, los 9 huecos para Carlos con ejecución del #6 y las 5 vueltas del revisor; 0 hallazgos)

- **Objetivo:** Ejecutar el control independiente de la Etapa C del manual del usuario (`T12` y `T13`, implementada por Claude en `d797d0b5`) siguiendo `docs/06-pruebas/PRU-04/PROMPT-GEMINI-T12-T13-CONTROL-ETAPA-C.md`.
- **Acciones y resultados concretos ([VERIFICACION-T12-T13-ETAPA-C-GEMINI.md](../06-pruebas/PRU-04/VERIFICACION-T12-T13-ETAPA-C-GEMINI.md)):**
  1. **Controles automáticos y suite:** `php scripts/manual/verificar-manual.php` &rarr; **59 páginas revisadas, 0 problemas**. `AyudaManualT12T13Test` y `AyudaBotonPorRolTest` en `wings_testing_gemini` &rarr; **23 passed (119 assertions)**.
  2. **Páginas contra el código (`REVISOR-MANUAL.md` punto 6):** Auditadas en profundidad 12 páginas contra los `if` completos de controladores/servicios/modelos y `@if` de vistas Blade (`admin/programar-clases`, `admin/administrar-grupos`, las 4 de `profesor/*`, `admin/condonar-deuda-alumno`, `admin/eliminar-alumno`, `admin/corregir-movimiento-cashflow`, `admin/administrar-tipos-caja`, `admin/configurar-parametros` y `profesor/tomar-asistencia`) y releídas las 12 restantes de la etapa. **0 diferencias**.
  3. **El botón `?` por rol en pantalla:** Probadas en `wings_testing_gemini` **33 combinaciones de rol (`PROFESOR`, `OPERATIVO`, `ADMIN`) y pantalla** con y sin parámetros. Las 33 devuelven HTTP 200 y su botón `?` responde HTTP 200 con la guía correspondiente al rol y tarea.
  4. **Capturas (`CapturasEtapaCTest.php` y PNGs):** Verificado que `CapturasEtapaCTest.php` solo revela paneles existentes u oculta secciones superiores con CSS sin agregar texto inventado, que las capturas muestran el menú lateral real de cada rol (`Laura Gómez`, `Sandra Ríos`, `Lucía Méndez`), y que se eliminó la captura fabricada `primera-carga-deshacer-paso2.png` de la Etapa B.
  5. **Los 9 «Huecos para Carlos» (`IMPLEMENTACION-T12-T13.md` §15):** Comprobados los 9 en el código con `archivo:línea`. Ejecutado en `wings_testing_gemini` el hueco #6: tanto en `GrupoWebController::update` como en `GrupoWebController::destroyPlan` lanza `QueryException` (`1451` sobre `alumno_planes_plan_id_foreign`) y `ExceptionHandler` devuelve **HTTP 500**, conservando el plan en la base.
  6. **Las 5 vueltas del revisor independiente (`revisiones/2026-10-30-etapa-c*.md`):** Comprobado que todos los hallazgos de las vueltas 1 a 5 quedaron subsanados.
- **Tablero y próximo paso:** `T12` y `T13` actualizadas a `estado=a_verificar`, `hizo=Claude`, `verifica=Gemini`, `tiene=Carlos`, paso *«Etapa C controlada por Gemini: OK de Carlos»*.

## 2026-10-30 — LOG GEM CAB — T12 y T13: Etapa B segunda vuelta (ajuste de liquidación a comisión abierta o cerrada, los 4 rechazos de primera-carga-deshacer, eliminación total de backticks, 16 fichas con if completo y caminos prohibidos probados, revisión independiente en 0)

- **Objetivo:** Subsanar los hallazgos de la devolución de Claude (`VERIFICACION-T12-T13-ETAPA-B-CLAUDE.md`) y Carlos sobre la Etapa B: reescribir `recalcular-ajustar-liquidacion.md`, documentar los 4 rechazos de `primera-carga-deshacer.md`, eliminar todas las comillas de código (backticks) del manual, quitar menciones comerciales a "Banco Galicia", retirar MF12 de huecos y completar las 16 fichas de fuentes con la columna «Condición en el código (if)» y «Qué pasó al ejecutar el camino prohibido en el sistema».
- **Resultados:** `php scripts/manual/verificar-manual.php` &rarr; **37 páginas revisadas, 0 problemas**; `AyudaManualT12T13Test.php` &rarr; **20 passed**; informe en `revisiones/2026-10-30-etapa-b-segunda-vuelta.md` con **0 Graves, 0 Medias, 0 Menores**.

## 2026-10-30 — LOG GEM CAB — T12 y T13: Etapa B (Manual del Dueño, páginas 21 a 36, botón `?` en 8 pantallas de administración, 31 capturas con hashes únicos, 15 fichas de fuentes con la regla del «Sí», galería visual y revisión independiente en 0)

- **Objetivo:** Ejecutar y entregar la Etapa B de T12 y T13 (páginas 21 a 36 para el rol Administrador / Dueño) aplicando `PROMPT-GEMINI-T12-T13-ETAPAS.md`, incorporar el botón `?` en las 8 vistas de administración autorizadas, generar 31 capturas reales con hashes únicos, confeccionar las 15 fichas de fuentes con la regla del «Sí», generar `APROBAR-T12-T13-ETAPA-B.html` y subsanar hallazgos del revisor independiente hasta 0 abiertas (`revisiones/2026-10-30-etapa-b.md`).

## 2026-10-29 — LOG GEM CAB — T12 y T13: Etapa A (19 páginas operativas, buscador en /ayuda, botón [?] en 16 pantallas, segunda vuelta de rechazos/avisos y revisión independiente en 0)

- **Objetivo:** Completar y auditar la Etapa A del manual operativo (págs. 2 a 20), buscador en `/ayuda`, botón `[?]` en 16 pantallas, 20 fichas `operativo-*.md` con las 54 condiciones `if` y tabla de rechazos/avisos probados en el sistema, y `VerificarCaminosProhibidosManualTest.php` (`18 passed`). Revisiones independientes (`2026-10-29-etapa-a*.md`) cerradas en **0 hallazgos abiertos**.
