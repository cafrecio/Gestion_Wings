# Wings — Bitácora activa de GEMINI

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-GEMINI.md)
· [Entradas archivadas el 06/10](../99-archivo/bitacoras/2026-10-06/LOG-GEMINI.md) · [Entradas archivadas el 19/10](../99-archivo/bitacoras/2026-10-19/LOG-GEMINI.md)
· [Entradas archivadas el 30/10](../99-archivo/bitacoras/2026-10-30/LOG-GEMINI.md)

## 2026-11-02 — LOG GEM CAB — Cierre de T113, T117 y T118

- **T113 (Desvalidar clase por el dueño):** Aprobada en control por Claude (`30ea9a41`), suite completa en verde (`cdebb67d`), cerrada en el tablero.
- **T117 (Configuración ya no se traba con sesión vencida):** Segunda vuelta verificada por Claude (`e36b6cc0`), cerrada en el tablero.
- **T118 (Abreviatura y longitudes con acentos):** Verificada y cerrada por Claude (`f020bd43`).
- **Estado:** Tareas integradas y desplegadas a producción (`b9394e7d`). Sin tareas pendientes en Gemini.

## 2026-11-01 — LOG GEM CAB — T117 y T118: Segunda vuelta (a verificación)

- **Objetivo:** Resolver hallazgos H1 a H5 de la verificación de Claude (`VERIFICACION-T117-T118-CLAUDE.md`) según `PROMPT — T117 y T118: segunda vuelta — para Gemini`.
- **Implementación y resultados ([IMPLEMENTACION-T117-T118-GEMINI.md](../06-pruebas/PRU-04/IMPLEMENTACION-T117-T118-GEMINI.md) «Segunda vuelta»):**
  1. **T117 (Configuración ya no se traba ante sesión vencida — H1):**
     - En `resources/js/bootstrap.js`, ante 419 o redirección a `/login`: define `window.__ignorarAvisoSalida = true`, `window.onbeforeunload = null` y registra listener en fase de captura (`capture: true`) con `event.stopImmediatePropagation()`.
     - En `resources/js/configuraciones.js:89` y `resources/js/alumnos-form.js:247`: agregada comprobación preventiva `if (window.__ignorarAvisoSalida) return;`.
     - Recompilado con `npm run build`.
     - *Navegador real Chrome CDP ([test-configuracion-segunda-vuelta.mjs](../06-pruebas/PRU-04/evidencia-t117-t118/test-configuracion-segunda-vuelta.mjs)):* Con campo modificado con teclado real y sesión vencida, clic de mouse real en Guardar navega directo a `/login` con flash y sin aviso `beforeunload` (captura `07-t117-configuracion-sesion-vencida-login.png`). Lo mismo al guardar reglas (captura `08-t117-reglas-sesion-vencida-login.png`). Con sesión viva y cambios sin guardar, sí abre el diálogo `beforeunload`.
  2. **T118 (Tres pruebas que defienden mutaciones — H4):**
     - En `tests/Feature/SesionVencidaYMayusculasT117T118Test.php`:
       - `test_movimientos_observacion_de_36_caracteres_con_acentos_se_ve_entera_sin_puntos_suspensivos`: defiende M8 (`mb_strlen` vs `strlen` en `movimientos/index.blade.php:119`). Falla con mutación, pasa con arreglo.
       - `test_tipo_caja_alta_guarda_abreviatura_con_acento_en_mayuscula`: defiende M9 (`mb_strtoupper` vs `strtoupper` en `TipoCajaWebController.php:52`). Falla con mutación, pasa con arreglo.
       - `test_tipo_caja_edicion_guarda_abreviatura_con_acento_en_mayuscula`: defiende M10 (`mb_strtoupper` vs `strtoupper` en `TipoCajaWebController.php:94`). Falla con mutación, pasa con arreglo.
     - Total del archivo: 10 pruebas pasadas, 43 aserciones.
  3. **Sembrador seguro (`evidencia-t117-t118/seed-test-gemini.php` — H3):**
     - Agregada verificación que solo permite ejecutar `migrate:fresh` si la base conectada es una descartable (`wings_testing_gemini`, `wings_testing_claude`, `wings_testing_codex`). Si se ejecuta sobre `wings_test` u otra base, aborta con código 1 emitiendo error. Comprobado en terminal.
  4. **Corrección de informe y bitácora (H2, H5):**
     - Sección 3.D corregida: cuenta desactivada llega a `/login` sin mensaje flash visible (consumido por la primera redirección interna que sigue `fetch`).
     - Nombres de pruebas corregidos con los nombres literales del archivo.
     - `TipoCajaWebController`: precisado que el cambio aplica sobre el campo `abreviatura`.
     - Separado lo ejecutado en navegador de lo leído; removida afirmación de consulta SQL del script.
- **Tablero:** T117 y T118 marcadas como `a_verificar`, `hizo=Gemini`, `tiene=Claude`. Conteo oficial actualizado a 1517 pruebas.

## 2026-11-01 — LOG GEM CAB — T113: Desvalidar una clase para el dueño (a verificación)

- **Objetivo:** Implementar la desvalidación de clases por el dueño (`ADMIN`) según `PROMPT — T113: Desvalidar una clase — para Gemini` para corregir validaciones erróneas sin perder el enlace `Editar` ni alternar a ciegas.
- **Implementación y resultados ([IMPLEMENTACION-T113-GEMINI.md](../06-pruebas/PRU-04/IMPLEMENTACION-T113-GEMINI.md)):**
  1. **Modelo `app/Models/Clase.php`:**
     - `integraLiquidacionHoraAbierta(): bool` (L180-186): verifica si la clase integra un detalle de liquidación por hora en estado `ABIERTA`.
     - `puedeDesvalidar(): bool` (L192-197): valida si está marcada para liquidación y no integra liquidación cerrada/pagada ni abierta por hora.
  2. **Controlador `app/Http/Controllers/ClaseWebController.php` (L785-844):**
     - Endpoint protegido con `abort(403)` defensivo para no administradores.
     - Bloqueo pesimista `lockForUpdate()` en transacción.
     - Detección de doble clic y pantalla vieja: si `accion === 'validar'` y ya estaba validada, o si `accion === 'desvalidar'` y ya estaba desmarcada, rechaza con *«La clase cambió mientras la mirabas. Recargá la pantalla.»*.
     - Bloqueo en liquidación cerrada/pagada: *«No se puede modificar la validación: la clase integra una liquidación cerrada.»*.
     - Bloqueo de desvalidación en liquidación por hora abierta: *«No se puede desvalidar: la clase está en una liquidación abierta. Eliminá esa liquidación, desvalidá y generala de nuevo.»*.
     - Estado booleano asignado según la intención explícita enviada por el formulario (`accion`).
  3. **Vista `resources/views/clases/show.blade.php` (L133-176):**
     - Botón `Validar` condicionado a `@if($esAdmin && !$clase->validada_para_liquidacion && $cantPresentes === 0)` con `accion="validar"`.
     - Botón `Desvalidar` condicionado a `@if($esAdmin && $clase->puedeDesvalidar())` con `accion="desvalidar"`.
     - Enlace `Editar` conservado en su bloque independiente con su condición original intacta. Sin JavaScript incrustado.
  4. **Pruebas y evidencia:**
     - Feature test: `tests/Feature/DesvalidarClaseT113Test.php` (14 passed, 87 assertions), cubriendo los 10 casos de la tabla y 4 mutaciones dirigidas.
     - Compatibilidad: `tests/Feature/ClaseSinAsistenciaYDniT34T67Test.php` (11 passed, 78 assertions).
     - Comprobación en navegador real Chrome CDP: `docs/06-pruebas/PRU-04/evidencia-t113/comprobar-navegador-t113.mjs` (7/7 passed, 8 capturas en escritorio 1366px y móvil 375px). Galería visual en `docs/06-pruebas/PRU-04/evidencia-t113/APROBAR-T113.html`.
     - Sintaxis PHP (`php -l`) limpia; compilación y limpieza de vistas Blade (`view:cache; view:clear`) limpia.
- **Tablero:** Marcada T113 como `a_verificar`, `hizo=Gemini`, `tiene=Claude`. Conteo oficial del repositorio actualizado a 1499 pruebas.

## 2026-11-01 — LOG GEM CAB — T12 y T13: Segunda vuelta del control del manual tras T94 a T109 (OK para Carlos)

- **Objetivo:** Ejecutar la segunda vuelta de control del manual tras la rectificación del camino 1 (retiros por Cashflow y faltante en turno) realizada por Claude, según `PROMPT — T12 y T13: segunda vuelta del control del manual — para Gemini`.
- **Acciones y resultados ([VERIFICACION-T12-T13-T94-T109-GEMINI.md](../06-pruebas/PRU-04/VERIFICACION-T12-T13-T94-T109-GEMINI.md) «Segunda vuelta»):**
  1. **Comprobación del camino 1 rectificado (`evidencia-t12-t13/SegundaVueltaManualGeminiTest.php` en `wings_testing_gemini`):**
     - Retiro no entra al turno operativo ni por el dueño (`Agregar`) ni por la operativa (`/caja/movimiento`), rechazando con `El subrubro seleccionado no está permitido para usuarios operativos.`
     - Retiro por Cashflow descuenta la cuenta Efectivo una sola vez ($15.000 a $12.000); esperado de turno no cambia ($10.000) y caja cierra con faltante (-$3.000). Al validar, la cuenta Efectivo no se vuelve a descontar.
     - Si Efectivo queda en negativo por retiro previo (-$4.000), al validar la caja con los cobros del turno ($10.000) el saldo se acomoda exactamente (-4.000 + 10.000 = +$6.000), verificando la literalidad del manual.
     - Aporte con `Agregar` entra solo si el subrubro está en `Ambos` (`OPERATIVO`) y `afecta_caja = true`, sube el esperado y queda a nombre del dueño (`usuario_id`).
     - 5 pasadas, 28 aserciones en 14,40 s.
  2. **Auditoría transversal y galería:**
     - Rastreadas las 59 páginas: ninguna otra página supone que un retiro entra a un turno ni que un subrubro Solo Admin entra a caja.
     - Galería `APROBAR-T12-T13-T94-T109.html` coincide palabra por palabra con las páginas corregidas.
     - Validadas las 3 capturas observadas en primera vuelta: `cobrar-cuota-paso2.png` y `primera-carga-descargar-plantilla-paso2.png` sin cambios de vistas desde `d797d0b5`, y `consultar-movimientos-paso3.png` con la columna `Registró` actualizada de T101.
  3. **Botón ? en navegador real (Chrome CDP headless):**
     - 9 comprobaciones con clics reales (`comprobar-signo-pregunta-t96-t97-t102.mjs`) sobre los tres roles: `/tipos-caja` -> `administrar-tipos-caja`, `/caja/configuracion` -> `configurar-mostrador`, `/configuraciones` -> `configurar-parametros`, `/cashflow/transferencia` -> `transferir-entre-cuentas`, `/cashflow` -> `corregir-movimiento-caja-validada`, `/niveles` -> `administrar-niveles`, `/grupos` -> `administrar-grupos`, `/caja/apertura` -> `abrir-caja`, `/clases` -> `consultar-mis-clases`.
     - 8 capturas guardadas en `docs/06-pruebas/PRU-04/evidencia-t12-t13/`.
  4. **Comandos de manual y guardianes:**
     - `verificar-manual.php` (59 páginas, 0 problemas), `AyudaManualT12T13Test` (20 passed, 111 aserciones), `AyudaBotonPorRolTest` (3 passed, 8 aserciones), `DocumentacionNoMienteTest` (1 passed, 7 aserciones).
- **Tablero:** T12 y T13 pasan a `a_verificar`, `tiene=Carlos`, paso: «Manual al día con T94 a T109, controlado por Gemini: OK de Carlos con APROBAR-T12-T13-T94-T109.html». Sin hallazgos graves ni medios.

## 2026-11-01 — LOG GEM CAB — T117 y T118: Sesión vencida en peticiones AJAX y mayúsculas con acento (a verificación)

- **Objetivo:** Implementar la expiración de sesión sin recargar pantalla (T117, ACC-006) y corregir mayúsculas y cortes que rompen acentos en castellano (T118) según `PROMPT — T117 y T118: sesión vencida y mayúsculas con acento — para Gemini`.
- **Implementación y resultados ([IMPLEMENTACION-T117-T118-GEMINI.md](../06-pruebas/PRU-04/IMPLEMENTACION-T117-T118-GEMINI.md)):**
  1. **T117 (Sesión vencida en AJAX en un solo lugar):**
     - *Servidor (`bootstrap/app.php:95-107`):* En excepción 419 fuera de `api/*`, si espera JSON almacena flash `'error' => 'Tu sesión expiró. Iniciá sesión nuevamente.'` y responde JSON 419 con `{ message, redirect: route('login') }`. Si es formulario común mantiene redirect 302 con flash. `api/*` responde JSON estándar sin redirect.
     - *Cliente (`resources/js/bootstrap.js` + `npm run build`):* Interceptor global en `window.fetch` que ante 419 redirige automáticamente con `window.location.assign(destino)` y retorna promesa pendiente para silenciar toasts de error en la página emisora. Cubre todas las peticiones fetch presentes y futuras (`cobrar.js`, `clases-show.js`, `ds-app.js`, etc.).
     - *Reingreso por rol verificado:* `ADMIN` -> `/admin/dashboard`, `OPERATIVO` -> `/operativo`, `PROFESOR` -> `/clases`. Comprobación de integridad: peticiones fallidas no persisten pagos ni asistencias.
     - *Navegador real:* ejecutados Cobrar, Guardar lista a 375px, Cancelar clase y reingresos; el resto cubierto por arquitectura compartida de `bootstrap.js`.
  2. **T118 (Mayúsculas y recortes con mbstring):**
     - `resources/views/revision-cobranza/index.blade.php:116`: `mb_strtoupper($resolucionLabel, 'UTF-8')` -> `REACTIVADO AUTOMÁTICAMENTE`.
     - `resources/views/caja/detalle.blade.php:136`: `mb_strtoupper(mb_substr(..., 0, 3, 'UTF-8'), 'UTF-8')` -> `FÚT`.
     - `resources/views/movimientos/index.blade.php:119-121`: `mb_strlen` y `mb_substr(..., 0, 36, 'UTF-8')` recortan a 36 caracteres sin partir caracteres multibyte.
     - `app/Http/Controllers/TipoCajaWebController.php:52, 94`: `mb_strtoupper(..., 'UTF-8')` sobre la abreviatura.
  3. **Pruebas y evidencia:**
     - Feature test: `tests/Feature/SesionVencidaYMayusculasT117T118Test.php` (7 passed en primera vuelta, nombres literales del archivo).
     - Chrome CDP headless: `docs/06-pruebas/PRU-04/evidencia-t117-t118/comprobar-navegador-t117-t118.mjs` (8/8 passed, 6 capturas guardadas).
     - Suite rápida (`--exclude-group=lento`): 1351 aprobadas, 6 omitidas, 0 fallidas (9885 aserciones, 360.62 s, salida 0 en `wings_testing_gemini`).
     - Sin errores de sintaxis (`php -l`), vistas compilan (`php artisan view:cache; php artisan view:clear`), `TableroAlDiaTest` pasa (6 passed), `DocumentacionNoMienteTest` pasa (1 passed).
- **Tablero:** T117 y T118 marcadas como `a_verificar`, `hizo=Gemini`, `tiene=Claude`.

## 2026-11-01 — LOG GEM CAB — T12 y T13: Control del manual tras T94 a T109 (Devuelto a Claude por Hallazgo Grave en Camino 1)

- **Objetivo:** Ejecutar el control independiente del manual de usuario tras las tareas T94 a T109 según `PROMPT — T12 y T13: control del manual después de T94 a T109 — para Gemini`.
- **Acciones y resultados ([VERIFICACION-T12-T13-T94-T109-GEMINI.md](../06-pruebas/PRU-04/VERIFICACION-T12-T13-T94-T109-GEMINI.md)):**
  1. **Auditoría de los 4 caminos ejecutados (`tests/Feature/VerificacionManualT94T109GeminiTest.php` en `wings_testing_gemini`):**
     - *Camino 1 (Hallazgo Grave):* El manual en `admin/dinero-del-dueno-retiros-aportes.md:30-35` instruye al dueño a registrar retiros físicos de efectivo del turno en curso usando **Agregar** con el subrubro **Retiro de dueños**. En Wings, `CajaWebController::editarStore` delega en `CajaService::validarSubrubroManual`, el cual rechaza la transacción arrojando excepción: `El subrubro seleccionado no está permitido para usuarios operativos.` debido a que valida `subrubro->permitido_para !== 'OPERATIVO'` sin contemplar el rol `ADMIN`. El flujo documentado falla y no es permitido por Wings.
     - *Camino 2 (Comprobado):* Botón **Anular** en `alumnos/show.blade.php` solo aparece para `ADMIN`, en pagos no anulados y fuera de caja (`sinCaja()`). Restaura el mes previo a `PENDIENTE` y deshace/elimina por completo el registro de cuota si fue cobro adelantado.
     - *Camino 3 (Comprobado):* En `/caja/cobrar/{alumno}`, cuotas tienen casillas de selección; matrícula de inscripción y recargos no tienen casilla y su imputación es automática con el importe entregado respetando el orden de cascada.
     - *Camino 4 (Comprobado):* Ajustes de saldo (`Ajuste de saldo: entrada`, `APORTE`) impactan totales y saldo en Cashflow sin alterar el resultado operativo en Reportes mensuales.
  2. **Auditoría de capturas y enlaces:**
     - 7 capturas nuevas inspeccionadas: auténticas, generadas con Chrome CDP sobre copia descartable real, respetando roles, sin texto DOM inyectado.
     - Capturas anteriores identificadas que aún reflejan interfaces previas a T94-T109 (`cobrar-cuota-paso2.png`, `dinero-del-dueno-retiros-aportes-paso2.png`, `consultar-movimientos-paso3.png`, `primera-carga-descargar-plantilla-paso2.png`).
     - Botón `?` y redirección `/caja/configuracion` -> `/caja` verificados con `AyudaManualT12T13Test` y `AyudaBotonPorRolTest` (23 passed, 119 assertions).
     - Clasificadas las 45 citas a mano de `citas-a-mano-t94-t109.txt` (ninguna oculta errores en el manual). Galería `APROBAR-T12-T13-T94-T109.html` verificada.
- **Tablero y próximo paso:** T12 y T13 pasadas a `estado=devuelto`, `tiene=Claude` para alinear backend y manual en el registro de retiros de efectivo del dueño.

## 2026-11-01 — LOG GEM CAB — T107 y T108: Segunda vuelta de control (concurrencia real, Excel real y navegador Chrome CDP)

- **Objetivo:** Ejecutar las tres comprobaciones prácticas pendientes de T107 y T108 en `wings_testing_gemini` solicitadas en el prompt de segunda vuelta.
- **Acciones y resultados ([VERIFICACION-T107-T108-GEMINI.md](../06-pruebas/PRU-04/VERIFICACION-T107-T108-GEMINI.md) §8):**
  1. **C1 (Concurrencia real con 2 procesos PHP y conexiones PDO independientes):**
     - Desarrollada `tests/Feature/CarreraCierreCobroT107Test.php` con subproceso CLI (`proc_open`), pipes y locks reales en `wings_testing_gemini`.
     - *Orden A (Cierre bloquea primero):* Cobro concurrente detecta caja cerrada y es rechazado sin cobro espurio.
     - *Orden B (Cobro bloquea primero):* Cobro incrementa saldo de $30.000 a $50.000; cierre con pantalla vieja es rechazado (`Cambió el efectivo esperado: ahora es $50.000,00`), caja queda abierta sin faltante falso. Base de datos limpia tras la corrida.
     - 2 pasadas, 14 aserciones en 62,90 s.
  2. **Caso 14 (Primera carga por Excel real):**
     - Desarrollada `tests/Feature/PrimeraCargaExcelRealT108Test.php` generando archivo `.xlsx` real con `PhpSpreadsheet` y subiéndolo por HTTP multipart (`revisar` y `cargar`).
     - Filas con «Inscripción: Sí» cobran matrícula ($5.000), «Inscripción: No» no cobran ($0), y dos filas con el mismo DNI en deportes distintos cobran exactamente una sola inscripción.
     - 4 alumnos creados, exactamente 2 cargos `INSCRIPCION` en `cargos_alumno` y 2 DNI únicos en `inscripcion_personas`.
     - 1 pasada, 16 aserciones en 15,58 s.
  3. **Casos 1 y 9 (Comprobación por pantalla en navegador Chrome CDP real):**
     - Ejecutado script `ejecutar-navegador.mjs` contra servidor local `artisan serve` (puerto 8112).
     - *Caso 1:* Operativa en `/caja/1/cerrar` ve $11.600; entra cobro de $44.000; intenta cerrar con $11.600 y recibe alerta «No se cerró la caja: Cambió el efectivo esperado: ahora es $55,600.00...». Pantalla se actualiza en el lugar a $55.600 (`esperado_visto = 55600.00`), cuenta $55.600 y cierra con éxito (`CERRADA`, diferencia $0,00). Capturas y volcado de texto guardados.
     - *Caso 9:* En `/alumnos/create` se escribe DNI existente `45000001` (Thiago Silva) y la pantalla despliega dinámicamente: «Esta persona ya es del club: no paga inscripción.». Se completa segunda ficha para Patín y se guarda sin duplicar matrícula. Capturas y volcado guardados.
- **Tablero y estado:** T107 y T108 cerradas con `verifica=Gemini`, `tiene=nadie`.

## 2026-11-01 — LOG GEM CAB — T107 y T108: Cierre de caja con pantalla vieja e inscripción en segunda ficha (cerradas sin hallazgos)

- **Objetivo:** Ejecutar el control independiente de T107 y T108 (implementadas por Claude en commit `a85d5c39`) según `docs/06-pruebas/PRU-04/PROMPT-GEMINI-T107-T108-CONTROL.md`.
- **Acciones y resultados ([VERIFICACION-T107-T108-GEMINI.md](../06-pruebas/PRU-04/VERIFICACION-T107-T108-GEMINI.md)):**
  1. **T107 (Cierre con pantalla vieja — 8 casos comprobados):**
     - Cobros o cancelaciones concurrentes mientras la pantalla de cierre está abierta son detectados por `CajaService::cerrarCajaOperativa` contra `esperado_visto`, bloqueando el cierre e informando el nuevo esperado sin crear faltantes falsos.
     - Cierres con esperado al día cierran con éxito (`diferencia = 0.00`). Faltantes reales se registran. Doble clic es rechazado.
     - En caja rechazada, el efectivo contado previo es inmutable (`La corrección no puede cambiar el efectivo que ya se contó y entregó`) y el nuevo cierre exige actualizar `esperado_visto`.
     - Validar la caja en Cashflow asienta los movimientos y preserva el arqueo al centavo.
  2. **T108 (Inscripción en segunda ficha — 7 casos comprobados):**
     - Alumno que ya tiene ficha en el club no vuelve a pagar matrícula al registrarse en un segundo deporte (`yaEsDelClub`), incluso si provino de la primera carga sin inscripción previa (caso Thiago).
     - Preview avisa «Esta persona ya es del club: no paga inscripción.»; persona nueva paga; DNI con formato o ficha inactiva no duplican; si se elimina la única ficha, vuelve a pagar.
     - Primera carga por Excel respeta la declaración por fila sin duplicar por DNI repetido. Ficha, cobranza y reportes reflejan solo la cuota mensual.
  3. **Mutaciones y verificación técnica:**
     - 7 mutaciones ejecutadas en worktree aislado (`mutaciones.py`): 100% detectadas y abatidas.
     - 18 pruebas directas (`CierrePantallaViejaT107Test`: 10, `InscripcionSegundaFichaT108Test`: 8) y 16 de verificación integral en `VerificacionT107T108GeminiTest.php` pasadas (87 aserciones).
     - Manuales (`cerrar-caja.md` y `alta-alumno.md`) y contratos V5 verificados condición por condición; `verificar-manual.php` en 0 problemas.
- **Tablero y próximo paso:** T107 y T108 pasadas a `estado=cerrado`, `hizo=Claude`, `verifica=Gemini`, `tiene=nadie`.

## 2026-11-01 — LOG GEM CAB — T109: Control de filtros inválidos y páginas no disponibles en recibos (cerrada sin hallazgos)

- **Objetivo:** Ejecutar el control independiente de T109 (implementado por Claude en commit `88db2b24`) según la Etapa 3 de `docs/06-pruebas/PRU-04/PROMPT-GEMINI-T101-T105-CONTROL.md`.
- **Acciones y resultados ([VERIFICACION-T109-GEMINI.md](../06-pruebas/PRU-04/VERIFICACION-T109-GEMINI.md)):**
  1. **8 casos del prompt verificados y aprobados:**
     - Caso 1: En Historial, `desde` y `hasta` con texto, fechas imposibles e inyecciones abren normalmente (HTTP 200), ignoran el filtro y limpian los campos del formulario.
     - Caso 2: Parámetros tipo array (`search[]`, `estado[]`, etc.) en 14 pantallas y listados abren sin errores 500.
     - Caso 3: En Caja del dueño, valores inválidos en `mes` cargan de manera segura el mes actual (`now()->format('Y-m')`).
     - Caso 4: Filtros válidos (fechas, búsquedas de alumnos y meses) preservan su funcionalidad completa.
     - Caso 5: Recibo inexistente (numérico o texto) muestra la pantalla estándar «Página no disponible» (HTTP 404 web), sin JSON técnico.
     - Caso 6: La profesora recibe HTTP 403 tanto para recibos existentes como inexistentes, garantizando que no se filtre información sobre cobros.
     - Caso 7: Liquidación inexistente o sin pagar (`estado_pago = PENDIENTE`) muestra «Página no disponible» (HTTP 404 web) en vez de error JSON.
     - Caso 8: Recibos existentes se visualizan con `?inline=1` (`Content-Disposition: inline`) y se descargan sin parámetro (`Content-Disposition: attachment`), ambos con PDF íntegro.
  2. **Pruebas técnicas y suite:**
     - 12 pruebas de `FiltrosInvalidosT109Test.php` pasadas.
     - 8 pruebas de verificación exhaustiva en `docs/06-pruebas/PRU-04/evidencia-t109/VerificacionT109GeminiTest.php` pasadas (162 aserciones).
     - Prueba masiva de seguridad `test_dias_41_47_filtros_con_basura_no_dan_error_del_servidor` en `SeguridadDiasPruebaTest.php`: 3.876 peticiones HTTP sobre 19 pantallas con **0 errores 500**.
     - Relevamiento de vistas y JS: ningún formulario ni enlace usa arrays para estos filtros.
- **Tablero y próximo paso:** T109 pasada a `estado=cerrado`, `hizo=Claude`, `verifica=Gemini`, `tiene=nadie`. Etapa 3 completada. Siguiente control asignado: T107 y T108 según `PROMPT-GEMINI-T107-T108-CONTROL.md`.

## 2026-11-01 — LOG GEM CAB — T105: Verificación de recorte de fuentes en recibos y medición de cobros (cerrada sin hallazgos)

- **Objetivo:** Ejecutar el control independiente de T105 (activación de `enable_font_subsetting` en `config/dompdf.php`) según `docs/06-pruebas/PRU-04/PROMPT-GEMINI-T101-T105-CONTROL.md`.
- **Acciones y resultados ([VERIFICACION-T105-GEMINI.md](../06-pruebas/PRU-04/VERIFICACION-T105-GEMINI.md)):**
  1. **Ahorro de peso en 4 tipos de recibos comprobado:**
     - Cuota normal: 1.658,3 KB → 215,2 KB (-87,0%).
     - Cuota anulada: 1.660,5 KB → 218,5 KB (-86,8%).
     - Liquidación por hora: 1.664,5 KB → 224,1 KB (-86,5%).
     - Liquidación por comisión: 1.664,3 KB → 224,6 KB (-86,5%).
  2. **Integridad tipográfica y de contenido (100% idénticos):**
     - Descompresión e inspección de flujos `/Contents`: flujos de páginas 100% idénticos byte a byte entre la versión original y la recortada.
     - Presencia perfecta de caracteres acentuados (á, é, í, ó, ú), eñes (ñ, Ñ) y caracteres especiales ($, –, ·, /, :, ,, ., %). El recorte solo descarta glifos TrueType no utilizados en `/FontFile2`.
  3. **Discrepancia de tiempos resuelta (72 ms vs ~4 s):**
     - La generación del PDF es *perezosa* (lazy): `PagoCuotaService::__construct` define `?ReciboService $reciboService = null`. Al resolverse por inyección de dependencias de Laravel sin binding contextual, recibe `null` y omite la compilación del PDF durante `registrarPagoCuotaOperativo`. El cobro tarda ~72–78 ms de pura base de datos.
     - La generación real del PDF ocurre al consultar la URL `/recibos/cuota/{id}`: tarda ~4.200 ms la primera vez (compilación DomPDF) y 7,9 ms en descargas posteriores desde caché en disco.
  4. **Pruebas y suite:** 2 pruebas de `ReciboPesoT105Test` aprobadas; suite rápida en `wings_testing_gemini` aprobada (1335 pasadas, 6 omitidas en 451 s).
- **Tablero y próximo paso:** T105 cerrada (`hizo=Claude`, `verifica=Gemini`, `tiene=nadie`). Sigue Etapa 3 (T109).

## 2026-11-01 — LOG GEM CAB — T101: Control de gasto del dueño en turno de operativa (cerrada sin hallazgos)

- **Objetivo:** Ejecutar el control independiente de T101 (implementada por Claude en commit `0805d759`) según `docs/06-pruebas/PRU-04/PROMPT-GEMINI-T101-T105-CONTROL.md`.
- **Acciones y resultados ([VERIFICACION-T101-GEMINI.md](../06-pruebas/PRU-04/VERIFICACION-T101-GEMINI.md)):**
  1. **Comprobación de 5 casos en base `wings_testing_gemini`:**
     - Caso 1: Dueño agrega gasto ($3.500) en el turno de la operativa. En `/movimientos`, la operativa ve el gasto a nombre del dueño en *Registró*, y el efectivo esperado de la caja disminuye al peso.
     - Caso 2: Operativa agrega en su turno por Agregar y por `/caja/movimiento`; ambos quedan y se muestran a su nombre.
     - Caso 3: Otra operativa y profesora reciben HTTP 403. Peticiones directas con inyección de `usuario_id` son neutralizadas, registrando siempre el ID del usuario autenticado.
     - Caso 4: Al validar la caja, en Cashflow los movimientos operativos generan asientos con su autor real (`CashflowIntegracionCajaService:81`), la pantalla de Cashflow muestra al autor respectivo en *Registró* y el saldo acumulado cierra al centavo.
     - Caso 5: La edición de movimientos preserva el autor original y audita al editor en `movimientos_operativos_correcciones`. La cancelación es rechazada por no ser cobro de alumno. Eliminación audita en correcciones y resumen suma egresos al peso.
  2. **Suite técnica y evidencia:** 8 pruebas de `GastoDuenoEnTurnoT101Test.php` pasadas; 5 pruebas de verificación integral en `docs/06-pruebas/PRU-04/evidencia-control-t101/VerificacionT101GeminiTest.php` pasadas (61 aserciones).
- **Tablero y próximo paso:** T101 pasada a `estado=cerrado`, `hizo=Claude`, `verifica=Gemini`, `tiene=nadie`. Sigue Etapa 2 (T105).

## 2026-11-01 — LOG GEM CAB — T94 segunda vuelta: Corrección de hallazgos del control (H1 a H5), concurrencia real comprobada y suite al día

- **Objetivo:** Corregir los 5 hallazgos reportados por Claude en `VERIFICACION-T94-CLAUDE.md` sobre T94 (`sacar una frecuencia de un grupo`), incorporando las pruebas de control de `docs/06-pruebas/PRU-04/evidencia-t94-control/` y actualizando el manual y la documentación técnica.
- **Acciones y resultados ([IMPLEMENTACION-T94.md](../06-pruebas/PRU-04/IMPLEMENTACION-T94.md) §8):**
  1. **H1 (Carrera concurrente):** Se serializó el bloqueo de planes removidos en orden estable (`orderBy('id')->lockForUpdate()`) y se implementó lectura actual (`lockForUpdate()->count()`) sobre `AlumnoPlan` dentro de la transacción de `update`. Esto resuelve la anomalía de repeatable read en InnoDB, detectando confirmaciones concurrentes de alumnos y rechazando con `«Nx por semana tiene N alumno(s): cambiales el plan antes.»`. Verificado con `tests/Feature/ControlT94CarreraTest.php` (3 pruebas reales con conexiones simultáneas vía `proc_open`, 52s).
  2. **H2 (Formulario viejo con ID desactivado):** Se valida que todo ID enviado en `planes` exista y esté activo (`where('activo', true)`). Si viene un ID desactivado, rechaza con `«Las frecuencias del grupo cambiaron. Volvé a cargar el formulario.»`.
  3. **H3 (Quitar y volver a agregar misma frecuencia):** Se calcula si la frecuencia quedó efectivamente desactivada en el resultado final (`$quedoAlgunaDesactivada`). Si la frecuencia se volvió a cargar como nueva fila en el mismo formulario, permanece activa y avisa `«Grupo actualizado correctamente.»`.
  4. **H4 (Retiro de consolidación):** Se eliminó la transferencia de referencias entre filas. Cambiar una fila existente a la frecuencia de una desactivada avisa `«Ya hay una Nx desactivada. Agregala con Nuevo en vez de cambiar esta fila.»`. Si ya existieran registros duplicados en la base, desactivar avisa `«Ya hay una Nx desactivada en el grupo.»` sin error 500 y sin modificar registros.
  5. **H5 (Intercambio de frecuencias):** El intercambio de frecuencias entre filas activas existentes en el mismo envío se rechaza con `«Ya hay una fila con esa frecuencia. Cambialas de a una.»`.
  6. **Manual y verificación:** `resources/manual/admin/administrar-grupos.md` y su ficha de fuentes actualizadas con los 3 nuevos avisos de validación; `php scripts/manual/verificar-manual.php` &rarr; 59 páginas revisadas, 0 problemas.
  7. **Pruebas y suite:** 36 pruebas directas de T94 en verde (`SacarFrecuenciaGrupoT94Test`: 25, `ControlT94ClaudeTest`: 8, `ControlT94CarreraTest`: 3 lentas). Suite rápida en `wings_testing_gemini`: 1305 pasadas, 6 omitidas. Total métodos de prueba del repo actualizado a 1437 en `ESTADO-ACTUAL.md`, `CHECKLIST-CARLOS.md` y `PLAN-PRODUCCION.md`.
- **Tablero y próximo paso:** Pasada a `estado=a_verificar`, `hizo=Gemini`, `tiene=Claude`, `verifica=Claude`.

## 2026-11-01 — LOG GEM CAB — T94: Sacar una frecuencia de un grupo (nunca usada se borra; usada antes se desactiva; con alumnos hoy se rechaza; reactivar si se vuelve a agregar)

- **Objetivo:** Implementar la decisión de Carlos para sacar frecuencias de un grupo (`T94`) según `docs/06-pruebas/PRU-04/PROMPT-GEMINI-T94.md`.
- **Acciones y resultados ([IMPLEMENTACION-T94.md](../06-pruebas/PRU-04/IMPLEMENTACION-T94.md)):**
  1. **Controlador y vistas (`GrupoWebController.php`):**
     - `show` y `edit` filtran `'planes' => fn($q) => $q->where('activo', true)->orderBy('clases_por_semana')`, logrando que la ficha y el formulario muestren únicamente frecuencias activas sin tocar ninguna vista Blade (diseño 100% protegido).
     - `destroyPlan`: si nunca se usó, se borra físicamente con `«Plan eliminado.»`; si se usó antes y no tiene alumnos hoy, se desactiva (`activo = false`) con `«Frecuencia desactivada: ya no se ofrece. Lo anterior queda como está.»` consolidando posibles filas previas para evitar colisiones del unique `unique_grupo_clases_activo`; si tiene alumnos hoy (`activo = true`), rechaza con `«Nx por semana tiene N alumno(s): cambiales el plan antes.»`; si es la última activa, rechaza con `«No se puede eliminar: el grupo quedaría sin frecuencias y no se podrían cargar alumnos.»`.
     - `storePlan`: si ya existe activa, avisa; si existe inactiva, la reactiva con el nuevo precio sin duplicar; si no existe, la crea. Redirige con `«Plan agregado.»`.
     - `update`: validación con `distinct` en frecuencias (`«Hay dos filas con la misma frecuencia. Dejá una sola.»`); si alguna frecuencia removida tiene alumnos hoy, aborta la transacción sin guardar nada (`«Nx por semana tiene N alumno(s): cambiales el plan antes.»`); frecuencias removidas nunca usadas se borran y usadas antes se desactivan; frecuencias enviadas reactivan filas inactivas con el nuevo precio si ya existían; avisa `«Frecuencia desactivada...»` si hubo desactivaciones o `«Grupo actualizado correctamente.»`.
  2. **Bloqueo a pedidos directos:**
     - `AlumnoWebController.php` (`store`, `update`, `previsualizarCuotaAlta`) y `CajaWebController.php` (`pagar` con `nuevo_plan_id`) exigen `activo = true` en la regla de validación de `plan_id`.
     - `CambioPlanService::cambiar` bloquea el plan `FOR UPDATE` y lanza `ValidationException` si `!$nuevo->activo`.
  3. **Pruebas automáticas (`tests/Feature/SacarFrecuenciaGrupoT94Test.php`):**
     - 22 pruebas cubriendo cada rama y caso borde: nunca usado, usado sin alumnos, con alumnos activos, plural, alumno inactivo en club con plan activo, cambio de plan futuro, última frecuencia activa, vaciado de frecuencias, reactivación vía store/update, colisiones unique, formulario con frecuencias repetidas, filtrado en show/edit, rechazo en alta/edición/cambio directo, flujo e2e al peso con cobro de deuda vieja de plan desactivado y posterior reactivación con nuevo alumno, concurrencia de asignación vs eliminación.
     - Pruebas de mutación ejecutadas (quitar última activa, quitar alumnos hoy, quitar plan inactivo en servicio): todas fallaron (`Exit 1`) y el código quedó restaurado.
  4. **Documentos actualizados:**
     - `resources/manual/admin/administrar-grupos.md` reescrita la sección de sacar frecuencias explicando las 4 ramas.
     - `docs/06-pruebas/PRU-04/evidencia-t12-t13/fuentes/admin-administrar-grupos.md` actualizada con líneas y textos literales exactos.
     - `docs/06-pruebas/PRU-04/IMPLEMENTACION-T12-T13.md` resueltos huecos 3 y 6 en §15.
     - `docs/02-contratos/Wings-contrato-alumno-grupo-deporte-deuda-v5.md` incorporadas reglas de T94 en §2.c.
     - Propuesta formal para `GRU-004` en `IMPLEMENTACION-T94.md` para Claude.
- **Tablero y próximo paso:** Pasada a `estado=a_verificar`, `hizo=Gemini`, `tiene=Claude`, `verifica=Claude`.

## 2026-11-01 — LOG GEM CAB — T96 segunda vuelta: Control de saldo inicial fijo de Efectivo, subrubros de ajuste, manual, mutaciones y suite rápida (cerrada)

- **Objetivo:** Ejecutar el control independiente de la segunda vuelta de `T96` (implementada por Claude en `4611b4c5` tras la decisión de Carlos del 01/11 que reemplaza `DU-59`) siguiendo `docs/06-pruebas/PRU-04/PROMPT-GEMINI-T96-SEGUNDA-VUELTA.md`.
- **Acciones y resultados ([VERIFICACION-T96-GEMINI-2.md](../06-pruebas/PRU-04/VERIFICACION-T96-GEMINI-2.md)):**
  1. **Fe de erratas del informe anterior:** Rectificada por escrito en §0 la cita errónea de `VERIFICACION-T96-GEMINI.md` §3.1 a un método inexistente `tiposElegiblesParaMostrador` en `CajaWebController.php:190-192` (en `c38e38fa`, `CajaWebController.php:44` listaba todas las cuentas activas).
  2. **Casos 1 a 15 (navegador Chrome + pedidos HTTP directos en `wings_testing_gemini`):** Comprobados los 15 casos contra las capturas aprobadas en `APROBAR-T96-PROPUESTA.html` y `evidencia-t96/final/` (sin configurar ofrece solo `Configurar`; pantalla con cuenta `Efectivo` fija y saldo vacío obligatorio; guardado de `2,310,000.50` y `0`; rechazo de vacío/negativo/3 decimales/letras; preservación del saldo cuando `Efectivo` ya tiene movimientos; redirección y bloqueo de `POST` directo una vez configurado; bloqueo de renombrar/desactivar/editar saldo en `Tipos de Caja`; permisos de operativa y profesora; ajustes de entrada/salida en Cashflow moviendo saldo y Disponible sin tocar resultado del club; bloqueo a operativa). Documentada en `O1` (Baja) que antes de configurar el mostrador, `Tipos de Caja → Editar Efectivo` permite editar `saldo_inicial` sin configurar `caja_mostrador`.
  3. **Controles especiales de borde:** Comprobada la imposibilidad de reproducir `H1` por 5 caminos; comportamiento sobre base con otra cuenta configurada e idempotencia de `ClasificacionSubrubros::aplicarAjustes()`; bloqueo `FOR UPDATE` real con 2 conexiones simultáneas a MariaDB (`configurarEfectivo` × 2 y `configurarEfectivo` + `abrirCajaOperativa`); barrido de 20 pantallas de `OPERATIVO` y `PROFESOR` con 0 filtraciones del saldo de la cuenta.
  4. **Manual, fichas, mutaciones y suite rápida:** `php scripts/manual/verificar-manual.php` &rarr; **59 páginas revisadas, 0 problemas**; manuales y fichas verificados línea por línea; las 9 mutaciones sobre `MedioPagoCajonTest` fallaron (`Exit 1`) y el código quedó restaurado; `AvisoAdminResumenDiarioTest` (**3 passed**) y suite rápida en `wings_testing_gemini` &rarr; **1192 passed, 6 skipped, 0 failed (8927 assertions, 301.46s)**.
- **Tablero y próximo paso:** `T96` pasada a `estado=cerrado`, `hizo=Claude`, `verifica=Gemini`, `tiene=nadie`.

## 2026-10-31 — LOG GEM CAB — T96: Control del botón Configurar en Caja (9 casos, manual, fichas, pruebas de mutación, suite rápida y hallazgo H1 en Cashflow con cambio retenido)

- **Objetivo:** Ejecutar el control independiente de `T96` (implementada por Claude en `c38e38fa`) siguiendo `docs/06-pruebas/PRU-04/PROMPT-GEMINI-T96.md`.
- **Acciones y resultados ([VERIFICACION-T96-GEMINI.md](../06-pruebas/PRU-04/VERIFICACION-T96-GEMINI.md)):**
  1. **Casos 1 a 9 (navegador Chrome + pedidos directos en `wings_testing_gemini`):** Comprobados los 9 casos en orden (`Configurar` y `Abrir` habilitados sin caja abierta; cambio de cuenta persiste autor/fecha y aplica a la siguiente apertura; `Configurar` inhabilitado con `title` explicativo cuando hay caja abierta de cualquier operativo; `/caja/configuracion` con selector `disabled`, aviso y sin `Guardar`; `POST` directo y apertura intercalada rechazados sin cambiar la cuenta; sin configurar solo muestra `Configurar`; operativa y profesora reciben `403`).
  2. **Hallazgo H1 (Grave) — Cashflow al peso al cambiar la cuenta con `cambio_retenido > 0`:** Probado al peso (`Caja #1` en `Cuenta A` ingresa `$30.000`, retiene `$10.000` de cambio y retira `$20.000` &rarr; cambio de mostrador a `Cuenta B` &rarr; `Caja #2` hereda `$10.000` bajo `Cuenta B`, ingresa `$5.000`, gasta `$8.000` y cierra cuadrada con `$7.000` retenidos). Tras validar ambas cajas, en Cashflow (`saldosPorCaja`, `saldoAcumuladoHastaFecha` y `/cashflow`) `Cuenta A` queda en **`$30.000,00`** (`+$10.000,00` de más) y `Cuenta B` queda en **`-$3.000,00`** (negativa, cuando el arqueo validado de `Caja #2` tiene `$7.000,00` en `Cuenta B`). Causa en `CajaService.php:42-74, 127-135, 196-206, 609-616` y `CashflowIntegracionCajaService.php:23-90`.
  3. **Manual, fichas, mutaciones y suite rápida:** `resources/manual/admin/configurar-mostrador.md` y fichas `admin-configurar-mostrador.md` / `admin-administrar-profesores.md` verificadas línea por línea contra el código; `php scripts/manual/verificar-manual.php` &rarr; **59 páginas revisadas, 0 problemas**; comprobado que las 4 pruebas nuevas de `MedioPagoCajonTest` fallan al quitar el botón o la condición de caja abierta (y código restaurado); suite rápida completa en `wings_testing_gemini` &rarr; **1178 passed, 6 skipped (8850 assertions, 262.29s)**.
- **Tablero y próximo paso:** `T96` pasada a `estado=devuelto`, `verifica=Gemini`, `tiene=Claude`. Queda en manos de Claude resolver el descuadre de `cambio_retenido` en Cashflow al cambiar la cuenta del mostrador.
