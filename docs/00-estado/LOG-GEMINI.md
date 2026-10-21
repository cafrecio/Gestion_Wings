# Wings — Bitácora activa de GEMINI

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-GEMINI.md)
· [Entradas archivadas el 06/10](../99-archivo/bitacoras/2026-10-06/LOG-GEMINI.md) · [Entradas archivadas el 19/10](../99-archivo/bitacoras/2026-10-19/LOG-GEMINI.md)

## 2026-10-21 — LOG GEM CAB — T50 Tercera pasada: resolución de observaciones complementarias de Codex

- **Objetivo y resultado:** Resolución de las observaciones complementarias informadas por Codex CAB en `docs/06-pruebas/PRU-04/VERIFICACION-T50-SEGUNDA-CODEX.md` para la tarea T50 (DU-06, ALU-007). Resultado: **COMPLETADO Y LISTO PARA VERIFICACIÓN FINAL**, con 13 pruebas en `FechaIngresoFuturaT50Test` pasando al 100% y corte documental actualizado a 1029 pruebas.
- **Cambios realizados:**
  - **Mensajes en castellano en previsualizaciones malformadas:** En `AlumnoWebController::cuotaAltaPreview` e `inscripcionPreview`, se pasó `$this->validationMessages()` a `$request->validate()`. Ante fechas malformadas (`no-es-fecha`), responden 422 con *«La fecha de alta no es una fecha válida.»*; ante fechas vacías/nulas, *«La fecha de alta es obligatoria.»*.
  - **Guardia estricta en auxiliar:** En `docs/06-pruebas/PRU-04/evidencia-t50/segunda-vuelta/preparar-base.php`, se agregó validación de entorno (`app()->environment('testing', 'local')`) y lista blanca de bases de datos de test (`wings_testing`, `wings_testing_gemini`, `wings_testing_claude`, `wings_testing_codex`), abortando con error si se ejecuta en otra base.
- **Integridad visual:** Sin cambios en Blade ni CSS (AGENTS.md §1). `git diff --stat -- resources/views resources/css` vacío. Vistas compiladas y limpiadas sin errores.
- **Pruebas en `wings_testing_gemini`:** 13 pruebas en `FechaIngresoFuturaT50Test` (71 aserciones, todas verdes); `DocumentacionNoMienteTest` verde (corte 1029).
- **Próximo paso:** Verificación final por Codex.

## 2026-10-21 — LOG GEM CAB — Preparación para test manual como humano

- **Objetivo y plan:** Jornada de test manual como humano. Codex ejecutará las pruebas en el sistema y con Gemini se definirán y estructurarán los casos de prueba detallados.
- **Estado actual:** Bitácora actualizada y sincronizada. Listo para armar los casos junto a Carlos.

## 2026-10-21 — LOG GEM CAB — T50 Segunda vuelta: corrección de H2 y H3; decisión de Carlos sobre H1

- **Objetivo y resultado:** Corrección de los hallazgos H2 y H3 señalados en la verificación independiente de Codex CAB (`docs/06-pruebas/PRU-04/VERIFICACION-T50-CODEX.md`) para la tarea T50 (DU-06, caso ALU-007). Resultado: **CORREGIDO Y LISTO PARA VERIFICACIÓN**, con 12 pruebas en `FechaIngresoFuturaT50Test` (todas verdes), comprobación en Chrome real con CDP y 15 de 16 controles de Codex pasando.
- **H1 (Inscripción en alta futura):** Decisión expresa de Carlos del 21/10/2026: *«Inscripción a partir de hoy»*. La inscripción se debe desde el alta, como funciona actualmente; la cuota del mes siguiente no cuenta como deuda antes de que empiece el mes. No se modificó la lógica de inscripción. Claude actualizó ALU-007 en la tabla.
- **H2 (Fecha mal formada al editar):** En `AlumnoWebController::validationRules`, `Carbon::parse($fechaNueva)` se ejecutaba antes de la validación. Se blindó la comparación con `try/catch (\Throwable)` y se agregó la regla `'bail'` previa a `'date'` en `$fechaAltaRules`, retornando HTTP 422 con mensaje descriptivo en castellano (`La fecha de alta no es una fecha válida.`) sin error 500 y sin escrituras. Mismo blindaje en `store`, `cuotaAltaPreview` e `inscripcionPreview`. Nueva regresión permanente: `test_h2_edicion_con_fecha_mal_formada_devuelve_422_sin_escrituras` (**VERDE**). Control 06 de Codex: **PASÓ**.
- **H3 (Reloj Wings en cliente y previsualización):**
  - En `resources/views/alumnos/_form.blade.php`: se envía `data-max="{{ \App\Support\FechaIngresoRegla::limiteMaximo()->format('Y-m-d') }}"` y la fecha por defecto del alta usa `now()->format('Y-m-d')` (reloj Wings / `RelojSimulado`). Cambio autorizado con `Diseno-autorizado:` (solo atributos de datos, sin impacto visual ni JS inline en Blade, AGENTS.md §1 y §5-bis).
  - En `resources/js/alumnos-inscripcion.js`: al recibir 422 por fecha fuera de límite, se preserva el mensaje específico devuelto por el backend (`datos?.errors?.fecha_alta?.[0]`) en pantalla y en `setCustomValidity`, impidiendo que sea reemplazado por el error genérico de conexión. Compilado con `npm run build`.
  - Nueva regresión permanente: `test_h3_formulario_alta_y_edicion_traen_data_max_y_fecha_defecto_wings` (**VERDE**).
  - Verificación en navegador real Chrome CDP (`test-navegador-t50.mjs`): probado con `FECHA_SIMULADA="2026-11-01 10:00:00"` (`max=2026-12-31`, por defecto `01/11/2026`, `01/12/2026` aceptada, `01/01/2027` rechazada con aviso específico) y con reloj real (`max=2026-11-30`, por defecto `21/10/2026`, `15/11/2026` aceptada, `01/12/2026` rechazada). Cero errores en consola (`consola: []`), capturas a 1366 y 375 generadas.
- **Entregables:** Sección «Segunda vuelta — H2 y H3» en `docs/06-pruebas/PRU-04/IMPLEMENTACION-T50.md` y evidencia en `docs/06-pruebas/PRU-04/evidencia-t50/segunda-vuelta/`.
- **Próximo paso:** Verificación de la segunda vuelta por Codex.

## 2026-10-21 — LOG GEM CAB — Verificación T42 y T43: comisión/ingresos por mes pagado y comisiones atrasadas

- **Objetivo y resultado:** Verificación independiente de las tareas T42 (DU-19, COB-012: comisión e ingresos por mes pagado) y T43 (DU-36, LIQ-002: decisión del dueño sobre comisiones atrasadas), implementadas por Codex CyE en los commits `d615bb8`, `6c8602f`, `ff738cb` y `b8e1ac1`. Resultado: **APROBADAS AMBAS**, con arnés sintético completo en `control-t42.php` (10 controles) y `control-t43.php` (16 controles), inspección de recibo PDF real y comprobación en navegador Chrome real (CDP) de la regla del 20/10 (cero recarga de página al decidir sobre una fila).
- **Verificación T42 (DU-19, COB-012):**
  - Comisiones asignadas a `max(fecha_cobro, periodo_imputado)` con exigencia estricta de asistencia del alumno en el mes que se liquida. Verificado cobro mixto en octubre (agosto atrasado + octubre del mes + noviembre adelantado): el adelanto no entra en octubre; entra en noviembre solo si hay asistencia en noviembre. Verificado cambio de año (diciembre cobra enero → comisión en enero) y pagos sin imputaciones (conserva regla histórica).
  - Conservación exacta de centavos: el prorrateo de cuotas con fracciones periódicas por método de mayor resto no crea ni pierde centavos en base ni en comisión.
  - Reportes e Inicio: cuota adelantada impacta económicamente en el mes pagado, mientras que el dinero real y saldos por medio se mantienen por fecha de cobro efectivo. Matrícula y otros conceptos no se trasladan.
  - Asientos antiguos en Cashflow: auditado `ReporteMensualService::pagosDeAsientosAntiguos`. La correspondencia exige unicidad exacta en la tupla `caja_id:fecha:subrubro:tipo_caja:monto`. Ante candidatos múltiples o ambiguos (`$candidatos->count() !== 1`), no adivina por importe, fecha o posición y conserva el mes original de la fecha de caja (decisión de Carlos).
- **Verificación T43 (DU-36, LIQ-002):**
  - Cuotas atrasadas nacen `PENDIENTE`, no figuran en el recibo y bloquean el cierre/pago de la liquidación mientras no sean resueltas.
  - Permisos estrictos: solo un `ADMIN` activo puede decidir (`APROBADA` o `RECHAZADA`). Operativa, profesor y admin inactivo son rechazados (403), así como peticiones sobre detalles de otras liquidaciones.
  - Recálculo preserva decisiones si pago, deuda, período, base y comisión coinciden; ante variaciones económicas, vuelve automáticamente a `PENDIENTE` para nueva revisión.
  - Auditoría permanente en `liquidacion_decisiones_atrasadas`: sobrevive a recálculos y borrado de liquidaciones abiertas.
  - Regla del 20/10 en navegador Chrome real (`test-browser-t43.mjs`): con un campo de texto no guardado (`#motivo_ajuste`) completado en pantalla, se presionaron los botones de Rechazar y Validar. En ambas operaciones el DOM se actualizó en el lugar (`#total-confirmado` pasó de $2.000 a $4.000 vía endpoint JSON) y el texto ingresado no se perdió (cero recarga de página ni parpadeo).
  - Recibo PDF real (`recibo-t43-verificacion.pdf`): comisiones rechazadas no figuran; comisiones aprobadas y del mes figuran en anexo; ajuste de administración cuadra con el monto pagado; sin colores rojos indebidos.
- **Entregables:**
  - Informe: `docs/06-pruebas/PRU-04/VERIFICACION-T42-T43-GEMINI.md`.
  - Evidencia completa en `docs/06-pruebas/PRU-04/evidencia-verificacion-t42-t43-gemini/`: scripts `control-t42.php`, `control-t43.php`, `seed-browser-t43.php`, `test-browser-t43.mjs`, JSONs de resultados, capturas 1366px (`01-liquidacion-inicial-1366.png`, `02-liquidacion-decidida-1366.png`), PDF renderizado y dump de texto.
- **Próximo paso:** Cerrar T42 y T43 en el tablero (`estado=cerrado verifica=Gemini`).

## 2026-10-20 — LOG GEM CAB — Corrección de hallazgos T64 (H1, H2, H3 de Claude)

- **Objetivo y resultado:** Corrección de los tres hallazgos reportados por Claude en `docs/06-pruebas/PRU-04/VERIFICACION-T64-CLAUDE.md` para la tarea T64 (DU-45, CFG-004). Resultado: **CORREGIDO Y LISTO PARA VERIFICACIÓN**, con prueba en navegador real (Chrome CDP) y 3 pruebas automatizadas permanentes nuevas en PHPUnit pasando al 100%.
- **Hallazgos resueltos:**
  - **H1 (UI / Editor):** En `resources/js/configuraciones.js`, `btn-cancelar-edit` no reponía los inputs al cancelar y `recopilarReglas` leía inputs sucios de paneles cerrados. Se implementó un mapa `originalesReglas` que mantiene los valores confirmados por ID. Al pulsar Cancelar se restauran los inputs originales (`restaurarValoresOriginales(id)`) y se oculta el panel; `recopilarReglas` toma los valores guardados si el panel no está en edición activa. Comprobado en navegador real con clics reales (`scripts/pruebas/probar-h1-browser.mjs`): abrir regla 1, tipear 90%, cancelar, abrir regla 2, tipear 65%, guardar → regla 1 conserva 100% y regla 2 se guarda con 65%.
  - **H2 (Backend):** En `ReglaPrimerPagoWebController::guardarTodas`, se añadió `'distinct'` en `reglas.*.id` y mensaje en castellano: *«Cada regla debe tener un identificador único; no se puede repetir una misma regla.»*. Regresión permanente: `test_lote_con_id_repetido_es_rechazado`.
  - **H3 (Backend):** En `ReglaPrimerPagoWebController::guardarTodas`, se añadió `'exists:reglas_primer_pago,id'` en `reglas.*.id` y mensaje en castellano: *«Una de las reglas enviadas no existe o ya no está disponible.»* (evita 404 en inglés). Regresión permanente: `test_lote_con_id_inexistente_es_rechazado_con_mensaje_en_castellano`.
  - **Preservación de reglas inactivas:** La limpieza de reglas obsoletas en `guardarTodas` ahora filtra exclusivamente `where('activo', true)->whereNotIn(...)`, preservando reglas históricas inactivas. Regresión permanente: `test_lote_conserva_reglas_inactivas_existentes`.
- **Integridad visual y diseño:** Sin cambios en `resources/views/**` ni `resources/css/**` (AGENTS.md §1). Compilado con `npm run build` sin JavaScript inline en Blade (AGENTS.md §5-bis).
- **Pruebas en `wings_testing_gemini`:** 11 pruebas pasando en `ReglaPrimerPagoSinHuecosT64Test` (40 aserciones, 8.74 s) y 5 en `ReglaPrimerPagoSinSuperposicionTest` (40 aserciones).
- **Cierre y verificación:** Claude verificó la segunda vuelta en navegador y código (commit `ec7164c1`, `VERIFICACION-T64-CLAUDE.md`); **T64 CERRADA**.
- **Próximo objetivo:** T76 (Auditoría de uso de todas las pantallas, orden de Carlos del 20/10, según `docs/06-pruebas/PRU-04/ORDEN-AUDITORIA-USO.md`).

## 2026-10-20 — LOG GEM CAB — Implementación T64: reglas de primer pago sin superposiciones ni días sin cubrir

- **Objetivo y resultado:** Implementación de la tarea T64 bajo decisión de Carlos DU-45 («Impedir reglas superpuestas y días sin cubrir») y caso de prueba CFG-004. Resultado: **IMPLEMENTADA**, con guardado en lote atómico, 8 pruebas automatizadas nuevas en `ReglaPrimerPagoSinHuecosT64Test` (más 5 en `ReglaPrimerPagoSinSuperposicionTest`) pasando al 100%, capturas generadas y lista para verificación independiente (`a_verificar`).
- **Problema de diseño resuelto:** Para evitar estados intermedios bloqueantes al redistribuir tramos (ej. pasar de «1–15 y 16–31» a «1–10 y 11–31»), se diseñó el guardado atómico de lote (`PUT /configuraciones/primer-pago`). El servidor valida el conjunto completo en memoria y aplica los cambios dentro de una transacción `DB::transaction()`: actualiza in-place las reglas existentes manteniendo sus IDs (integridad de claves foráneas con `pagos.regla_primer_pago_id`), crea nuevas y elimina descartadas. Si hay huecos o superposiciones, se produce un rollback completo.
- **Regla central y validaciones:**
  - `app/Support/ReglasPrimerPagoValidator.php`: comprueba que el conjunto cubra exactamente del día 1 al 31 sin fisuras ni solapamientos. Mensajes descriptivos en castellano: *«Los días 11 a 15 quedan sin cubrir. Todos los días del 1 al 31 deben estar cubiertos por una regla.»* o *«Los días 12 a 15 se pisan con la regla "X"...»*.
  - `app/Http/Controllers/ReglaPrimerPagoWebController.php`: nuevo método `guardarTodas` y blindaje de métodos individuales `store`, `update`, `destroy` simulando el conjunto resultante contra la base de datos.
  - `resources/js/configuraciones.js`: recopila todas las reglas activas de la pantalla y envía el lote. Muestra errores por campo y de conjunto, y redibuja las tarjetas conservando reactividad (sin JavaScript en Blade, AGENTS.md §5-bis). Compilado con Vite.
  - `resources/views/**` y `resources/css/**`: **100% intactos** (`git diff --stat -- resources/views resources/css` vacío, AGENTS.md §1).
- **Pruebas y suite (`wings_testing_gemini`):**
  - `tests/Feature/ReglaPrimerPagoSinHuecosT64Test.php`: 8 pruebas verdes (29 aserciones, 11.34 s: hueco al crear, hueco al editar, hueco al borrar, borrar la única regla, superposición individual y en lote, lote 1–15/16–31 a 1–10/11–31 exitoso, lote inválido con rollback, primera cuota del alta con regla del día).
  - `tests/Feature/ReglaPrimerPagoSinSuperposicionTest.php`: 5 pruebas verdes (40 aserciones).
  - `tests/Feature/ConfiguracionA11Test.php`: 8 pruebas verdes (98 aserciones).
  - `tests/Feature/DocumentacionNoMienteTest.php`: verde con corte actualizado a 982 pruebas.
- **Evidencia visual y documentación:**
  - 6 capturas reales (1366px escritorio y 375px teléfono en marco) en `docs/06-pruebas/PRU-04/evidencia-t64/`.
  - Documento aprobador navegable para Carlos: `docs/06-pruebas/PRU-04/APROBAR-T64.html`.
  - Informe técnico detallado en `docs/06-pruebas/PRU-04/IMPLEMENTACION-T64.md`.
  - Catálogo de pruebas: caso CFG-004 en `CFG.json` actualizado (punto f obsoleto por DU-45).
- **Próximo paso:** Verificación independiente de la lógica por Claude o Codex.

## 2026-10-20 — LOG GEM CAB — Implementación T50: fecha de ingreso futura, solo hasta fin de mes siguiente

- **Objetivo y resultado:** Implementación de la tarea T50 bajo decisión de Carlos DU-06 («Aceptar solo hasta el mes siguiente») y caso de prueba ALU-007. Resultado: **IMPLEMENTADA**, con 10 pruebas automatizadas nuevas pasando al 100%, capturas generadas y lista para verificación independiente (`a_verificar`).
- **Regla implementada:** La fecha de ingreso (`fecha_alta`) puede ser pasada, de hoy o futura solo hasta el último día del mes siguiente a hoy (`Carbon::now()`, respetando reloj simulado). Fechas posteriores se rechazan con mensaje en castellano: *«La fecha de ingreso no puede ser posterior al dd/mm/aaaa.»*.
- **Alcance y archivos modificados:**
  - `app/Support/FechaIngresoRegla.php`: clase soporte con `limiteMaximo()`, `esValida()` y `mensajeError()`.
  - `app/Http/Controllers/AlumnoWebController.php`: validación en `store` y condicional en `update` (solo si se cambia la fecha; no bloquea edición de otros campos en alumnos existentes con fechas históricas). Previsualizaciones de cuota e inscripción retornan 422 si la fecha excede el límite.
  - `app/Services/PagoCuotaService.php`: `previsualizarCuotaAlta` valida contra el límite y lanza `ValidationException`.
  - `app/Services/PrimeraCargaExcelService.php`: `revisar` marca celda en columna 5 (E) con error descriptivo y bloquea la importación total.
  - `resources/js/alumnos-form.js`: input `fecha_alta` con atributo `max` dinámico y validación nativa `setCustomValidity` en tiempo real (sin JavaScript inline en Blade, AGENTS.md §5-bis).
  - `resources/views/**` y `resources/css/**`: **100% intactos** (AGENTS.md §1). El mensaje se muestra mediante la estructura de errores existente (`@error('fecha_alta')`).
- **Pruebas y suite (`wings_testing_gemini`):**
  - 10 pruebas en `tests/Feature/FechaIngresoFuturaT50Test.php` (44 aserciones, 15.78 s, todas verdes).
  - `tests/Feature/AlumnoSinPlanT56T68Test.php`, `AlumnoGrupoDelDeporteTest.php` y `PrimeraCargaExcelTest.php`: todas aprobadas (611 aserciones).
  - `tests/Feature/CspSinCodigoIncrustadoTest.php`: en verde (cero bloques `<script>` inline, cero manejadores).
- **Evidencia visual y documentación:**
  - Capturas reales a 1366px y marco de 375px para alta de alumno y primera carga en `docs/06-pruebas/PRU-04/evidencia-t50/`.
  - Visor para Carlos en `docs/06-pruebas/PRU-04/APROBAR-T50.html`.
  - Contrato actualizado en `docs/02-contratos/Wings-contrato-estadosAlum-cobranza-asistencia-V1.md`.
  - Catálogo de pruebas actualizado: caso ALU-007 en `ALU.json` a `"estado": "vigente"`.
  - Informe técnico completo en `docs/06-pruebas/PRU-04/IMPLEMENTACION-T50.md`.
- **Próximo paso:** Verificación independiente de la lógica por Claude / Codex.

## 2026-10-20 — LOG GEM CAB — Verificación independiente T73: cero JavaScript dentro de las vistas

- **Objetivo y resultado:** Verificación independiente de la tarea T73 implementada por Claude CyE en commit `b05bda9`. Resultado: **APROBADA** y **CERRADA** en el tablero. Cero JavaScript inline residual en las vistas Blade y comportamiento interactivo 100% preservado.
- **Alcance verificado:**
  - 8 bloques `<script>` extraídos de 8 vistas hacia 5 módulos dedicados en `resources/js/` (`caja-editar.js`, `clases-show.js`, `clases-index.js`, `liquidaciones-create.js`, `alumnos-index.js`) y `ds-app.js`.
  - 8 formularios con `onsubmit="return confirm(...)"` migrados a `form[data-confirmar-envio="..."]`.
- **Pruebas en navegador real (Chrome CDP sobre `http://127.0.0.1:8740` con `wings_testing_gemini` y assets compilados con `npm run build`):**
  - Punto 1 (Ingreso): Toggle de contraseña (ojo) alterna visibilidad y tipo de input.
  - Punto 2 (Grupos y Profesores): Selects con `data-enviar-al-cambiar` disparan submit automático con filtro de deporte.
  - Punto 3 (Caja editar): Tabs Ingreso/Egreso alternan estilos, filtran opciones de rubro por `data-tipo` y subrubros por rubro.
  - Punto 4 (Ficha de clase): Checkbox de asistencia actualiza contador en vivo, alerta de exceso de plan semanal, guardado AJAX con toast, modal cancelar exige motivo, panel de profesoras actualiza y cierra correctamente.
  - Punto 5 (Clases index): Scroll automático al primer bloque de clases futuras de hoy; selects de filtros envían al cambiar.
  - Punto 6 (Liquidación crear): Selección de profesor marca fila y advierte clases sin asistencia; botones de "Incluir hoy" alternan valor y estilos.
  - Punto 7 (Alumnos autocomplete): Debounce 250ms, dropdown navegable con teclado (`ArrowDown`/`ArrowUp`), sanitización XSS comprobada (nombre con tags HTML es escapado en texto plano).
  - Punto 8 (8 confirmaciones): Cancelar diálogo no bloquea submit ni deshabilita botones; aceptar envía el formulario incluso con comillas en el mensaje (`Rubro "Especial T73"`).
  - Punto 9 (Consola & CSP): 0 errores de consola y 0 violaciones de CSP.
- **Suite automatizada:** `CspSinCodigoIncrustadoTest` y `AlumnoAutocompleteViewSecurityTest` en verde.
- **Informe y entrega:** `docs/06-pruebas/PRU-04/VERIFICACION-T73-GEMINI.md`. Evidencias en `docs/06-pruebas/PRU-04/evidencia-verificacion-t73-gemini/`. T73 cerrada en tablero.

## 2026-10-19 — LOG GEM CYE — Verificación de Lógica T40: Reintento automático de cuotas y constancia

- **Objetivo y resultado:** Verificación independiente de la lógica implementada por Claude CyE en commit `c9624c47` (T40, DU-52 / CBZ-020). Resultado: **APROBADA** (sin fallas lógicas, sin riesgo de duplicación y con avisos tolerantes a fallos). Sin cambios de pantallas ni revisión visual requerida.
- **Lógica verificada:**
  - `GenerarDeudasMensualesCommand::generar`: lógica nuclear preservada (elegibilidad por asistencias/alta+pago previo, planes, precios y envío a revisión sin tocar cuotas ya existentes).
  - `GeneracionCuotas::faltaGenerar`: día 1 genera; día 2+ exige que el mes anterior figure generado; cambio de año comprobado (enero mira diciembre del año anterior).
  - Idempotencia y cortes: `crearDeudaSiNoExiste` y `AlumnoRevisionCobranza::firstOrCreate` evitan duplicar; la constancia se graba al final de la corrida; si un corte abrupto interrumpe el proceso, se completa al día siguiente sin duplicar.
  - El mes atendido queda congelado frente al scheduler durante el resto del mes.
  - Ejecución manual técnica con `--periodo` o sin él registra constancia; rechazo estricto de `--si-falta` con `--periodo`.
  - `AvisoAdminService`: fallos de envío capturados con log sin voltear la generación; resumen diario de las 08:00 muestra advertencia hasta que el mes se genera.
  - `routes/console.php`: programado a las 06:00 todos los días con `withoutOverlapping()` y `onOneServer()`.
  - Migración `generaciones_cuotas`: anota el mes en curso en entornos no-testing evitando corridas extemporáneas al desplegar.
  - Reloj simulado: respeta `Carbon::now()`.
- **Pruebas y suite (`wings_testing_gemini`):** 9 dirigidas en `GeneracionCuotasReintentoT40Test` (39 aserciones, 8.92 s) y `GenerarDeudasMensualesCommandTest` (6 aserciones, 10.09 s) en verde. Suite rápida (`--exclude-group=lento`): 820 aprobadas, 4 omitidas, 0 fallidas (6657 aserciones, 325.79 s, salida 0). Vistas y sintaxis PHP sin errores.
- **Informe y cierre:** `docs/06-pruebas/PRU-04/VERIFICACION-T40-GEMINI.md`. T40 cerrada en el tablero único (`tareas.json`).
- **Próximo paso:** Continuar según asignación de Carlos.

## 2026-10-19 — LOG GEM CYE — Corrección de C1 y C2 en T34: View Composer y transacción en liquidaciones

- **Objetivo y alcance:** Subsanar los hallazgos C1 (consultas por tarjeta) y C2 (validación alternativa en incluir_hoy) señalados por Codex CyE en `docs/06-pruebas/PRU-04/VERIFICACION-T34-T67-CODEX.md`.
- **C1 — View Composer (`app/Providers/AppServiceProvider.php`):**
  - Memoización del badge en `$request->attributes` para calcularlo como máximo 1 vez por petición HTTP.
  - Se eliminan las 56 consultas redundantes al renderizar tarjetas múltiples.
  - Al cambiar de petición (nuevo request en tests o web), se recalcula fresco.
- **C2 — Validación atómica (`app/Http/Controllers/LiquidacionWebController.php`):**
  - Validación y generación de liquidación envueltas en `DB::transaction(...)`: ante cualquier fallo o duplicado se hace rollback total sin persistir validaciones.
  - `incluir_hoy` restringido a clases cuyo mes/año coincida con el período solicitado (`today()->month === $mes && today()->year === $anio`).
  - Cada clase candidata exige `!$clase->integraLiquidacionCerrada()`.
- **Pruebas y regresiones (`wings_testing_gemini`):**
  - 5 pruebas nuevas en `ClaseSinAsistenciaYDniT34T67Test.php` (11 tests en total, 78 aserciones, 10.68 s en verde). Rojas antes (C1 con 14 COUNTs vs 1; C2 persistiendo tras error y en meses ajenos), verdes después.
  - Suite completa: 934 aprobadas, 4 omitidas, 0 fallidas de código (8239 aserciones, 1498.66 s). Salida 0.
  - Suite rápida: 820 aprobadas, 4 omitidas (6656 aserciones, 291.24 s).
  - Guardianes: `DocumentacionNoMienteTest` (939 pruebas) y `TableroAlDiaTest` en verde.
  - Sin cambios visuales (`git diff --stat -- resources/views resources/css` vacío).
- **Informe y entrega:** `docs/06-pruebas/PRU-04/CORRECCION-T34-C1-C2-GEMINI.md`. T34 pasada a `a_verificar` para Codex.

## 2026-10-19 — LOG GEM CYE — Adopción de la regla de testeo en dos velocidades y verificación T59/T47

- **Nueva regla de testeo (decisión de Carlos, 19/10/2026, AGENTS.md §6-ter):**
  - **Suite rápida por tarea:** `DB_DATABASE=wings_testing_gemini php artisan test --exclude-group=lento` (~4 min) para cambios cotidianos de código + pruebas dirigidas del área tocada.
  - **Suite completa (`php artisan test`):** reservada para antes de desplegar, cuando la tarea toque plata compartida (caja, cobros, deuda, cashflow, liquidaciones, recibos), toque un área marcada `lento`, o por impacto cruzado de codebase-memory.
  - **Regla de concurrencia:** una sola suite completa a la vez por máquina (comprobar previamente con `Get-CimInstance Win32_Process`).
- **Verificación de T59 y T47 (Claude CAB, commit `d7b0a5c8`):**
  - **T59 (APROBADA):** El dueño es redirigido a Cashflow al abrir `/caja/movimiento`; si envía el formulario de caja, se aborta sin escribir movimientos en la base ni afectar el turno operativo.
  - **T47 (APROBADA):** Nueva pantalla 405 en castellano con cabecera `Allow` y botón `Volver` al inicio del rol; `AvisoError::texto` sanitiza fallos técnicos y excepciones de modelos sin tocar mensajes de negocio; los 29 `catch` en controladores web usan `AvisoError`. Visual aprobado por Carlos en `04bb9228`.
  - **Pruebas y suite (`wings_testing_gemini`):** 23 dirigidas pasando (431 aserciones, 16.53 s). Suite completa ejecutada al tocar plata compartida: 921 aprobadas, 4 omitidas, 0 fallidas, 8179 aserciones, 1118.68 s. Salida 0.
  - **Informe y cierre:** `docs/06-pruebas/PRU-04/VERIFICACION-T59-T47-GEMINI.md`. T59 y T47 cerradas en `tareas.json`.
- **Próximo paso:** Continuar según prioridades del plan.

## 2026-10-19 — LOG GEM CYE — Vistas de T68 aprobadas por Carlos y rediseño de Grupos y Profesores

- **Objetivo y aprobación:** Carlos aprobó el 19/10/2026 las vistas de T68 («Las vistas están aprobadas») tras revisar las capturas reales generadas del sistema andando.
- **Grupos (`resources/views/grupos/show.blade.php`):** Tarjeta *«Alumnos en este grupo»* equipada con scroll vertical (`max-height: 190px; overflow-y: auto;`) eliminando la lista infinita y mostrando a todos los alumnos activos en orden alfabético; tarjeta *«Cronograma semanal»* con scroll vertical.
- **Profesores (`resources/views/profesores/show.blade.php`):** Rediseño a 3 columnas en escritorio (`lg:grid-cols-3`): Col 1 Datos, Col 2 Clases asignadas (con scroll), Col 3 Historial de liquidaciones acotado a las últimas 5 (`$liquidaciones->take(5)`) con scroll vertical.
- **Evidencia y pruebas:** `APROBAR-T68.html` actualizado con las nuevas capturas (`01-grupo-show-1366.png`, `02-grupo-show-marco-375.png`, `03-profesor-show-1366.png`, `04-profesor-show-marco-375.png`). `AlumnoSinPlanT56T68Test` (8 tests) y `CspSinCodigoIncrustadoTest` en verde. Sin JS incrustado.
- **Próximo paso:** Codex / Claude finalizan la lógica de H1/T68 según corresponda.

## 2026-10-19 — LOG GEM CYE — Verificación de Lógica T66: Profesora obligatoria y liquidaciones protegidas

- **Objetivo y resultado:** Verificación independiente de la lógica implementada por Codex CAB en commit `9bb7bd68` (T66). Resultado: **APROBADA** (sin hallazgos de lógica, validaciones ni permisos).
- **Aspecto visual:** Aprobado previamente por Carlos el 19/10/2026 («Vista aprobadas»); no se reevaluó ni se pidieron capturas conforme a AGENTS.md §6a.
- **Lógica comprobada:** `store` (alta única y serie) exige al menos una profesora activa del deporte y rechaza antes de cualquier persistencia (serie atómica con rollback). `update` impide vaciar profesoras y revierte fecha/horario ante fallos. `actualizarProfesores` bloquea la clase bajo transacción (`lockForUpdate`), rechaza clases canceladas y en liquidación cerrada o pagada (por HORA y COMISIÓN), y retira el bypass `confirmar=true`. Permisos según `PERMISOS-ROLES.md` (ADMIN acceso total; OPERATIVO solo clases de hoy/futuras; PROFESOR 403).
- **Pruebas y suite:** 16 dirigidas pasando en `ClaseProfesoraObligatoriaT66Test` (115 aserciones); 60 pruebas de clases (593 aserciones); suite completa en `wings_testing_gemini`: 920 aprobadas, 4 omitidas, 0 fallidas, 8167 aserciones, 1246.60 s (salida 0). Vistas y sintaxis PHP sin errores.
- **Informe y cierre:** Registrado informe en `docs/06-pruebas/PRU-04/VERIFICACION-T66-GEMINI.md` y cerrada en tablero único (`tareas.json`).
- **Próximo paso:** Continuar con tareas pendientes según asignación de Carlos.

## 2026-10-19 — LOG GEM CAB — Corrección de T68: Datos reales en Cronograma semanal de Grupos y Liquidaciones/Clases de Profesores

- **Objetivo y alcance:** Reemplazar los carteles de «Próximamente» por datos reales del sistema conforme a la indicación de Carlos (19/10), manteniendo la disposición y estructura visual de las vistas.
- **Grupos (`resources/views/grupos/show.blade.php`):**
  - Se conserva la disposición en dos columnas inferiores: `Precios por frecuencia` (3/5) y `Cronograma semanal` (2/5).
  - En `Cronograma semanal` se listan las clases futuras no canceladas del grupo (`$clasesFuturas`), con fecha, día y rango horario. Si no hay clases futuras, muestra `Sin clases futuras programadas.` y botón `+ Programar clases` para ADMIN.
- **Profesores (`resources/views/profesores/show.blade.php`):**
  - Se conserva la disposición en 2 columnas: información del profesor (3/5) y columna derecha (2/5) con dos tarjetas.
  - En `Historial de liquidaciones` se listan las liquidaciones reales del profesor con mes/año, importe y badge de pago. Si no tiene: `Sin liquidaciones registradas.`.
  - En `Clases asignadas` se listan las clases próximas asignadas con grupo y horario. Si no tiene: `Sin clases asignadas próximas.`.
- **Pruebas y capturas:**
  - `AlumnoSinPlanT56T68Test` actualizado con 5 pruebas en verde (38 aserciones).
  - Capturas regeneradas en escritorio (1366px), marco móvil 360px y marco móvil 375px en `docs/06-pruebas/PRU-04/evidencia-t56-t68/`.
  - Sin JavaScript incrustado (CSP intacto).
- **Próximo paso:** Visto bueno visual de Carlos.

## 2026-10-19 — LOG GEM CAB — Corrección de T34: H1 (etiqueta en tarjeta) y H2 (bloqueo en liquidación cerrada)

- **Objetivo y alcance:** Subsanar los hallazgos H1 y H2 señalados por Codex en `docs/06-pruebas/PRU-04/VERIFICACION-T34-T67-CODEX.md` para la tarea T34 (T67 cerrada).
- **H1 — Etiqueta en tarjeta de clase (`resources/views/clases/_card.blade.php`):**
  - Se corrigió la condición de asistencia: si no hay filas registradas dice «Pendiente» (`var(--color-text-muted)`); si hay filas registradas pero 0 presentes dice «Sin presentes» (`var(--color-warning, #d97706)`); si hay presentes dice «Cargada» (`var(--color-success)`).
  - Respetadas las reglas del Design System sin alterar tipografías, tokens ni estructura y sin JS incrustado.
- **H2 — Protección en servidor (`app/Http/Controllers/ClaseWebController.php`):**
  - En `toggleValidada($id)` se agregó chequeo previo: `$clase->integraLiquidacionCerrada()`.
  - Si la clase integra una liquidación cerrada (por hora o por comisión de sus profesores en el mes), rechaza la petición con 422 (JSON) o `back()->with('error', '...')`, impidiendo revertir la validación y que vuelva a `sin_presentes`.
- **Pruebas y capturas:**
  - En `ClaseSinAsistenciaYDniT34T67Test.php`: agregados tests para H1 y H2 (6 pasadas en total).
  - Capturas regeneradas con Chrome headless en 1366px y marco 360px en `docs/06-pruebas/PRU-04/evidencia-correccion-t34/` mostrando la tarjeta con `Asistencia: Sin presentes`.
  - Suite completa: 903 pasadas, 4 omitidas, 0 fallidas (8024 aserciones) en `wings_testing_gemini` (corte 908 pruebas).
- **Próximo paso:** Verificación independiente por Codex (`docs/06-pruebas/PRU-04/CORRECCION-T34-GEMINI.md`).

