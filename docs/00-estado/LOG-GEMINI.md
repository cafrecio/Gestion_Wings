# Wings — Bitácora activa de GEMINI

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-GEMINI.md)
· [Entradas archivadas el 06/10](../99-archivo/bitacoras/2026-10-06/LOG-GEMINI.md) · [Entradas archivadas el 19/10](../99-archivo/bitacoras/2026-10-19/LOG-GEMINI.md)

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

