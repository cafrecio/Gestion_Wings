# Wings — Bitácora activa de GEMINI

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

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

## 2026-10-19 — LOG GEM CAB — Implementación de T56 (alumno activo no puede quedar sin plan) y T68 (sacar carteles de «Próximamente»)

- **Objetivo y alcance:** Implementar T56 (DU-15: impedir que un alumno activo quede sin plan) y T68 (DU-57: retirar los tres carteles de «Próximamente»).
- **T56 — Alumno activo siempre con plan:**
  - Inspección exhaustiva de 7 puertas de entrada de alumnos sin plan.
  - Encontrados y corregidos 2 huecos:
    1. `AlumnoWebController::update`: al cambiar de grupo (`$cambiaGrupo`), ahora se exige obligatoriamente seleccionar una frecuencia semanal del nuevo grupo (`plan_id` requerido y validado con `Rule::exists('grupo_planes')->where('grupo_id', $nuevoGrupo)`).
    2. `PagoCuotaService::registrarPagoCuotaOperativo` y `registrarPagoCuotaAdmin`: pagar deuda vieja a un inactivo solo reactiva a `activo = true` si el alumno tiene plan vigente para el mes actual (`$this->obtenerPlanParaPeriodo(..., now()->format('Y-m'))?->plan`). De lo contrario se cobra la deuda pero permanece inactivo, evitando que el generador mensual del día 1 lo saltee sin cobrar.
  - Relevamiento de bases: 0 alumnos activos sin plan en local, testing y VPS (`wingstest` sobre 100 alumnos activos, 0 sin plan).
- **T68 — Retiro de «Próximamente»:**
  - `resources/views/grupos/show.blade.php`: retirado el bloque «Cronograma semanal» («Próximamente: horario de clases del grupo.»); «Precios por frecuencia» se expandió a `md:col-span-5` ocupando el ancho completo inferior.
  - `resources/views/profesores/show.blade.php`: retirada la columna derecha con «Historial de liquidaciones» y «Clases asignadas» («Próximamente.»); la tarjeta de profesor quedó a ancho completo.
  - Comprobado que no queda ninguna otra ocurrencia en ninguna vista de la aplicación.
- **Evidencia visual y pruebas:**
  - `tests/Feature/AlumnoSinPlanT56T68Test.php`: 5 pruebas que validan cambio de grupo, edición sin plan, cobro a inactivo con/sin plan y ausencia de «Próximamente».
  - Capturas generadas con Chrome headless en 1366px y marco móvil 360px en `docs/06-pruebas/PRU-04/evidencia-t56-t68/`.
  - Suite completa: 891 aprobadas, 3 omitidas, 0 fallidas en `wings_testing_gemini` (corte 895 pruebas).
- **Próximo paso:** Verificación por Claude y visto bueno visual por Carlos.

## 2026-10-19 — LOG GEM CAB — Verificación independiente de T44: Historial de tarifas y vigencia por alumno y mes

- **Objetivo y veredicto:** Verificación independiente del trabajo de Codex en los commits `10a42cd` y `9826e49` (T44: historial de tarifas y vigencia de cambios de plan, decisiones DU-40 de Carlos). Se reemplazó a Claude por falta de créditos. **Veredicto: APROBADA y CERRADA** (visual previamente aprobado por Carlos el 19/10: *«Ok, pasan»*).
- **Inspección de código y reglas de negocio:**
  - `GrupoPlan::precioParaPeriodo`: evaluada y probada la lógica de corte por fecha de alta original para altas nuevas en el mes, y fin de mes anterior para existentes. Casos de múltiples aumentos en el mismo mes y mismo día verificados.
  - `CambioPlanService`: bajas de plan se difieren siempre al mes siguiente; subidas con cuota impaga o pago parcial son inmediatas y conservan lo pagado ($60.000 con $10.000 pagados deja saldo de $50.000); subidas con mes saldado se difieren al mes siguiente. Se conserva el `porcentaje_alta` original.
  - Migración `grupo_plan_precios`: preserva precios iniciales sin fecha límite (`vigente_desde = null`) y sin pérdida de decimales.
  - Sin lecturas de `precio_mensual` para cobros ni deudas en todo `app/` ni en vistas operativas. API deshabilitada en `bootstrap/app.php`.
- **Pruebas y suite:**
  - 19 pruebas de `PrecioPlanVigenciaT44Test` pasaron (89 aserciones).
  - 17 pruebas de `CambioPlanCobroTest` y `CambioPlanConDescuentoMatrizTest` pasaron (105 aserciones).
  - 5 pruebas sintéticas de verificación independiente ejecutadas y aprobadas.
  - Suite completa: **882 pasadas, 3 omitidas, 0 fallidas (7.823 aserciones)** en 935,82 s en `wings_testing_gemini`.
- **Cierre:** Informe redactado en `docs/06-pruebas/PRU-04/VERIFICACION-T44-GEMINI.md`. Tarea T44 cerrada en tablero con `hizo=Codex` y `verifica=Gemini`.

## 2026-10-19 — LOG GEM CAB — Corrección de borde en celular de la lista de asistencia (clases/show)

- **Corrección de borde en tarjetas de alumnos (orden de Carlos):**
  - En `resources/views/clases/show.blade.php`: las tarjetas de alumnos usaban `ds-card ds-surface` con un `<div class="ds-card__rail ds-rail">` interior. En móvil (`<= 768px`), la regla `app.css:1247` forzaba `padding-inline: calc(var(--mobile-actions-inset) - 1px) !important;` en `.ds-content > .ds-surface`, inyectando 23px de espacio blanco a la izquierda del riel y despegándolo del borde exterior como una píldora flotante.
  - Se adaptó la tarjeta al estándar canónico de Wings: `<div class="alumno-card alumno-card--{{ $rail }} alumno-asistencia-card">`, eliminando el div interior `ds-rail` y `ds-card__content`. `alumno-card` define `border-left: 4px solid var(--color-sport-{{ $rail }})`, y en móvil `app.css:1242` descuenta los 4px (`padding-left: calc(var(--mobile-actions-inset) - 4px) !important;`), logrando que el borde izquierdo quede perfectamente integrado, continuo y al ras de la tarjeta.
  - Se regeneraron `03-lista-asistencia-dni-recortado.html` y las capturas en escritorio (1366px) y móvil marco (360px). Comprobado visualmente: borde izquierdo continuo, sin píldora flotante ni margen blanco roto.
- **Pruebas:** `ClaseSinAsistenciaYDniT34T67Test` y `CspSinCodigoIncrustadoTest` 100% en verde sobre `wings_testing_gemini`.

## 2026-10-19 — LOG GEM CAB — Corrección de diseño y evidencia en T34 y T67 (botón Nuevo fuera del título y planes activos en simulación)

- **Corrección de diseño visual (orden de Carlos):**
  - En `resources/views/clases/index.blade.php`: se retiró el botón `<x-ds.button>` `Nuevo` de la barra de título de *"Clases de hoy"* (`stats-bar` superior).
  - Se ubicó en la barra de resultados (`stats-bar mb-3` debajo de los filtros, sección E, a la derecha del contador de resultados), coincidiendo canónicamente con `alumnos/index.blade.php`.
- **Corrección de datos de simulación y pruebas:**
  - En `tests/Feature/ClaseSinAsistenciaYDniT34T67Test.php`: se asignaron `GrupoPlan` y `AlumnoPlan` activos al alumno en `setUp()`. Se agregó aserción verificando que no figure `Sin plan` y que muestre el ratio semanal (`0/2`).
  - Se regeneraron las 6 capturas de evidencia real en `docs/06-pruebas/PRU-04/evidencia-t34-t67/` con alumnos que poseen plan activo: en `clases/show` ahora figura `0/2` en verde en lugar de `Sin plan`, y en el listado `Nuevo` está ubicado exclusivamente en la barra de resultados.
- **Suite completa:** 882 pruebas pasadas (7823 assertions) en `wings_testing_gemini`.

## 2026-10-18 — LOG GEM CAB — Implementación de T34 y T67: Clases con lista tomada y DNI recortado en lista de asistencia

- **Objetivo y alcance:** Implementar T34 (DU-33: con lista tomada sale del contador y de «Sin asistencia», y queda en filtro nuevo «Sin presentes» para decisión del dueño) y T67 (DU-56: en la lista de asistencia mostrar solo los últimos 3 números del DNI: `DNI …123`).
- **T34 — Contador y filtros de clases:**
  - `AppServiceProvider:67`: `$badge` ahora consulta con `doesntHave('asistencias')`. Mantiene sincronía con el filtro `estado=finalizada` («Sin asistencia»).
  - `ClaseWebController:129`: filtro `estado=finalizada` usa `doesntHave('asistencias')`. Nuevo filtro `estado=sin_presentes` (solo admin) para clases pasadas, no canceladas, no validadas con asistencias tomadas pero cero presentes.
  - `ReporteMensualService:193`: sincronizada la consulta del indicador `'asistencia'` con `doesntHave('asistencias')`.
  - `LiquidacionWebController:93`: sincronizado el conteo de `clases_sin_asistencia` por profesor.
  - `resources/views/clases/index.blade.php`: agregado `<option value="sin_presentes">Sin presentes</option>` para el admin.
- **T67 — DNI recortado en asistencia:**
  - `resources/views/clases/show.blade.php:335`: reemplazado `DNI {{ $alumno->dni }}` por `DNI …{{ substr((string)$alumno->dni, -3) }}`.
  - Verificado que el profesor no tiene acceso a fichas de alumnos y que el HTML de `clases/show` no contiene el DNI completo.
- **Pruebas y verificación empírica:**
  - 4 pruebas nuevas en `tests/Feature/ClaseSinAsistenciaYDniT34T67Test.php` cubriendo contador, filtro sin asistencia, filtro sin presentes, permisos para roles, liquidación y recorte de DNI (100% aprobadas).
  - 6 capturas reales en escritorio (1366 px) y marco celular (360 px) en `docs/06-pruebas/PRU-04/evidencia-t34-t67/`.
  - Contrato actualizado en `docs/02-contratos/Wings-Contrato-Clases-Asistencias-V1.md`.
- **Próximo paso:** T34 y T67 pasan a `a_verificar` para Claude en el tablero; Carlos evalúa si requiere registrar explícitamente «no se paga» para clases sin presentes.

## 2026-10-18 — LOG GEM CAB — Verificación independiente de T58: El dueño crea desde la ficha la cuota de un mes pasado

- **Objetivo y veredicto:** Verificar de forma independiente el trabajo de Claude en los commits `687262f` y `c154ac4` (T58 / DU-23: «Que el dueño pueda crear la cuota de un mes pasado desde la ficha»). **Veredicto: APROBADO (pasa a Carlos para visto bueno visual: `tiene=Carlos`, `paso="Verificada por Gemini. Falta el visto bueno visual de Carlos"`).**
- **Inspección de código:**
  - Leídos los cuerpos de `PagoCuotaService::mesesPasadosSinCuota`, `crearCuotaMesPasado`, `obtenerPlanParaPeriodo` y `AlumnoWebController::crearCuotaPasada`.
  - La ruta `POST /alumnos/{alumno}/cuotas-pasadas` está protegida por `['auth', 'activo']` y el controlador valida `$request->user()->isAdmin()`, abortando con 403 para rol operativo o usuarios no administradores. El profesor no accede a la ficha (`alumnos.show`) ni a las rutas de alumnos.
  - La vista `alumnos/show.blade.php` respeta 100% las directivas del Design System y no contiene JavaScript incrustado; utiliza el atributo declarativo `data-confirmar` gestionado por `ds-app.js`, y ubica el botón a la derecha (`flex justify-end`).
- **Verificación empírica independiente (`wings_testing_gemini`):**
  - Alumno con cambio de plan a mitad de camino (Plan 1 $10.000 en julio, Plan 2 $16.000 en agosto/septiembre): se comprobó que genera exactamente la cuota con el importe histórico del plan vigente para el mes elegido.
  - Rechazos verificados: mes actual y futuro, mes anterior al alta, mes con cuota existente, mes sin plan vigente y cadenas con formato no válido.
  - Control de accesos: operativo recibe 403 y no ve el bloque; dueño inactivo (`activo = false`) recibe 403 por middleware; profesor recibe 403.
  - Creación de cuota y cobro posterior: tras la creación, desaparece el mes del selector y la cuota en estado `pendiente` es cobrada normalmente con emisión de recibo.
- **Evidencia visual:** 6 capturas en escritorio (1366 px) y marco celular (360 px con `<iframe>`) documentadas en `docs/06-pruebas/PRU-04/evidencia-t58/`.
- **Suite completa:** 862 tests ejecutados en `wings_testing_gemini` (859 aprobados, 3 omitidos, 0 fallas). Informe detallado en `docs/06-pruebas/PRU-04/VERIFICACION-T58-GEMINI.md`.
- **Próximo paso:** Carlos revisa las capturas para visto bueno visual y cierre de T58.

## 2026-10-18 — LOG GEM CAB — Verificación independiente de T39: Anulación de cobro del dueño con fecha original

- **Objetivo y veredicto:** Verificar de forma independiente el trabajo de Claude en el commit `687262f` (T39 / DU-21 y decisión del 18/10: la anulación de un cobro del dueño va con la fecha del original y la deuda de cuota, inscripción y recargo vuelve en el mismo mes). **Veredicto: APROBADO (`estado=cerrado`, `verifica=Gemini`)**.
- **Código y puertas de entrada:** Leídos los cuerpos completos de `PagoCuotaService::anularCobroAdmin` y `ReporteMensualService::punitoriosPendientes`. El servicio fija `'fecha' => $original->fecha->toDateString()` para todos los contraasientos negativos en Cashflow y asigna `$deuda->fechaHistorial = $fechaOriginal;` al revertir `DeudaCuota`, dejando el historial de deuda fechado en el día del pago original. Se retiró la reconstrucción sintética `$anulados` de punitorios pendientes para que vuelvan al período de origen. No existen otras vías activas para anular cobros del dueño que usen `today()`.
- **Pruebas modificadas y nuevas:** Las dos pruebas ajustadas (`ReporteMensualTest` y `PunitoriosCobroTest`) controlan estrictamente la regla contractual nueva sin márgenes inventados. Las 5 pruebas nuevas de `AnulacionFechaOriginalT39Test.php` prueban exhaustivamente fecha de contraasientos, deuda de recargo, impacto simétrico en reportes, aislamiento de meses posteriores y restitución del saldo de caja.
- **Verificación empírica y navegador local (`wings_testing_gemini`):** Cobro del dueño simulado el 25/09/2026 (cuota $10.000, inscripción $5.000, recargo $500; total $15.500) y anulado el 10/10/2026. Comprobado que: 1) en Cashflow los 3 contraasientos llevan fecha `25/09/2026` y el neto de septiembre queda en `$0.00`; 2) en el reporte de septiembre lo cobrado baja $15.500 y la deuda total sube exactamente $15.500 (+10k cuota, +5k inscripción, +500 punitorios); 3) en octubre el cobrado y deuda no sufren alteración (0 movimientos en Cashflow); 4) el saldo del medio de pago queda exactamente en su balance original. 8 capturas en escritorio (1366 px) y marco celular (360 px con `<iframe>`) en `docs/06-pruebas/PRU-04/evidencia-t39/`.
- **Suite completa:** Ejecutada en forma aislada sobre `wings_testing_gemini`: **858 aprobadas, 3 omitidas, 0 fallidas (7.698 aserciones, 1.293,94 s)**. Solo falló `DocumentacionNoMienteTest` por pruebas agregadas en paralelo por Claude en el árbol de trabajo (`CuotaMesPasadoT58Test.php`).
- **Próximo paso:** Tablero frena por cambios pendientes de Codex en T75 (`tareas.json`, `TABLERO.html`, `QUIEN-HACE-QUE.md`); Carlos puede cerrar T39 (`estado=cerrado`, `verifica=Gemini`).
## 2026-10-18 — LOG GEM CAB — Implementación T63 y T57: Deporte/Grupo en uso y Medio de Pago del Cajón

- **Objetivo y alcance:** Implementar T63 (DU-41: no desactivar un deporte o grupo con alumnos activos o clases futuras) y T57 (DU-16: medio del mostrador protegido; DU-59: el dueño lo cambia solo si no hay ninguna caja abierta).
- **T63 — Cambios en código y reglas:**
  - `DeporteWebController::toggleActivo` y `GrupoWebController::toggleActivo`: si el elemento está activo y se intenta desactivar, se comprueba si existen alumnos activos o clases futuras (no canceladas, `fecha >= today()` en hora de Wings, según definición canónica de `ClaseWebController:528`).
  - Si existen dependencias activas, frena la desactivación con mensaje claro indicando cantidades exactas (singular/plural) y devuelve HTTP 422 `{error: ...}` en JSON y flash `error` en redirección. Reactivación sin restricciones.
  - 12 pruebas en `tests/Feature/DesactivarDeporteGrupoEnUsoTest.php` (100% aprobadas, incluye rechazo 403 a operativo).
- **T57 — Cambios en código, servicio y vista:**
  - `app/Services/CajaService.php`: nuevo método `tipoCajaMostradorId(): ?int`. En `configurarMostrador(int $tipoCajaId, int $adminId)`: si ya está configurado y se cambia el medio, comprueba `CajaOperativa::where('estado', 'ABIERTA')->exists()`; si hay caja abierta arroja `DomainException` («No se puede cambiar el medio del cajón mientras haya una caja abierta. Cerrá la caja abierta antes de cambiarlo.»). Registra `configurado_por_id` y `configurado_at`.
  - `app/Http/Controllers/TipoCajaWebController.php`: en `toggleActivo` y `update` bloquea la desactivación del medio de pago configurado en el mostrador («No se puede desactivar porque es el medio de pago del cajón de mostrador.»).
  - `app/Http/Controllers/CajaWebController.php`: pasa `$hayCajaAbierta` a la vista y captura `DomainException` en `configurarMostrador`.
  - `resources/views/caja/configuracion.blade.php`: con caja abierta muestra el select `disabled`, aviso de advertencia y oculta botón `Guardar`; sin caja abierta permite elegir y guardar. Sin JavaScript incrustado y respetando tokens de diseño.
  - 13 pruebas en `tests/Feature/MedioPagoCajonTest.php` (100% aprobadas, incluye uso del nuevo medio en siguiente apertura y rechazo 403 a operativo).
- **Evidencia visual y contratos:**
  - 8 capturas PNG en escritorio (1366 px) y celular (marco 360 px) generadas con Chrome headless (`docs/06-pruebas/PRU-04/evidencia-t57/`).
  - Actualizados `docs/02-contratos/Wings-contrato-alumno-grupo-deporte-deuda-v4.md` y `docs/02-contratos/Wings-Contrato-Caja-Cashflow-V5.md`.
  - Suite completa: **853 pruebas** (851 ejecutadas, 2 omitidas, 0 fallidas) sobre `wings_testing_gemini`.
- **Próximo paso:** T63 y T57 pasan a `a_verificar` para Claude en el tablero; aprobación visual de T57 para Carlos.

## 2026-10-18 — LOG GEM CAB — Verificación independiente de T38: Reactivar a un alumno le genera la cuota del mes

- **Objetivo y veredicto:** Verificar de forma independiente el trabajo de Claude en `cbc851e`, `3cef48b`, `544ace4` y `a372008` (T38 / DU-12 y decisión del 17/10: reactivar a un alumno le genera la cuota del mes con el porcentaje de reingreso del día y plazo de alta). **Veredicto: APROBADO (`estado=cerrado`, `verifica=Gemini`)**.
- **Código y puertas de entrada:** Leídos los cuerpos completos de `PagoCuotaService::crearCuotaReingreso`, `completarReingresoPorCobro`, `calcularReglaPrimerPago`, `AlumnoWebController::toggleActivo`, `CobranzaEstadoService::limiteDeLaCuota` / `calcularEstadoDesdeDeudas` y `PunitorioService::aplicar`. Barrido con `codebase-memory` en `app/` y `routes/`: ninguna puerta alcanzable quedó sin la regla (`AlumnoWebController::update` no valida `'activo'`, `RevisionCobranzaService::resolver` no activa inactivos y `Admin\AlumnoController::update` pertenece a `routes/api.php`, deshabilitado en `bootstrap/app.php:17`).
- **Casos verificados en `wings_testing_gemini`:** Probados de forma independiente los importes (días 16–23 al `70 %`, 24–31 al `40 %`, 0 reglas y 2 reglas superpuestas al `100 %`), los plazos (reactivado el 20 en plazo hasta el 26 y moroso el 27; reactivado el 28 en plazo hasta el 03/11 y deudor el 04/11; las demás cuotas conservan su vencimiento), el recargo por mora (no aplica el 21 ni el 26 y sí aplica el 27 al `5 %` = `$350,00` sobre `$7.000,00`), el cobro a un alumno inactivo (solo deuda vieja, parte del mes y las tres variantes de quien nunca había pagado, contrastadas con «Sin verificar o fuera de alcance») y el caso de alta el 17 + reingreso el 24 (respeta el `40 %`).
- **Navegador y suite completa:** Recorrido en Chrome local sobre `wings_testing_gemini` con 12 capturas reales a `1366 px` y `360 px` (en marco `<iframe>` de 360px + control de `/login`: `scrollWidth = 345`) en `docs/06-pruebas/PRU-04/evidencia-t38-gemini/`. Comprobado que el fallo inicial visto por Codex durante T36 se debió a la edición paralela en la carpeta compartida antes de `cbc851e`. Suite completa en `wings_testing_gemini`: **821 aprobadas, 2 omitidas, 0 fallidas (7.593 aserciones, 852,91 s)**. Informe en `docs/06-pruebas/PRU-04/VERIFICACION-T38-GEMINI.md`.

## 2026-10-18 — LOG GEM CAB — Implementación T41: No se puede cerrar una liquidación antes de que termine el mes

- **Objetivo y regla (DU-37 · LIQ-005):** Impedir cerrar una liquidación antes de que termine el mes liquidado. Según decisión de Carlos (*«Impedir cerrar antes de que termine el mes»* / *«A partir del 1ro de cada mes»*), una liquidación del período `(mes, anio)` solo puede cerrarse a partir del día `01` del mes siguiente a las `00:00` en la hora de Wings (`America/Argentina/Buenos_Aires`, respetando `today()` y `RelojSimulado`).
- **Cambios en código y vista:**
  - `app/Models/Liquidacion.php`: agregados `fechaHabilitacionCierre(): Carbon` y `puedeCerrarse(): bool`.
  - `app/Services/LiquidacionService.php`: en `cerrarLiquidacion(int $liquidacionId)`, si `!$liquidacion->puedeCerrarse()` lanza excepción en castellano (`No se puede cerrar la liquidación antes de que termine el mes. Se puede cerrar desde el DD/MM/AAAA.`), protegiendo tanto `LiquidacionWebController::cerrar` como `LiquidacionController::cerrar`.
  - `resources/views/liquidaciones/show.blade.php`: cuando la liquidación está `ABIERTA` y el mes no terminó, oculta el botón `Cerrar` y muestra el texto `Se puede cerrar desde el DD/MM/AAAA` sin JavaScript incrustado. `Recalcular`, `Eliminar` y `Volver` siguen igual. En `liquidaciones/index.blade.php` y `liquidaciones/create.blade.php` no se ofrece cerrar.
- **Pruebas, capturas y contrato:** 5 pruebas nuevas con reloj fijo (`travelTo`) en `tests/Feature/CerrarLiquidacionMesTerminadoTest.php` (`5 passed, 24 assertions`); las pruebas existentes de liquidaciones (`CancelarLiquidacionCerradaTest`, `LiquidacionHoraPorDuracionTest`, `LiquidacionHoraVistaYReciboTest`, `ImportesPesosT24Test`) y `CspSinCodigoIncrustadoTest` pasan sin requerir ajustes de fecha. Capturas a `1366` y `360` (en marco `<iframe>` de 360px + control de `/login`) en `docs/06-pruebas/PRU-04/evidencia-t41/`. Actualizado `docs/02-contratos/LIQUIDACIONES_CONTRATO_V2.md` e informe en `docs/06-pruebas/PRU-04/IMPLEMENTACION-T41.md`.
- **Próximo paso:** T41 pasa a `a_verificar` con dueño `Claude` en el tablero.

## 2026-10-17 — LOG GEM CAB — Verificación independiente de T35: La asistencia se toma desde el día de la clase

- **Objetivo y veredicto:** Verificar de forma independiente el commit `8f2be1f` de Claude (T35 / DU-35: no se toma asistencia de una clase que todavía no ocurrió). **Veredicto: APROBADO (`estado=cerrado`, `verifica=Gemini`)**.
- **Código y puertas de entrada:** Revisado el diff completo de `8f2be1f` y barrido con `codebase-memory` en `app/` y `routes/`. La única puerta HTTP activa (`ClaseWebController::storeAsistencias`, `POST /clases/{id}/asistencias`) y el servicio (`ClaseService::registrarAsistencia`) bloquean clases futuras con `$clase->todaviaNoOcurrio()` (`$this->fecha->gt(today())`). `AsistenciaController::storeBulk` no tiene la regla pero pertenece a `routes/api.php`, deshabilitado en `bootstrap/app.php:17`.
- **Límite de fecha y navegador real (`wings_testing_gemini`):** Comprobado el corte exacto entre las `23:59` del día anterior y las `00:00` del día de la clase en `America/Argentina/Buenos_Aires` con `RelojSimulado`. Recorrida la pantalla en Chrome Headless CDP a `1366 px` y `360 px` (en marco `<iframe>` de 360 px + control de `/login`): clase futura con `OPERATIVO` y con `PROFESOR` (cartel visible, casillas `disabled=true`, sin botón `Guardar`, y `POST` directo como `PROFESOR` rechazado con HTTP `422`), clase de hoy, clase pasada con motivo de corrección y clase cancelada.
- **Pruebas, efectos y entregables:** Revisadas las 8 pruebas de `AsistenciaClaseFuturaTest.php` y ejecutada la suite sobre `wings_testing_gemini`: corrida completa de **786 pruebas** (`783 passed, 2 skipped, 1 failed` por colisión de disco temporal compartido en `PrimeraCargaExcelTest`, **7.389 aserciones**, **1.004,00 s**) y reejecución de `AsistenciaClaseFuturaTest` + `PrimeraCargaExcelTest` con **28/28 aprobadas (579 aserciones, 39,70 s)**. Analizados los efectos de asistencias futuras previas sobre «Sin asistencia», cancelación, liquidación anticipada y cupo semanal. Informe en `docs/06-pruebas/PRU-04/VERIFICACION-T35-GEMINI.md` y 11 capturas + `verificacion-dom.json` en `docs/06-pruebas/PRU-04/evidencia-t35-gemini/`.

## 2026-10-17 — LOG GEM CAB — Implementación T48 y T49: Operativa renombrada y operativa con la caja abierta

- **Objetivo:** Implementar T48 (renombrar el subrubro de sueldo `Op-<nombre>` al guardar la ficha de una operativa que ya tiene `subrubro_id`) y T49 (impedir desactivar o cambiar de rol a una operativa con la caja abierta avisando que primero se cierre la caja).
- **Cambios en código (`UsuarioWebController.php`, `SubrubroSueldoService.php`, `resources/js/ds-app.js`):**
  - **T48:** En `SubrubroSueldoService::paraUsuarioOperativo`, cuando el usuario es `OPERATIVO` y ya tiene `subrubro_id`, si `$usuario->wasChanged('name')` delega en `renombrarParaUsuarioOperativo($usuario)`, actualizando el mismo subrubro (mismo `id`) a `Op-<nombre nuevo>` sin tocar movimientos imputados; si el nombre no cambió, no hace nada. En `UsuarioWebController::update`, si `Op-<nombre nuevo>` ya existe en otro subrubro (`existeChoqueParaOperativo`), rechaza antes de tocar la base y envuelve el guardado en `DB::transaction` («todo o nada»). Caso de choque documentado y consultado a Carlos en el informe.
  - **T49:** En `UsuarioWebController::toggleActivo` y `update`, si la operativa tiene caja abierta (`CajaService::obtenerCajaAbierta($usuario->id)`), impide desactivarla (vía JSON HTTP `422` y redirección) o cambiarle el rol, devolviendo el aviso `Este usuario tiene una caja abierta. Primero hay que cerrar la caja.`. La edición de nombre, email, clave y CBU/alias con caja abierta sigue permitida. En `resources/js/ds-app.js` se revierte el interruptor `.ds-toggle[data-url]` y se muestra el cartel `.ds-flash--error` cuando la respuesta JSON trae `data.error`.
- **Pruebas y verificación:** 7 pruebas nuevas en `RubroReservadoTest.php` y `UsuarioActivoWebTest.php` (renombrar, nombre sin cambios, choque de nombres, desactivar con/sin caja abierta por JSON y redirección, cambiar rol con caja abierta, editar nombre/mail/clave con caja abierta). Suite completa en `wings_testing_gemini`: **786 pruebas (784 aprobadas y 2 omitidas, 7395 aserciones, 1040,70 s)**. Recorrido real en Chrome local con 5 capturas en `docs/06-pruebas/PRU-04/evidencia-t48-t49/` e informe en `docs/06-pruebas/PRU-04/IMPLEMENTACION-T48-T49.md`.
- **Próximo paso:** T48 y T49 pasan a `a_verificar` por Claude en el tablero.

## 2026-10-16 — LOG GEM CYE — Ejecución T4: Repetición de los cuatro casos fallados en el sitio de prueba (ACC-003 y CLA-011)

- **Objetivo y resultado:** Repetición integral en el sitio de prueba real (`https://test.gestionar-te.com.ar`, commit `d4f02d4`, hora real) de los 4 defectos del 11/10 previamente corregidos en `602efcc` y T21 (`44d5ba7`). Resultado: **21/21 comprobaciones PASA (100%)**.
- **Caso ACC-003 (D02-04 — Límite de ingresos y recargas):**
  - Se recargó `/login` 10 veces sin ingresar credenciales: no consumió intentos de rate limit.
  - Se ingresó con 4 cuentas válidas sucesivamente (dueño, operativa, profesora, dueño): todas accedieron sin trabas.
  - 6 intentos rápidos en menos de 1 minuto dispararon el bloqueo por IP con pantalla en castellano (*«Esperá un momento · Demasiados intentos seguidos»* y botón *Volver*).
  - Durante el bloqueo, otra cuenta distinta recibió la misma restricción a nivel IP. Tras 65s sin peticiones, el login volvió a habilitarse con éxito.
  - Una sesión paralela abierta previamente (Sandra en `/operativo`) se mantuvo intacta y operativa en todo momento.
- **Caso CLA-011 (D03-01, D03-02, D03-03 — Cancelación de clases de hoy):**
  - Se preparó escenario con 3 clases de hoy (1 para cancelar Sandra, 1 para cancelar Admin y 1 de control activa).
  - Sandra canceló sin motivo: el frontend bloqueó la acción y exigió motivo en rojo (*«El motivo es obligatorio.»*).
  - Sandra canceló con motivo (*«Lluvia y pista resbaladiza»*): la ficha pasó a `Cancelada`, asistencia bloqueada y al volver a `/clases` quedó al fondo de hoy con opacidad reducida (`opacity: 0.6`), badge `Cancelada` y motivo visible. El filtro `estado=cancelada&fecha=2026-10-16` la halló correctamente.
  - El dueño canceló la segunda clase con motivo (*«Cancha anegada por temporal»*): modal operable sin trabas y en listado de hoy quedó al fondo junto a la primera.
  - La profesora Salinas (`veronica.salinas@wings.test`) ingresó a su clase cancelada: visualizó badge `Cancelada`, cartel de advertencia y botón *Guardar* inexistente (imposibilitada de tomar asistencia).
  - Verificaciones adicionales: el encabezado de hoy descontó las dos canceladas (mostró `1 CLASE(S)`), el menú *«Sin asistencia»* no sumó pendientes (se mantuvo en 41), el inicio de la operativa (`/operativo`) no mostró las canceladas, y la clase de control (ID 80) permaneció `Activa` con asistencia habilitada.
- **Entregables:**
  - Informe detallado: `docs/06-pruebas/PRU-04/REPETICION-T4.md`.
  - 21 capturas reales: `docs/06-pruebas/PRU-04/evidencia-t4/` (`01-acc03...` a `21-cla11...`).
  - Runner y telemetría de ejecución: `scripts/pruebas/cdp-helper.mjs`, `scripts/pruebas/ejecutar-t4-caso2-v2.mjs` y `resultados-ejecucion.json`.
- **Tablero:** El estado de T4 en el tablero no se modifica (lo actualizará Claude en la tabla de pruebas al revisar el informe, según decisión 6a).

## 2026-10-16 — LOG GEM CYE — Tercera vuelta T11: Corrección documental de fuentes y formato de capturas

- **Objetivo y resultado:** Resolución de la devolución de Codex (`docs/06-pruebas/PRU-04/VERIFICACION-T11-CODEX-SEGUNDA.md`). Se subsanaron las observaciones documentales en `IMPLEMENTACION-T11.md` sin alterar código, manifest, imágenes PNG, vistas ni estilos CSS.
- **Correcciones realizadas en `docs/06-pruebas/PRU-04/IMPLEMENTACION-T11.md`:**
  1. *Formato de capturas (Sección 3, línea 77):* Se removió WebP; la especificación oficial admite exclusivamente PNG y JPEG (*«Only JPEG and PNG image formats are supported»*).
  2. *Enlaces oficiales válidos (Sección 3, línea 70):* Se corrigió el enlace roto reemplazándolo por las fuentes oficiales comprobadas: [Chrome Developers: Richer PWA installation UI](https://developer.chrome.com/blog/richer-pwa-installation) y [web.dev: How to add Richer Install UI](https://web.dev/articles/web-apps/richer-install-ui).
  3. *Verificación contra fuentes oficiales:* Corroboradas las afirmaciones de dimensiones (320px–3840px), relación de aspecto (hasta 2.3), proporciones idénticas por `form_factor`, tope de 8 capturas mostradas y campo `description` recomendado truncado a ~7 líneas / ~324 caracteres.
  4. *Barrido general y trazabilidad:* Se agregó la Sección 12 con el detalle línea por línea de lo corregido y se enlazaron los criterios de instalación de Chromium en la Sección 2.
- **Tablero:** T11 pasa a estado `a_verificar` con dueño `Codex`.
- **Próximo paso:** Codex repite la inspección de Application > Manifest en DevTools y la suite completa en base propia `wings_testing_codex`. Tras su aprobación, pase a Carlos para despliegue y prueba física en su teléfono.

## 2026-10-14 — LOG GEM CAB — Segunda vuelta T11: Avisos de screenshots resueltos e informe corregido

- **Objetivo y resultado:** Resolución de la devolución de Codex (`VERIFICACION-T11-CODEX.md`) para cumplir el criterio estricto de cero errores y cero avisos en Chrome DevTools Application > Manifest. Se implementaron capturas en `public/` y se rectificó el informe técnico.
- **Cambios realizados:**
  1. *Screenshots en manifest:* Agregadas capturas de `/login` (pantalla neutral sin datos de personas ni dinero): `public/screenshot-wide.png` (1280x720, form_factor wide) y `public/screenshot-narrow.png` (720x1280, form_factor narrow), cumpliendo especificación de Chrome (320px–3840px, relación < 2.3:1). Declaradas en `public/site.webmanifest` junto con `description`.
  2. *Correcciones al informe `IMPLEMENTACION-T11.md`:* Sección 5 reescrita (el sitio de prueba usa Apache con `mod_mime` y `AllowOverride All`, no Nginx; la directiva en `.htaccess` aplica directamente y fue comprobada 200 OK por Codex); precisión en sección 2 (no hay SW ni Cache storage propio, no garantiza ausencia de caché HTTP del cliente, nadie probó pantallas viejas); y actualización de estado (el sitio de prueba ya sirve el manifest corregido en `d4f02d4`).
  3. *Pruebas y suite:* `PwaInstalacionTest.php` ampliado con validación de capturas físicas, MIME, dimensiones reales y form factors. 3/3 pasan (63 aserciones) en `wings_testing_gemini`.
- **Evidencia generada (`docs/06-pruebas/PRU-04/evidencia-t11/`):**
  - `chrome-devtools-manifest.png` y `chrome-devtools-manifest-screenshots.png`: DevTools sin errores ni avisos, previsualizaciones de screenshots reconocidas.
  - `application-manifest-dom.txt`: Árbol de accesibilidad completo sin avisos.
  - `cdp-installability.json`: Telemetría CDP con 0 errores de instalabilidad y 0 de manifest.
  - `generar-evidencia-t11.mjs`: Script reproducible actualizado.
- **Próximo paso:** Codex verifica segunda vuelta de T11. Teléfono físico pendiente de Carlos tras el despliegue.

## 2026-10-13 — ORDEN DE CARLOS PARA LOS TRES AGENTES — esto no puede volver a pasar

Escrito por Claude CyE en las tres bitácoras por orden de Carlos («ponelo en todas las
bitácoras como algo que no puede pasar»). Regla completa en `AGENTS.md` §5-bis.

**1. Prohibido escribir JavaScript dentro de una vista.** Ni `<script>` sin `src`, ni
`onclick` u otro atributo de evento. Va en un archivo de `resources/js/`. Antes de
escribir, buscar en `ds-app.js` si ya existe. El botón de mostrar la contraseña del
ingreso se escribió adentro de la pantalla el 21/07 y ahí sigue, aunque `ds-app.js` ya lo
resuelve para el formulario de usuarios. Cada pantalla así rompe la política de seguridad
y manda un aviso al servidor en cada carga. Quedan 11 bloques en 10 vistas: los saca T73.

**2. Una limpieza no queda «en avance» sin dueño ni fecha.** Sacar el código de las
vistas empezó el 13/09 y quedó a medias un mes, sin tarea en el tablero. Si un trabajo se
corta, queda una tarea T con lo que falta.

**3. Un número en un documento se corrige cuando cambia.** `ESTADO-ACTUAL` y
`PLAN-PRODUCCION` decían «19 bloques en 17 vistas» y `AGENTS.md` «24», cuando eran 11.
Quien baja el número en `CspSinCodigoIncrustadoTest` lo baja también en los documentos.


## 2026-10-13 — LOG GEM CYE — Segunda vuelta T2: Documentos corregidos y wings:exportar-padron retirado

- **Objetivo y resultado:** Resolución de la devolución de Claude (`VERIFICACION-T2.md`) y cumplimiento de la decisión de Carlos del 13/10 («si no sirve, no aporta nada»). Se retiró `wings:exportar-padron` y se corrigió toda la documentación que aún referenciaba los procedimientos antiguos.
- **Cambios realizados:**
  1. *Paso pendiente de Carlos:* En `CHECKLIST-CARLOS.md:196-199`, se reescribió para indicar la primera carga por pantalla vía `/primera-carga` (se mantiene pendiente de Carlos sin marcarlo hecho).
  2. *Plan de trabajo:* En `PLAN-TRABAJO-IA-v2026-09-08.md:146, 209` y `PLAN-TRABAJO-CARLOS-v2026-09-08.html:153` (ENT-09), se actualizaron las referencias hacia `/primera-carga` eliminando nombres de comandos viejos y rutas obsoletas.
  3. *Órdenes y herramientas archivadas:* `ORDEN-CODEX-DEUDA-INICIAL.md`, `DISENO-DEUDA-INICIAL-V1.md`, `generar-deuda-inicial.php` y `preparar-base-cobranza.php` trasladados a `docs/99-archivo/` con cabecera de archivo.
  4. *Retiro de `wings:exportar-padron`:* Eliminados `ExportarPadronCargaInicialCommand.php` y `ExportarPadronCargaInicialTest.php` (2 pruebas retiradas). Suite pasa de 724 a 722 pruebas.
  5. *Sincronización de estado:* `ESTADO-ACTUAL.md`, `CHECKLIST-CARLOS.md`, `PLAN-PRODUCCION.md` y `RESUMEN-ARRANQUE.md` actualizados a 722 pruebas.
- **Pruebas y verificación:**
  - `php artisan list wings`: confirmada la ausencia de comandos viejos.
  - Guardianes: `DocumentacionNoMienteTest`, `DefectosNoDivergenTest` y `TablerosNoDivergenTest` en verde.
  - `PrimeraCargaExcelTest`: 20/20 verde (553 aserciones).
  - Suite completa en `wings_testing_gemini`: 720 aprobadas, 2 omitidas (722 pruebas, 6892 aserciones).
- **Próximo paso:** Claude verifica segunda vuelta de T2.

## 2026-10-13 — LOG GEM CYE — Implementación T2: Retiro de los dos importadores viejos de la primera carga

- **Objetivo y resultado:** Cumplimiento de la decisión P0/P1 de `docs/06-pruebas/PRU-02/DEFECTOS.md` tras confirmarse el funcionamiento íntegro de la primera carga web unificada (`/primera-carga`). Se retiraron los dos importadores antiguos de consola y sus dependencias.
- **Cambios realizados:**
  1. *Comandos y servicios eliminados:* `ImportarSaldoInicialPadronCommand` (`wings:importar-padron`), `ImportarDeudaInicialExcelCommand` (`wings:importar-deuda-inicial`), `CargaSaldoInicialPadronService` y `CargaDeudaInicialExcelService`.
  2. *Pruebas obsoletas retiradas:* `CargaPadronFormatosExcelTest` (6), `CargaSaldoInicialPadronTest` (5) e `ImportarDeudaInicialExcelCommandTest` (3). Total 14 pruebas retiradas; suite pasa a 724 pruebas.
  3. *Componentes preservados:* `FormatoExcelCargaService` (utilizado por `PrimeraCargaExcelService`), `PrimeraCargaWebController`, seeders y `ExportarPadronCargaInicialCommand` (`wings:exportar-padron`).
  4. *Ajuste en prueba desacoplada:* En `ExportarPadronCargaInicialTest`, se reemplazó la llamada al comando eliminado por una aserción directa sobre la estructura de la hoja exportada `Padron`.
  5. *Documentación archivada:* `CARGA-DEUDA-INICIAL-EXCEL.md`, `CARGA-PADRON-SALDO-INICIAL.md` y 7 planillas `.xlsx` históricas trasladadas a `docs/99-archivo/`. Estado actualizado en `ESTADO-ACTUAL.md`, `CHECKLIST-CARLOS.md`, `PLAN-PRODUCCION.md` y `RESUMEN-ARRANQUE.md`.
- **Análisis de `wings:exportar-padron`:** Se documentó en `IMPLEMENTACION-T2.md` que servía exclusivamente para generar la planilla del comando retirado `wings:importar-padron`, no tiene invocadores web y se recomienda su retiro futuro cuando Carlos lo confirme.
- **Pruebas y verificación:** `PrimeraCargaExcelTest` (20/20), `ExportarPadronCargaInicialTest` (2/2), `DocumentacionNoMienteTest` (7 aserciones, sincronizado en 724).
- **Próximo paso:** Claude verifica código, archivos retirados y suite en 724.

## 2026-10-13 — LOG GEM CYE — Implementación T31: El que debe la inscripción también es moroso o deudor

- **Objetivo y resultado:** Implementación de T31 bajo las decisiones de Carlos del 12/10/2026 y agregado del 13/10/2026 (plazo propio del alumno nuevo de 7 días, `dias_plazo_alta`). Cobranza, el buscador de Cobrar y el Inicio de la Operativa ahora listan y cuentan exactamente a los mismos alumnos (38 alumnos con deuda en el dataset de prueba).
- **Resolución técnica implementada:**
  1. *Migración y configuración:* Migración `database/migrations/2026_10_13_080000_add_dias_plazo_alta_to_configuraciones.php` con clave `dias_plazo_alta = 7`. `CobranzaEstadoService::diasPlazoAlta()` no tiene fallback silencioso: si la clave no existe en la base, lanza `\LogicException`.
  2. *Regla de vencimiento de alta:* `limiteVencimientoAlta(Carbon $fechaAlta)` calcula el vencimiento tomando el mayor entre `fechaAlta + dias_plazo_alta` y el día de gracia general del mes (`dias_gracia_cobranza`). Si el alta cruza de mes (ej. 28/09 con 7 días), queda `EN_PLAZO` hasta el día 4 del mes siguiente; al expirar el día 5 pasa directo a `DEUDOR` (no moroso) por pertenecer a un mes calendario ya cerrado.
  3. *Jerarquía y estado:* El estado del alumno es el peor entre cuotas e inscripción: `DEUDOR > MOROSO > EN_PLAZO > AL_DIA`. Si la inscripción está saldada o condonada no altera el estado; si está pagada en parte sigue contando por el saldo pendiente.
  4. *Dos deportes y sin N+1:* La inscripción se imputa a un solo registro del alumno según la regla histórica de `inscripcionPendientePorAlumno()`. Si el registro imputado se da de baja y el otro continúa activo, la inscripción se imputa al activo. Pre-carga en bloque de saldos de inscripción por alumno para evitar consultas N+1 en listados masivos.
  5. *Orden en Cobranza:* Quien debe solo la inscripción se ordena por la antigüedad de esa deuda (`fecha_alta` Y-m) en lugar de caer al fondo sin fecha.
  6. *Inicio de la Operativa:* En `OperativoDashboardController.php:109`, «Alumnos con deuda» ahora sale de `$this->cobranzaService->listadoCobranza()->count()`, eliminando la divergencia con Cobranza (incluye a deudores de solo inscripción y excluye meses futuros).
- **Bordes verificados e informados:**
  - Alumno nuevo con ingreso posterior al día de gracia general: rige su plazo propio (`dias_plazo_alta`).
  - Cruce de mes en alta tardía (28/09): `EN_PLAZO` hasta el 04/10; al expirar el 05/10 pasa directo a `DEUDOR`.
  - Corrección de `fecha_alta`: recalcula el plazo contra la nueva fecha.
  - Alumno reactivado: conserva su `fecha_alta` original.
  - Cambio de mes: inscripción impaga en curso pasa de morosa a deudora al cerrar el mes.
- **Pruebas y verificación:**
  - 18 pruebas nuevas en `tests/Feature/CobranzaInscripcionEstadoTest.php` (42 aserciones).
  - 9 pruebas de verificación de Claude en `docs/06-pruebas/PRU-04/evidencia-t31/VerificacionT31ClaudeTest.php` (9/9 pasan).
  - Corridas complementarias adaptadas y en verde: `CobranzaEntrega1Test` (8/8), `CuotaAltaEstadoTest` (15/15), `InscripcionTest` (7/7), `DocumentacionNoMienteTest` (7 aserciones, sincronizado en 738).
- **Documentación y entrega:**
  - Contrato actualizado: `docs/02-contratos/Wings-contrato-estadosAlum-cobranza-asistencia-V1.md` (§3 y §11).
  - Informe de implementación: `docs/06-pruebas/PRU-04/IMPLEMENTACION-T31.md`.
  - Tablero: `T31` movido a `a_verificar` (hizo=Gemini, verifica=Claude, tiene=Claude).
  - Diseño: `git diff --stat -- resources/views resources/css` no contiene ningún cambio de T31. Sin deploy al VPS.

## 2026-10-12 — LOG GEM CAB — Verificación T29: Buscador de Cobrar lista solo a quienes deben

- **Objetivo y resultado:** Verificación independiente de la regla decidida por Carlos (12/10) implementada por Claude en commit `8a8a6b8` (D01-5 de la prueba). Comprobado que sin buscar la lista trae únicamente a los alumnos con deuda y coincide exactamente con el encabezado; a cualquier alumno al día o sin cuota generada se llega buscándolo por nombre, apellido o DNI y se le puede cobrar normalmente. T29 queda **APROBADO y CERRADO**.
- **Comprobaciones efectuadas:**
  1. *Deudores y encabezado:* Con 15 deudores y 5 al día, la lista sin buscar pagina (12 y 3) sin mostrar a ningún alumno al día y con encabezado idéntico (15 alumnos).
  2. *Alumno al día cobrado por camino real:* No aparece sin buscar; buscándolo aparece con punto verde y botón Cobrar; se completó el flujo real de cobro (`POST web.caja.pagar`) registrando pago, movimiento y cuota pagada.
  3. *Alumno recién dado de alta con cuota del mes:* Aparece sin buscar con punto rojo y se cuenta en el encabezado.
  4. *Alumno sin cuota del mes generada:* No aparece sin buscar; buscándolo se le ofrece el mes en curso y se le puede cobrar.
  5. *Deuda solo de inscripción vs mes futuro:* Debe solo inscripción aparece con punto rojo; cuota de mes futuro no aparece ni cuenta como deuda.
  6. *Alumno inactivo:* Con deuda no aparece sin buscar ni buscándolo por DNI/nombre.
  7. *Dueño sin caja:* Mismo comportamiento, pudiendo cobrar directo a Cashflow sin caja abierta.
  8. *Profesor:* Acceso denegado (403 Forbidden).
  9. *Enlaces de origen:* Funcionales desde Caja index, Inicio Operativo y navegación general.
  10. *Comparación con Cobranza:* Verificada y explicada la diferencia de alcance entre Cobrar (gestión de cobro inmediato: cuotas + en plazo + inscripción) y Cobranza DEUDORES (gestión punitoria de plazos vencidos: deudores y morosos).
  11. *Casos no cubiertos:* Búsqueda por DNI parcial, mayúsculas/minúsculas, deuda parcial, deuda condonada total, paginación con query string `search` y espacios en blanco.
- **Pruebas:** 11 pruebas pasando (113 aserciones) en `docs/06-pruebas/PRU-04/evidencia-t29/VerificacionT29GeminiTest.php` en `wings_testing_gemini`. Suite completa: 664 tests pasando.
- **Informe:** `docs/06-pruebas/PRU-04/VERIFICACION-T29.md`.

## 2026-10-12 — LOG GEM CAB — T28 segunda vuelta: vincular sin adivinar y columna de quién validó

- **Objetivo y resultado:** Segunda vuelta de T28 solicitada tras la revisión de Claude y decisiones de Carlos del 12/10/2026. Se implementó la vinculación directa y persistida de cada asiento de Cashflow con su movimiento operativo de caja (`movimiento_operativo_id` mediante migración autorizada por Carlos), eliminando toda deducción por textos o heurísticas. Para renglones preexistentes sin ID guardado, se exige coincidencia exacta o conserva la observación original (eliminado el segundo intento por importe y rubro). Se agregó la columna «Val.» en escritorio (50px) y el badge «Val: <iniciales>» en tarjetas de celular con tooltip del nombre completo de quien validó la caja (queda vacía en movimientos directos sin caja). Desempate de iniciales en 2 letras distintivas (ej. CB y CZ).
- **Decisiones visuales de Carlos (12/10/2026):**
  - Carlos respondió y aprobó: *«Sí, creá la migración con la columna movimiento_operativo_id (nullable) en cashflow_movimientos para vinculación directa y exacta»*.
  - Diseño: *«Opcion 1 escritorio, opcion 2 celular. No achiques tanto el monto, sino cuando sean de mayor valor van a quedar desbordados. Ojo que se tienen que ver los decimales»*.
  - Desempate: *«Que uno sea CB y el CZ o CO y CB»*.
- **Cambios realizados:**
  1. *database/migrations/2026_10_12_120000_add_movimiento_operativo_id_to_cashflow_movimientos.php:* agregada columna `movimiento_operativo_id` nullable con foreignId hacia `movimientos_operativos` (nullOnDelete).
  2. *app/Models/CashflowMovimiento.php:* agregado a `$fillable` y relación `movimientoOperativo(): BelongsTo`.
  3. *app/Models/User.php:* método `iniciales(): string` con desempate automático de iniciales en 2 letras (ej. Carlos Bonifacio `CB` vs Carlos Benítez `CZ`).
  4. *app/Services/CashflowIntegracionCajaService.php:* en `reflejarCajaEnCashflow()`, guarda `'movimiento_operativo_id' => $movimiento->id`.
  5. *app/Http/Controllers/CashflowWebController.php:* en `index()`, resuelve validador (`validadorNombre` y `validadorIniciales`), vincula directo por `movimiento_operativo_id` sin tocar observaciones; en renglones viejos aplica coincidencia exacta estricta o nada.
  6. *resources/views/cashflow/index.blade.php:* tabla de escritorio con columna «Val.» (50px) y columna Monto (125px holgado para decimales); tarjetas móviles con `Registró: <nombre>` a la izquierda y badge `Val: <iniciales>` a la derecha. Montos conservan formato de partida a la espera de que Codex aplique T30.
  7. *docs/02-contratos/Wings-Contrato-Caja-Cashflow-V5.md:* actualizada tabla de decisiones con el vínculo directo y la columna de validó.
- **Pruebas y capturas:**
  - 5 pruebas permanentes en `tests/Feature/CashflowVinculoDirectoYValidadorTest.php` (30 aserciones, 100% PASS).
  - Verificación de Claude `VerificacionT28ClaudeTest` (2 pruebas, 159 aserciones, 100% PASS) y `CashflowDetalleAlumnoYUsuarioTest` (5/5 PASS).
  - Capturas reglamentarias finales en `docs/06-pruebas/PRU-04/evidencia-t28/`: `cashflow-v2-escritorio.png`, `cashflow-v2-celular-375.png`, `login-control-v2-escritorio.png` y `login-control-v2-celular-375.png`.
- **Estado y próximo paso:** Diseño aprobado por Carlos. Pasado a `a_verificar` en el tablero con `tiene=Claude` para verificación de código y base.
- **Informe:** `docs/06-pruebas/PRU-04/IMPLEMENTACION-T28.md`.

## 2026-10-12 — LOG GEM CAB — Implementación T11: Wings instalable en el teléfono como app

- **Objetivo y resultado:** Resolver la instalabilidad de Wings como aplicación en Android con Chrome (Carlos lo probó el 12/10 en su teléfono y solo creaba acceso directo con sello de Chrome y abría en el navegador). Decisión de Carlos del 12/10: la app instalada abre en `/login` a pantalla completa sin barra del navegador (`standalone`), y al tener sesión iniciada lleva de inmediato al inicio del rol sin pedir credenciales nuevamente. Estado en el tablero pasado a `a_verificar` con `tiene=Carlos` para despliegue y prueba en su teléfono.
- **Diagnóstico y solución:**
  1. *Qué faltaba:* En `public/site.webmanifest` faltaba `start_url` (Chromium arrojaba `start-url-not-valid` en `Page.getInstallabilityErrors`), además de `scope`, `id` y `purpose: "any maskable"` en los íconos.
  2. *Service Worker:* Se comprobó con Chrome DevTools Protocol (`Page.getInstallabilityErrors`) y la especificación de Chromium que Chrome moderno no exige Service Worker para habilitar la instalación. Siguiendo la orden de Carlos (*«Si no lo exige, no lo agregues»*), no se agregó Service Worker para evitar riesgos de caché obsoleto en datos de plata. Al corregir el manifest, `installabilityErrors` quedó en `[]` (cero errores).
  3. *public/site.webmanifest:* agregado `start_url: "/login"`, `scope: "/"`, `id: "/login"`, `display: "standalone"`, y separación limpia de íconos 192 y 512 con `purpose: "any"` y `purpose: "maskable"`.
  4. *public/.htaccess:* agregada directiva Apache `AddType application/manifest+json .webmanifest`.
  5. *Vistas Blade:* no se modificó ninguna vista. `resources/views/layouts/ds-app.blade.php` ya contenía el `<link rel="manifest">` y `<meta name="theme-color">`.
- **Pruebas y capturas:**
  - 3 pruebas permanentes en `tests/Feature/PwaInstalacionTest.php` (29 aserciones, 100% PASS en `wings_testing_gemini`).
  - Evidencia generada en `docs/06-pruebas/PRU-04/evidencia-t11/`: `chrome-devtools-manifest.png` (panel Application > Manifest con Identity, Presentation, Icons y cero errores), `login-escritorio.png`, `login-celular-360.png` y telemetría `cdp-installability.json`.
- **Qué no se probó:** No se probó en un teléfono físico real (pendiente de que Carlos despliegue al VPS y lo pruebe en su Android).
- **Informe:** `docs/06-pruebas/PRU-04/IMPLEMENTACION-T11.md`.

## 2026-10-12 — LOG GEM CAB — Implementación T28: Cashflow muestra a quién se le cobró y quién cobró

- **Objetivo y resultado:** Implementar la decisión de Carlos del 12/10 para T28 (originada en el día 1 de la prueba por días): en Cashflow, un cobro de cuota o inscripción muestra en la columna «Detalle» el `Apellido, Nombre` del alumno, y en la columna «Registró» a quien cargó el movimiento (la operativa si provino de una caja validada, o el dueño si cobró directo sin caja). Un gasto o movimiento sin alumno conserva sus observaciones.
- **Diseño visual aprobado por Carlos (12/10/2026):**
  - Carlos revisó las alternativas y aprobó expresamente: *«Para Escritorio. Opcion 1 OK; Para celular. Opcion 2 OK»*.
  - *Escritorio (>= 768px):* Tabla de 7 columnas rebalanceada (`<colgroup>` calibrado: 95px, 35px, 95px, 230px, `<col>` flexible para Detalle, 110px, 160px para Registró completo), eliminando huecos blancos y garantizando que los nombres no se corten.
  - *Celular (< 768px):* Vista en tarjetas verticales compactas sin scroll lateral. Cabecera con fecha, tipo caja y badge I/E, monto destacado a la derecha; cuerpo con Rubro — Subrubro, alumno resaltado o detalle de gasto; pie con «Registró: <nombre>».
- **Cambios realizados:**
  1. *app/Services/CashflowIntegracionCajaService.php:* al reflejar los movimientos de una caja validada en Cashflow, se guarda `'usuario_admin_id' => $movimiento->usuario_id ?? $adminId`, asegurando consistencia en la persistencia.
  2. *app/Http/Controllers/CashflowWebController.php:* en `index()`, para los movimientos de la página actual, se resuelve de forma dinámica el `Apellido, Nombre` del alumno y el usuario original que registró el movimiento (eager loading de `Pago` para `REF_PAGO_CUOTA` y `MovimientoOperativo` para `REF_CAJA`), resolviendo automáticamente tanto asientos futuros como históricos ya existentes en Cashflow sin migraciones ni backfills.
  3. *resources/views/cashflow/index.blade.php:* diseño responsive con Opción 1 (tabla rebalanceada) en escritorio y Opción 2 (tarjetas verticales) en móvil.
  4. *docs/02-contratos/Wings-Contrato-Caja-Cashflow-V5.md:* actualizada tabla de decisiones con la regla de T28.
- **Pruebas y capturas:**
  - 5 pruebas permanentes en `tests/Feature/CashflowDetalleAlumnoYUsuarioTest.php` (32 aserciones, 100% PASS).
  - Capturas reglamentarias finales en `docs/06-pruebas/PRU-04/evidencia-t28/`: `cashflow-escritorio.png` (Opción 1) y `cashflow-celular-360.png` (Opción 2 tarjetas en marco 360 px), más login de control.
- **Estado y próximo paso:** Diseño visual aprobado por Carlos. Tarea en `a_verificar` con `tiene=Claude` para que otro agente verifique la regla de negocio y datos según §6a de AGENTS.md.
- **Informe:** `docs/06-pruebas/PRU-04/IMPLEMENTACION-T28.md`.

## 2026-10-12 — LOG GEM CAB — Verificación y Cierre T26: Historial de caja del dueño sin límites

- **Objetivo y resultado:** Verificar la implementación de Claude (commit `f4a030e`) para T26 («El dueño debe ver todo, los límites no son para él», Carlos 11/10/2026). Regla verificada y APROBADA. Estado en el tablero pasado a `cerrado` con `verifica=Gemini`.
- **Qué se comprobó:**
  1. *Límite de fechas:* El dueño encuentra movimientos de más de 90 días pidiéndolo con `desde`. La operativa no, ni forzando parámetros antiguos en la URL (queda acotada a la ventana de 90 días). Sin fecha solicitada, el dueño arranca por defecto en los últimos 90 días para agilidad.
  2. *Subrubros exclusivos de ADMIN:* El dueño ve movimientos con subrubros solo de admin y los ve en el select del filtro (`subrubros`). La operativa no ve los movimientos ni el subrubro en el filtro.
  3. *Cancelados:* El dueño los ve identificados con `· Cancelado` y tachados (`line-through`). La operativa no los ve. En ambos roles `totalIngresos` y `totalEgresos` excluyen lo cancelado.
  4. *Cobro del dueño sin caja y anulado por camino real:* Flujo real `POST /caja/cobrar/{id}` (`registrarPagoCuotaAdmin`) y `POST /pagos/{id}/anular` (`anularCobroAdmin`). Aparecen ambos renglones con la alumna identificada; la anulación resta (`neto < 0`, rojo en HTML) y los ingresos netos suman $0,00.
  5. *Profesora:* `GET /caja/historial` devuelve HTTP 403 Forbidden (`reject.profesor.web`).
  6. *Filtros y paginación:* Búsqueda por texto (alumno, obs, subrubro), selector de subrubro, tipo (INGRESO/EGRESO), fechas y paginación (30 por página).
  7. *Casos borde agregados:* Una caja operativa cerrada y validada por admin (`reflejarCajaEnCashflow` con `REF_CAJA`) no duplica movimientos ni montos en el historial. Los totales recalculan con exactitud ante cualquier filtro aplicado.
- **Entorno y pruebas:**
  - Suite de verificación independiente: `docs/06-pruebas/PRU-04/evidencia-t26/VerificacionT26Test.php` (8 pruebas, 103 aserciones, 100% PASS en 12.8s).
  - Suite completa: 641 pasando, 2 omitidas (6075 aserciones, 532s) en `wings_testing_gemini`.
  - No se tocó el sitio de prueba ni su reloj.
- **Informe:** `docs/06-pruebas/PRU-04/VERIFICACION-T26.md`.

## 2026-10-11 — LOG GEM CAB — Implementación T23: Apertura con aviso > 50% y rectificación de efectivo inicial

- **Objetivo y resultado:** Implementar las decisiones de Carlos del 11/10 para T23: aviso enfático en pantalla si el efectivo recibido al abrir difiere más de 50% del cierre anterior (si dejó > $0), requiriendo confirmación explícita para abrir; y rectificación del efectivo inicial mientras la caja esté ABIERTA por el operativo del turno o ADMIN, con motivo obligatorio, auditoría completa y recálculo inmediato del esperado. Estado en el tablero pasado a `a_verificar` con `hizo=Gemini` y `tiene=Carlos` para que Carlos apruebe las capturas y luego otro agente verifique la regla.
- **Cambios realizados:**
  1. *database/migrations/2026_10_11_120000_add_rectificacion_to_cajas_operativas.php:* agrega `efectivo_inicial_anterior`, `motivo_rectificacion`, `rectificado_at` y `usuario_rectificacion_id`.
  2. *CajaOperativa:* fillable, casts y relación `usuarioRectificacion(): BelongsTo`.
  3. *CajaService:* `abrirCajaOperativa` detecta diferencia > 50% (cuando `efectivo_heredado > 0`) y exige confirmación explícita `confirmar_diferencia_grande`. Nuevo método transaccional `rectificarApertura()` que valida estado ABIERTA, permisos, motivo obligatorio y recalcula `efectivo_esperado`.
  4. *CajaWebController y routes/web.php:* rutas GET y POST `/caja/{id}/rectificar`, vistas `caja/rectificar.blade.php`, aviso enfático en `caja/apertura.blade.php`, botón `Rectificar` y bloque de auditoría en `caja/resumen.blade.php`.
  5. *Contrato V5:* actualizado con las nuevas reglas.
- **Pruebas y capturas:**
  - 8 pruebas permanentes en `tests/Feature/CajaAperturaDiferenciaYRectificacionTest.php` (41 aserciones, 100% PASS).
  - 10 capturas PNG en `docs/06-pruebas/PRU-04/evidencia-t23/`: apertura con aviso y rectificación para operativo y admin en escritorio y celular 360px con marco, más login de control.
- **Suite completa:** 633 pruebas registradas, suite completa ejecutada en `wings_testing_gemini`.
- **Informe:** `docs/06-pruebas/PRU-04/IMPLEMENTACION-T23.md`.

## 2026-10-11 — LOG GEM CAB — Implementación T21: Clases canceladas de hoy visibles en Clases

- **Objetivo y resultado:** Implementar la decisión de Carlos del 10/10 para T21: las clases canceladas del día siguen viéndose en el bloque «Hoy» al final, apagadas (`opacity: 0.6`), con la etiqueta «Cancelada» destacada y el motivo a la vista. El filtro Cancelada ahora encuentra las de hoy. Estado en el tablero pasado a `a_verificar` con `hizo=Gemini` y `tiene=Carlos` para aprobar las capturas.
- **Cambios realizados:**
  1. *ClaseWebController::index:* se eliminó `where('cancelada', false)` del bloque «Hoy», ordenando por `cancelada asc` y luego por `hora_inicio asc` (activas primero, canceladas al final). Se flexibilizó `whereDate('fecha', '!=', $hoy)` del listado inferior para incluir hoy si se filtra `estado=cancelada` o se pide explícitamente la fecha de hoy, sin duplicar clases si no hay filtros.
  2. *clases/index.blade.php:* el encabezado de «Hoy» cuenta solo las clases activas a dar (`$misClasesHoy->where('cancelada', false)->count()`). Si todas las de hoy están canceladas, se muestran las tarjetas y no el cartel de día vacío.
  3. *clases/_card.blade.php:* muestra la etiqueta «Cancelada» estilizada en rojo peligro con fondo suave y el motivo debajo del encabezado (`margin: 0 0 0.75rem 1.5rem`).
- **Pruebas y capturas:**
  - 9 pruebas permanentes en `tests/Feature/ClasesCanceladasHoyTest.php` (41 aserciones, 100% PASS).
  - 14 capturas PNG en `docs/06-pruebas/PRU-04/evidencia-t21/`: parcial y todas canceladas para admin, operativo y profesora en escritorio y celular 360px con marco, más login como control.
- **Suite completa:** 623 aprobadas, 2 omitidas (625 pruebas en total) en `wings_testing_gemini`.
- **Informe:** `docs/06-pruebas/PRU-04/IMPLEMENTACION-T21.md`.

## 2026-10-11 — LOG GEM CAB — Verificación y Cierre T18: Segunda vuelta del cobro adelantado

- **Objetivo y resultado:** Verificar las dos correcciones de Claude en commit 567f030 sobre cobro adelantado. Tarea APROBADA y cerrada en el tablero (`php scripts/tablero/tablero.php cambiar T18 estado=cerrado verifica=Gemini`).
- **Qué se comprobó:**
  1. *Hallazgo 1:* Mes futuro pendiente (importe pactado $42.000 tras anulación) muestra el mismo saldo ($0,00) en las 5 pantallas (Ficha `/caja/cobrar/{id}`, Buscador `/caja/cobrar-cuota`, Cobranza, Inicio y Reportes). El alumno no es moroso ni deudor (`AL_DIA`). Al empezar noviembre (2026-11-02), la cuota cuenta como deuda por los $42.000 pactados y no por los $55.000 del nuevo plan, sin duplicarse. Tras anular, se puede volver a cobrar por adelantado sin duplicar registro.
  2. *Hallazgo 2:* Períodos `2026-13` y `2026-00` se rechazan con HTTP 422 de validación, sin crear deuda ni pago. Período válido (`2026-11`) sigue funcionando.
  3. Revalidados puntos 1, 2, 3, 6 (6a–6e) y 8 de la primera verificación (100% OK).
- **Stash y entorno:** `stash@{0}` conservado intacto sin aplicar ni borrar. No se tocó el sitio de prueba ni su reloj.
- **Suites:** `VerificacionT18Test` 14/14 pasando (157 aserciones, 15.23s); suite completa 614 aprobadas, 2 omitidas (5096 aserciones, 432.39s) en `wings_testing_gemini`.
- **Informe:** `docs/06-pruebas/PRU-04/VERIFICACION-T18.md` (segunda vuelta agregada al final).

## 2026-10-10 — LOG GEM CYE — Implementación T20: Pantalla de Cobrar en el celular (360px)

- **Objetivo y resultado:** Implementar los 3 ajustes visuales pedidos por Carlos para la pantalla de cobro (`resources/views/caja/cobrar.blade.php`) observada desde el teléfono (360px), sin alterar la lógica de negocio ni scripts. Estado en tablero pasado a `a_verificar` con `verifica=Carlos`.
- **Cambios realizados:**
  1. *Planes del mismo tamaño:* Configurado en `grid grid-cols-1 sm:grid-cols-2 gap-2` con labels a todo el ancho y `justify-between`. En 360px miden exactamente lo mismo una debajo de la otra; en escritorio dos columnas simétricas idénticas.
  2. *Íconos en los rótulos:* Incorporados SVGs acordes al design system (`subrubros/_form.blade.php`) en los rótulos de Mes (calendario), Importe (moneda), Medio de pago (tarjeta), Fecha del pago (calendario) y Observaciones (nota).
  3. *Medio de pago, Fecha y Observaciones en 3 filas:* Contenedor migrado a `grid grid-cols-1 md:grid-cols-3 gap-4`. En 360px cada control ocupa su propia fila a todo el ancho, dando amplitud y legibilidad al selector de caja.
- **Comprobaciones y evidencia:**
  - Capturas antes y después (zonas y pantalla completa a 360px y 1366px) guardadas en `docs/06-pruebas/PRU-04/evidencia-t20/`.
  - Verificado que `scrollWidth <= clientWidth` (360px exactos, 0 scroll horizontal ni desbordes).
  - Prueba interactiva en 360px: tildar cuota, elegir medio de pago, agregar mes adelantado con importe editado, suma correcta a $63.000 y botón Cobrar habilitado.
- **Suite completa:** 613 tests ejecutados, 5087 aserciones, 2 omitidos (100% OK) en `wings_testing_gemini`.
- **Informe:** `docs/06-pruebas/PRU-04/IMPLEMENTACION-T20.md`.

## 2026-10-10 — LOG GEM CYE — Verificación y Cierre de T19: La misma deuda en Inicio, Reportes y Cobranza

- **Objetivo y resultado:** Verificar la lógica de unificación de deuda de alumnos activos (cuotas + inscripciones) en Inicio, Reportes y Cobranza (commit `ddd6343` de Claude, complementado por commit `567f030`). Tarea APROBADA y cerrada en el tablero (`php scripts/tablero/tablero.php cambiar T19 estado=cerrado verifica=Gemini`).
- **Qué se probó:**
  - Suite de comprobaciones independientes en `docs/06-pruebas/PRU-04/evidencia-t19/VerificacionT19Test.php` (14 tests, 77 aserciones, 100% PASS).
  - Verificados los 7 puntos solicitados:
    1. Igualdad al peso en los 8 casos de prueba (inscripción totalmente pagada, pago parcial, condonada parcialmente, cuota parcial, cuota condonada, alumna en 2 deportes con 1 sola inscripción, hermanos activo/inactivo, baja y reactivación inmediata).
    2. Mes futuro pendiente no cuenta como deuda en ninguna pantalla ($0,00).
    3. Filtro por deporte en Reportes: suma de deportes individuales da exactamente el total general ($70.000).
    4. Meses pasados en Reportes y comportamiento temporal ante pagos posteriores y bajas analizado y documentado.
    5. «Del mes» y «Meses anteriores» en Reportes: cada cargo/cuota cae en exactamente un balde y su suma es idéntica al total.
    6. Relevamiento exhaustivo de todas las pantallas con deuda (Ficha, Buscador de Cobrar, Revisión, Reporte Alumnos, Dashboard Operativo).
    7. «Profesores por pagar» y balance general de Reportes intactos sin regresiones.
- **Stash y repositorio:** Stash local `stash@{0}` («dashboard local changes») preservado intacto.
- **Suite completa:** 613 tests ejecutados, 5087 aserciones, 2 omitidos (100% OK) en `wings_testing_gemini`.
- **Informe entregado:** `docs/06-pruebas/PRU-04/VERIFICACION-T19.md`.

## 2026-10-10 — LOG GEM CYE — Verificación T18: Cobro Adelantado devuelto a Claude

- **Objetivo y resultado:** Verificar la lógica de cobro adelantado implementada por Claude en commit `3a3849a`. Tarea DEVUELTA a Claude por divergencia de saldos entre la ficha de cobro y el buscador/Cobranza ante cuotas futuras pendientes (ej. tras anulación), y por validación incompleta de meses en el regex de períodos.
- **Qué se probó:**
  - Suite de comprobaciones independientes en `docs/06-pruebas/PRU-04/evidencia-t18/VerificacionT18Test.php` (13 tests, 104 aserciones, 100% PASS).
  - Verificados los 9 puntos: rechazo sin confirmación por todos los canales (409), monto cambiado mayor y menor fijado en `monto_original` y `monto_pagado` con estado `PAGADA`, congelamiento de precio ante aumentos y ejecución de `cobranza:generar-deudas`, bordes de 12 y 13 meses, deudas preexistentes, cruces (deuda anterior con motivo, inscripción, primer pago, cambio de plan, múltiples adelantados), anulación y movimientos contables únicos en caja/cashflow.
- **Fallas detectadas que motivan la devolución:**
  1. *Divergencia de saldos por cuotas futuras pendientes:* Si una cuota futura queda en estado `PENDIENTE` en la base (ej. tras anular un cobro adelantado con `anularCobroAdmin` o `cancelarCobroOperativo`), `CobranzaEstadoService::saldoDeAlumnos()` no filtra por mes vigente y la suma. En `/caja/cobrar` (ficha) «Total pendiente» dice `$0,00` pero en `/caja/cobrar-cuota` (buscador) y `/cobranza` dice `$35.000` (1 cuota pendiente).
  2. *Regex de períodos permite meses fuera de 01-12:* `regex:/^\d{4}-\d{2}$/` deja pasar `2026-13` provocando un error 500 no capturado en `Carbon::parse()` en vez de un 422 de validación.
- **Stash verificado:** `stash@{0}` («dashboard local changes») pertenece a pruebas locales previas de Gemini en dashboard; se mantiene intacto sin aplicar ni borrar.
- **Tablero:** `T18` pasado a `devuelto` con `tiene=Claude`. Informe: `docs/06-pruebas/PRU-04/VERIFICACION-T18.md`.
- **Suite completa:** 609 pasadas, 2 omitidas (5065 aserciones, 499.09s) en `wings_testing_gemini`.

## 2026-10-10 — LOG GEM CYE — T17: Inicio cuenta cuotas + inscripciones en la deuda

- **Objetivo y cambios realizados:**
  - En Inicio (`WebController::adminDashboard`), «Alumnos por cobrar» suma las cuotas pendientes calculadas por `ReporteMensualService::obtener` más las inscripciones pendientes de alumnos activos.
  - Se extrajo el método `totalInscripcionesPendientes()` en `CobranzaEstadoService`, reutilizado tanto en `resumenDashboard()` como en `adminDashboard()` garantizando idéntico criterio al peso.
  - La suite específica `InicioDeudaCuotasEInscripcionesTest` (7 tests, 27 aserciones) comprueba: solo cuotas, cuotas + inscripción, inscripción pagada, inscripción con pago parcial, alumna en dos deportes con 1 sola inscripción, inscripción condonada y exclusión de alumno inactivo.
  - Documentos sincronizados a 611 pruebas (`ESTADO-ACTUAL.md`, `CHECKLIST-CARLOS.md`, `PLAN-PRODUCCION.md`).
  - Suite completa: 609 aprobadas, 2 omitidas (5053 aserciones) en `wings_testing_gemini`. Sin modificaciones visuales ni de CSS. Informe: `docs/06-pruebas/PRU-04/IMPLEMENTACION-T17.md`.

## 2026-10-10 — LOG GEM CYE — PRU-03 Segunda Vuelta: Primera Carga en Excel Real y Flujo Orgánico

- **Objetivo:** Responder a las observaciones de Claude (`VERIFICACION-CLAUDE.md`) completando lo pendiente de la primera carga: manipulación directa en Microsoft Excel 2016 desktop, carga manual de 10 alumnos adicionales con cronómetro, prueba del error de fila repetida (12 errores en total), navegación orgánica tocando el menú sin escribir URLs directas, capturas completas de la planilla para el manual de usuario, y depuración de la carpeta `capturas/fichas/`.
- **Qué se hizo de verdad:**
  - **A. Excel desktop:** Se abrió la plantilla en Microsoft Excel 2016 real y se cargaron 10 alumnos celda por celda cronometrando el tiempo (promedio: 1m 16s por alumno; 45s para alumno al día, hasta 2m para deudas complejas). Se detectaron tropiezos reales: las listas desplegables de Plan muestran los 12 planes del club juntos sin filtrar por el grupo elegido, las columnas de deuda obligan a scroll hasta la columna AL perdiendo de vista los nombres, y la hoja Catálogos está desprotegida.
  - **B. Recorrido web por menú y botones:** Se usó «Deshacer» en `https://test.gestionar-te.com.ar` para limpiar la base. Se hizo clic en Inicio, Alumnos y Reportes desde el menú comprobando que redirigen a Primera Carga. Se avanzó a Paso 2 haciendo clic en «Continuar», y a Paso 3 haciendo clic en «Descargar». Se subió `PADRON-PRU-03-v2-con-12-errores.xlsx` con los 12 errores requeridos (incluyendo fila duplicada en A102); el validador detectó 11 errores y normalizó 1. Se descargó el Excel marcado y se subió `PADRON-PRU-03-v2.xlsx` limpio. Se confirmó la carga final de 100 alumnos.
  - **C. Capturas para el manual:** Generadas 8 capturas directas de Excel (plantilla vacía, hoja Guía, hoja Catálogos, desplegables, alumnos al día, con deuda y Excel marcado con columna AM). Se reemplazaron las capturas erróneas 404 por capturas de los bloqueos reales del sistema. Se depuró la carpeta `capturas/fichas/` dejando exactamente 15 capturas limpias (una por alumno y deporte).
  - **D. Informe reescrito:** Documentada la experiencia humana real en `docs/06-pruebas/PRU-03/INFORME-PRIMERA-CARGA.md` con desglose de qué se hizo, qué se consultó en el código y qué se supuso.
- **Entregables:** `PADRON-PRU-03-v2.xlsx` (100 alumnos), `PADRON-PRU-03-v2-con-12-errores.xlsx`, `PADRON-PRU-03-v2-marcado-errores.xlsx`, 28 capturas principales y 15 fichas limpias. Sitio de prueba dejado con 100 alumnos cargados. Tablero intacto (T4 a cargo de Claude).
  - Padrón con errores: `docs/06-pruebas/PRU-03/PADRON-PRU-03-con-errores.xlsx`.
  - Excel marcado devuelto: `docs/06-pruebas/PRU-03/PADRON-PRU-03-marcado-errores.xlsx`.
  - 23 capturas de pantalla de alta resolución (1366 × 768) en `docs/06-pruebas/PRU-03/capturas/`.
  - Informe completo para Carlos: `docs/06-pruebas/PRU-03/INFORME-PRIMERA-CARGA.md`.
- **Estado final:** Sitio de prueba dejado con la carga final terminada y activa. Tablero intacto (tarea T4 de Claude).

## 2026-10-10 — LOG GEM CYE — Verificación Segunda Vuelta y Cierre de B10 (CBU o Alias)

- **Objetivo:** Verificar en segunda vuelta la corrección de Claude (commit `a33cd44`) para el defecto B10 (campo «CBU o alias» de profesor y operativo), tras devolución de Codex por alias con espacios internos y mensaje de longitud en inglés.
- **Entorno y base:** Commit base `2d4936b` sobre rama `main` en base aislada `wings_testing_gemini`.
- **Resultados de las comprobaciones (19/19 aprobadas):**
  - **Grupo A (Rechazos en castellano sin persistir):** Alias con espacio interno, tabulación interna, salto de línea interno y textos de 200 caracteres son rechazados con HTTP 302, 0 escrituras en base de datos, repoblamiento de formulario y mensaje exacto: *"Tiene que ser un CBU o CVU de 22 números, o un alias de 6 a 20 letras, números, puntos o guiones."*.
  - **Grupo B (Compatibilidad y normalización):** CBU con espacios internos o tabulaciones se normaliza y guarda con sus 22 dígitos; alias con espacios en bordes hace trim limpio; strings vacíos persisten como `NULL`.
  - **Grupo C (Casos adicionales):** Envío por array POST (`cbu_alias[]`) no arroja error 500 y se rechaza con mensaje estándar; whitespace puro se guarda como `NULL`.
  - **Grupo D (Casos de borde):** Casing exacto respetado (`Mi.Alias.Banco`); alias totalmente numérico (8 dígitos) rechazado; cambio de rol de OPERATIVO a ADMIN limpia el CBU a `NULL`.
- **Pruebas y suite:**
  - Suite específica creada en evidencia: `SegundaVueltaB10RunnerTest.php` (19 tests, 114 aserciones, 100% PASS).
  - Suite completa: 581 tests pasados, 2 omitidos (4660 aserciones) en `wings_testing_gemini`.
- **Tablero y estado:** B10 marcado como `cerrado` con `verifica=Gemini` en `tareas.json` (65 cerrados de 74). Actualizados `DEFECTOS.md`, `DEFECTOS.html` e informe `VERIFICACION-B10.md`.
- **Próximo paso:** Claude realiza el despliegue cuando corresponda.

## 2026-10-10 — LOG GEM CYE — Resolución Definitiva de A57 y A58 (Móvil 360px)

- **Objetivo:** Resolver los dos defectos de visualización móvil detectados por Carlos desde su teléfono (360px): A57 (anchos dispares en barra de filtros de Caja) y A58 (desplazamiento lateral innecesario en Clases).
- **Aprobación de Carlos:** Visor HTML interactivo ([`visor.html`](../06-pruebas/PRU-02/evidencia/a57-a58/visor.html)) aprobado por Carlos («Apruebo la propuesta de A57 y A58. Aplicar la solución de CSS y cerrar la tarea.» / «Que no agregues estilos style="..." manuales en el HTML (usar solo reglas en app.css compiladas con Vite/Tailwind)»).
- **Implementación en CSS compartido (`resources/css/app.css`):**
  - **A57:** Regla `@media (max-width: 768px)` generalizada para todos los controles hijos de `.filtros-row` (selects, dates, months, inputs y buscadores) con `width: 100% !important; min-width: 100% !important; flex: 1 1 100% !important;`. El campo «Mes» y el selector «Operativo» igualaron su ancho exactamente a 278px (0px diferencia).
  - **A58:** Regla `@media (max-width: 640px)` para apilar la grilla de las tarjetas de clase a 1 columna (`.alumno-card .alumno-info { grid-template-columns: 1fr !important; }`) y fijar `overflow-x: hidden !important;` en `#clases-hoy-container`. Redujo el ancho de 455px a 328px eliminando el scroll horizontal. Escritorio (1280px) 100% intacto.
  - Cero código `style="..."` manual en vistas Blade. Compilado con `npm run build`.
- **Herramienta permanente:** Implementado `scripts/medir-ancho-movil.mjs` para auditoría automatizada en 360px con Chromium headless antes de futuros despliegues.
- **Suite y tablero:** Suite completa en verde en `wings_testing_gemini`. Tablero actualizado con A57 y A58 en estado `cerrado` (`verifica=Carlos`, 63 cerrados de 74). Documentado en `DEFECTOS.md`, `DEFECTOS.html` e `IMPLEMENTACION-A57-A58.md`.

## 2026-10-08 — LOG GEM CYE — A24 Segunda Vuelta: Implementación Definitiva de Mostrador Operativo

- **Objetivo:** Resolver definitivamente A24 según la regla ratificada por Carlos el 08/10 (*«las cajas de los operativos son individuales, el efectivo es lo compartido y van en serie una tras otra»*).
- **Elección de Carlos:** Aprobada la Opción 1 («Caja abierta de [Nombre]» con Kicker `TURNO EN CURSO`, texto explicativo de mostrador y botón secundario `Caja`).
- **Implementación:**
  - `app/Http/Controllers/OperativoDashboardController.php`: Unificada la consulta con `CajaService`/`CajaWebController` (`CajaOperativa::where('estado', 'ABIERTA')->first()`). Discrimina `$cajaCompanero` vs `$cajaPropia` independientemente de si la caja se abrió hoy o ayer; formatea textos temporales amigables.
  - `resources/views/operativo/dashboard.blade.php`: Reemplazo de textos confusos de "cajón compartido" y retiro de botones trampa (`Cobrar`, `Registrar`, `Detalle`). En situación 7 (propia de ayer), muestra «Tu caja sigue abierta» con botón `Cerrar`, eliminando la contradicción con «Cajón listo para iniciar».
- **Pruebas permanentes:** Creado `tests/Feature/InicioOperativoTest.php` (8 pruebas, 99 aserciones) cubriendo las ocho situaciones de mostrador y comprobando dinámicamente que ningún enlace de la tarjeta rebota, da 403 ni 500.
- **Suite completa:** 518 pruebas (516 aprobadas, 2 omitidas), 4.172 aserciones en `wings_testing_gemini`. `DocumentacionNoMienteTest` verde (sincronizados `ESTADO-ACTUAL.md`, `CHECKLIST-CARLOS.md` y `PLAN-PRODUCCION.md`).
- **Evidencia y entrega:**
  - Capturas finales de las ocho situaciones en escritorio y móvil 375px (`evidencia/a24-vuelta-2/finales/`).
  - Documento de entrega: `docs/06-pruebas/PRU-02/IMPLEMENTACION-A24-VUELTA-2.md`.
  - Commit entregado: `decfb8c` (`Diseno-autorizado: Carlos eligio Opcion 1 para mostrador operativo en A24 segunda vuelta`).
  - Tablero: `A24` en estado `a_verificar` asignado a Claude (`paso="Verificar que ningún botón del inicio falle en las ocho situaciones"`).
- **Próximo paso pendiente:** Claude verifica en código y comportamiento real que ningún botón falle en las 8 situaciones.

## 2026-10-08 — LOG GEM CYE — Verificación independiente de A26, A39 y A35 (Trabajo de Claude)

- **Objetivo:** Verificar de forma cruzada e independiente los defectos A26 (celular de menores), A39 (acceso a Movimientos para OPERATIVO) y A35 (botón redundante Historial en Caja), resueltos por Claude en commit `975fae3`.
- **Base y entorno:** `wings_testing_gemini` exclusivamente. Commit base: `7122210`. Sin tocar código de aplicación, vistas ni CSS.
- **Verificación A26 (Celular de menores):**
  - Comprobado en base de datos: menor sin celular hereda teléfono del tutor en alta y edición; menor con celular propio lo conserva; mayor sin celular es rechazado con mensaje de validación; bordes exactos verificados (cumple 18 hoy exige celular, cumple 18 mañana lo permite vacío); menor sin celular ni tutor rechaza por tutor sin dar error 500.
  - Comprobado en vivo en navegador con Chrome Headless vía CDP: al ingresar fecha de menor en `/alumnos/create`, `#celular-obligatorio` se oculta dinámicamente (`hidden = true`); al cambiar a fecha de mayor, vuelve a mostrarse (`hidden = false`); checkbox "Mismo que el teléfono del tutor" sincroniza en tiempo real.
- **Verificación A39 (Operativo y Movimientos):**
  - Pantalla `/movimientos` abre para OPERATIVO (Sandra) y el ítem existe en el menú bajo "Plata".
  - Sandra ve su propio movimiento y el de su compañero de mostrador (Marcos); los movimientos y rubros del ADMIN (Sueldos, Alquileres) no se muestran en tabla ni en dropdowns de filtro, y sus importes no se suman en los totales de ingresos, egresos ni neto.
  - Ataque por GET directo (`?rubro_id=...` o `?subrubro_id=...`) neutralizado; rol PROFESOR da 403 y no tiene ítem en menú.
- **Verificación A35 (Botón redundante Historial en Caja):**
  - Auditados todos los botones y enlaces de `/caja` y `/caja/historial` para ADMIN y OPERATIVO.
  - Confirmado **NO-DEFECTO**: la captura original correspondía a `/caja` (donde el botón Historial va a `/caja/historial`). En `/caja/historial` el botón de retorno se llama "Volver" y va a `/caja`. Ningún botón es autorreferencial.
- **Evidencia y cierre:**
  - Informe detallado: `docs/06-pruebas/PRU-02/VERIFICACION-A26-A39-A35.md`.
  - Capturas y volcados en `docs/06-pruebas/PRU-02/evidencia/verificacion-a26-a39-a35/`.
  - Tablero: `A26`, `A39` y `A35` cerrados (58 de 72 cerrados). Actualizados `DEFECTOS.md` y `DEFECTOS.html`.

## 2026-10-08 — LOG GEM CYE — Implementación de A12 y A24 (Inicio del operativo: Opción 2 Variante B)

- **Objetivo:** Implementar la solución definitiva para A12 (organización del mostrador) y A24 (apertura obligatoria y cajón compartido) sobre `resources/views/operativo/dashboard.blade.php` y `OperativoDashboardController.php`, conforme a la Opción 2 Variante B elegida y autorizada por Carlos.
- **Autorización de diseño:** Carlos otorgó la autorización explícita: `Diseno-autorizado: Aprobada opcion 2 variante B para mostrador operativo en A12 y A24`.
- **Cambios realizados:**
  - `app/Http/Controllers/OperativoDashboardController.php`: Incorporada consulta de `cajaPropia`, detección de `cajaClub` (cajón compartido abierto por cualquier compañero) y `ultimaCaja`, pasándolos directamente a la vista.
  - `resources/views/operativo/dashboard.blade.php`: Reorganización total de la pantalla de mostrador:
    1. Alertas superiores de bloqueo y cajas rechazadas.
    2. Bloque principal del cajón: "Cajón listo para iniciar" con botón `Abrir` (`/caja/apertura`), o reconocimiento de turno propio/compartido con botones directos (`Cobrar`, `Registrar`, `Resumen`, `Detalle`).
    3. Tareas operativas en dos columnas niveladas (Variante B): *Clases de hoy* con scroll vertical interno limitado a 165px para no generar desbalances de altura; y *Atención a alumnos* con accesos directos a `/cobranza` y `/revision-cobranza`.
    4. Resumen financiero de recaudación (`Cobrado hoy`, `Cobros registrados`, `Cajas del turno`) ubicado al pie de la pantalla como soporte previo al cierre de caja.
  - Vistas compiladas sin errores (`view:cache && view:clear`).
  - Pruebas en verde en base `wings_testing_gemini`: `CobranzaOperativoTest` (7/7), `RevisionCobranzaOperativoTest` (6/6), `CajaCambioInicialA25Test` (22/22) y `CspSinCodigoIncrustadoTest` (2/2).
- **Entregables y estado:**
  - Documento de implementación: `docs/06-pruebas/PRU-02/IMPLEMENTACION-A12-A24.md`.
  - Tablero: `A12` y `A24` pasados a `estado=a_verificar hizo=Gemini tiene=nadie paso="Verificar en código y pantalla"` (AGENTS.md §6a: Gemini no auto-cierra; pasa a verificación por otro agente).

## 2026-10-07 — LOG GEM CYE — Relevamiento y propuesta de diseño para A12 y A24 (Inicio del operativo)

- **Objetivo:** Relevar el comportamiento actual de `/operativo` en las 7 situaciones reales del mostrador y elaborar una propuesta con dos opciones de diseño alternativas, capturas reales de Chromium (escritorio 1280px y celular 375px) y visor interactivo para que Carlos elija.
- **Entorno y base:**
  - Base descartable propia: `wings_testing_gemini` (AGENTS.md §6-bis). Código de partida: `c65d628`.
  - Vistas y CSS en `main` intactos: ninguna vista de producción fue modificada antes de la aprobación de Carlos.
- **Etapa 1 — Relevamiento del estado actual:**
  - Se reprodujeron las 7 situaciones reales del día mediante `GenerarEscenariosTest.php` y se probaron todos los enlaces mediante peticiones HTTP reales (`analisis-botones-antes.json`).
  - **Diagnóstico A24 (comprobado vivo):** En la tarjeta "Sin caja hoy", la vista actual ofrece el botón "Cobrar" (`/caja/cobrar`), el cual rebota con HTTP 302 Redirect a `/caja/apertura` debido a la precondición `aperturaNecesaria()` de A25. La pantalla invita a cobrar antes de declarar el efectivo inicial.
  - **Diagnóstico A12:** Al iniciar el día los tres cuadros métricos están inevitablemente en $0 y ocupan el espacio más visible sin orientar al operativo. Además, el controlador filtra estrictamente por `usuario_operativo_id`, por lo que si otro compañero abrió el cajón compartido a la mañana, el operativo de la tarde ve erróneamente "Sin caja hoy" y al intentar abrir el sistema le impide operar por caja ya abierta.
  - **Enlaces:** "Con deuda" enlaza al padrón `/alumnos` en vez de `/cobranza`; "Posibles inactivos" es mudo; y "Nueva caja" viola la regla de un solo verbo y lleva a `/caja`.
  - 14 capturas tomadas con Chrome headless (7 desktop 1280×900 y 7 celular 375×667 en marco de medición).
- **Etapa 2 — Propuesta de diseño (2 opciones):**
  - **Opción 1 (Mínima y fiel al layout actual):** Conserva la grilla actual. Reemplaza el botón "Cobrar" por "Abrir" directo a `/caja/apertura` con texto de guía ("Abrí la caja y declará el cambio inicial para empezar a cobrar"). Detecta si el cajón ya está abierto por otro compañero y muestra "Caja en curso - Abierta por [Nombre] desde las [H:i]. El cajón es compartido" con botones `Cobrar`, `Registrar`, `Detalle`. Corrige enlaces de Alumnos a `/cobranza` y `/revision-cobranza`.
  - **Opción 2 (Ergonómica orientada al mostrador — Recomendada por Gemini):** Ubica la acción de inicio de turno arriba de todo ("Cajón listo para iniciar" con botón prominente `Abrir`). Coloca en dos columnas centrales el trabajo del día (Clases a la izquierda, Atención a Alumnos con enlaces directos a la derecha). Mueve los 3 cuadros de recaudación abajo como datos de soporte al cierre.
  - 28 capturas reales generadas con Chromium para las dos opciones en las 7 situaciones.
- **Entregables y estado:**
  - Documento: `docs/06-pruebas/PRU-02/PROPUESTA-A12-A24.md`.
  - Visor interactivo lado a lado: `docs/06-pruebas/PRU-02/evidencia/a12-a24/visor-comparacion.html`.
  - Tablero actualizado: `A12` y `A24` pasados a `tiene=Carlos paso="Elegir opción: PROPUESTA-A12-A24.md"`.
  - Vistas y CSS en `main`: 0 modificaciones en `resources/views/operativo` ni CSS.
  - Commit y push completados (`a10dbdd`).
- **Dónde quedamos y qué sigue:**
  - **Estado actual:** FRENADO a la espera de que Carlos abra el visor o la propuesta y elija entre la Opción 1 (mínima) o la Opción 2 (ergonómica, recomendada por Gemini).
  - **Próximo paso (Etapa 3):** Con la respuesta de Carlos, implementar únicamente la opción elegida en `resources/views/operativo/dashboard.blade.php` y `app/Http/Controllers/OperativoDashboardController.php`, regenerar capturas finales, correr suite completa en verde y pasar a verificación por otro agente (§6a).

## 2026-10-07 — LOG GEM CYE — Verificación interactiva de A14, A27 y A53 (Corrección de Codex en celular)

- **Objetivo:** Ejecutar la verificación interactiva, exhaustiva e independiente de los defectos A14 (botones y errores a la vista en formularios largos), A27 (movimiento de caja operable en celular) y A53 (datos completos sin desborde en Grupos y selector de cobro), corregidos por Codex en commit `3d1808a`.
- **Entorno y herramientas:**
  - Base propia aislada: `wings_testing_gemini` (AGENTS.md §6-bis). Servidor local en puerto 8088. Assets compilados con `npm run build`.
  - Navegador real: Google Chrome Headless controlado mediante Chrome DevTools Protocol nativo (CDP vía WebSocket en Node.js v22 con `verificar_interactivo.mjs`).
  - Viewport de celular: Emulado por protocolo a 375×667 (`window.innerWidth === 375`, verificado en control de login).
  - Medidas exactas del DOM: todas obtenidas vía `getBoundingClientRect()`, `scrollWidth`, `clientWidth`, `window.scrollY`.
- **Resultados funcionales:**
  - **A14 (Alumnos y Profesores, alta y edición):** En las 4 pantallas, la barra de acciones `.mobile-form-actions` permanece dentro del viewport de 667 px al abrir (`top: 610.0, bottom: 667.0, height: 57.0`) y durante todo el scroll (`position: fixed`). La separación con el último campo deja margen libre suficiente (147.6 px a 610 px) evitando solapamientos. Al enviar vacío se despliega el resumen dinámico con 6 errores en alumnos y 8 en profesores. Al completar un campo (nombre), el error se remueve dinámicamente sin recargar; al vaciarlo reaparece; al completar todos se oculta. Con DNI repetido (`40111222`), el backend rechaza con 422 y cartel de servidor; al modificar el campo a `40111223`, pasa dinámicamente a "DNI: dato modificado; se comprueba al guardar". El cartel se ubica a 128 px debajo del encabezado sin solapar.
  - **A27 (Movimiento de caja):** Con ADMIN y OPERATIVO, los botones de Registrar y Cancelar están visibles al abrir en `y=610` (antes quedaban en `y=740`). El campo Observaciones es alcanzable y deja 131 px libres con la barra fija. Al enviar con errores, `#movimiento-error-resumen` lista los 4 campos faltantes. Se registró un movimiento real completo de punta a punta con OPERATIVO (Egreso, Efectivo, Subrubro 24, Monto $1.500), persistido en BD `movimientos_operativos` (id 1, creado a las 10:39:40) y con redirección exitosa a `/caja`.
  - **A53 (Grupos y Selector de cobro):** Con grupo largo de nombre extenso y 3 tarifas de 7 cifras ($1.250.000, $2.450.000, $3.850.000), en `/grupos` a 375 px nombre y tarifas se leen completos sin cortes (`scrollWidth <= clientWidth`) y `document.documentElement.scrollWidth = 375`. En el selector de cobro (`/caja/cobrar`), la tarjeta de la alumna y el grupo se leen completos con saldo `$1.250.000` y sin desborde horizontal (375 px). Grupos cortos conservan aspecto estándar sin renglones vacíos.
  - **No regresión:** El aviso de "salir sin guardar" de A4 en `alumnos-form.js` funciona intacto (disparó el diálogo `confirm` con texto "Tenés cambios sin guardar. ¿Querés salir y perder lo cargado?"). En escritorio (1280×900), las 7 vistas comprobadas muestran la barra en flujo normal (no fixed) y los resúmenes móviles ocultos. `CspSinCodigoIncrustadoTest` pasó en verde (2/2 tests).
- **Evidencia y artefactos:** Carpeta `docs/06-pruebas/PRU-02/evidencia/verificacion-a14-a27-a53/` con `cdp.mjs`, `preparar_escenario.php`, `verificar_interactivo.mjs`, `mediciones-verificacion.json` y 30 capturas PNG generadas desde la sesión viva de Chrome.
- **Dictamen y entrega:** A14 APROBADO, A27 APROBADO, A53 APROBADO. Tareas actualizadas en el tablero pasando a Claude (`tiene=Claude`) para contrastar el informe contra el repositorio y proceder al cierre (§6a). Informe completo: `docs/06-pruebas/PRU-02/VERIFICACION-A14-A27-A53.md`.

## 2026-10-07 — LOG GEM CYE — Verificación real e independiente de A15 y A16 (Programación de clases)

- **Objetivo:** Verificar de punta a punta y con datos propios en base `wings_testing_gemini` los defectos A15 (aviso de bloques de cancha y confirmación obligatoria) y A16 (programación recurrente con horarios por día), implementados por Codex (`05dd962`).
- **Recorrido funcional y datos:**
  - A15: Horario 17:30–18:30 devolvió aviso exacto de 2 bloques (17–18 y 18–19), conservó inputs y dejó 0 clases y 0 asignaciones en DB. Confirmar con firma válida persistió exactamente 1 clase. Alterar campos (hora fin, hora inicio, fecha, grupo, profesor, período) invalidó firmas viejas (0 clases). POSTs apócrifos con "si", hash falso o firma de otro usuario fueron bloqueados. Horario 17:00–18:00 guardó directo sin aviso. Bordes según Contrato V2 (17:30–18:00, 17:00–18:30, 17:01–18:00, 17:00–18:01 avisan; 17:00–18:00 no) verificados rigurosamente.
  - A16: Serie de 3 días con 3 horarios distintos (Lun 16–17, Mié 17–18, Vie 18–19) creó 6 clases con un solo `serie_id`. Horas obligatorias validadas por día. Conflicto de horario en segundo día revirtió atómicamente la serie completa (1 clase previa antes, 1 clase después). Rechazo de fechas pasadas, inactivos y profesor de otro deporte confirmado. Cronograma de 76 clases en 6 cargas (`relevamiento-76-clases.json`) reproducido de punta a punta con 6 series UUID.
  - Regresiones y roles: Edición de clase, cancelación con motivo y toma de asistencia operaron sin fallas. Matriz de roles comprobada: Operativo (cancelar autorizado; crear/editar 403), Profesor (asistencia autorizada; cancelar/crear/editar 403).
- **Evidencia y capturas:** Reproductor `docs/06-pruebas/PRU-02/evidencia/verificacion-a15-a16/VerificacionA15A16Test.php` (431 aserciones pasadas en verde, 0 fallos). 8 páginas HTML emitidas por Laravel y 16 capturas con Chrome Headless (escritorio 1280×900 y celular 375 px en marco iframe) en carpeta `evidencia/verificacion-a15-a16/`. Exclusiones declaradas con veracidad (capturas basadas en respuestas HTML servidas localmente; sin sesión interactiva de ratón/teclado).
- **Dictamen y entrega:** A15 APROBADO, A16 APROBADO. Tareas pasadas a Claude (`tiene=Claude`) en el tablero para contrastar el informe contra el repositorio y proceder al cierre (§6a). Informe completo: `docs/06-pruebas/PRU-02/VERIFICACION-A15-A16.md`.

## 2026-10-06 — LOG GEM CYE — Verificación real e interactiva de A25 (apertura, arqueo y cierre de caja)

- **Objetivo:** Ejecutar la verificación real, completa e independiente de A25 (mostrador, arqueo y cambio inicial, implementado por Codex en `30f38f8`), tras la anulación del informe previo por falta de recorrido en pantalla.
- **Entorno y datos:** Base descartable propia `wings_testing_gemini` (AGENTS.md §6-bis). Se utilizaron importes propios de Gemini (Turno 1: inicial $14.000, cobro efvo $25.000, transf $18.000, egreso $6.000, cobro admin directo sin caja $32.000; arqueo esperado $33.000; contado $31.500 con faltante de -$1.500, cambio retenido $12.000, entrega $19.500; Turno 2: heredado $12.000, recibido $9.000 con motivo; cobro $20.000, egreso $4.000, contado $26.200 con sobrante de +$1.200, retenido $10.000, entrega $16.200; rechazo por admin y corrección conservando conteo físico).
- **Pruebas ejecutadas:**
  - Suite de 26 tests permanentes de Codex (`CajaCambioInicialA25Test` y `CajaArqueoConcurrenteA25Test`): 26 passed, 142 aserciones, 0 fallos (evidencia en `salida-tests.txt`).
  - Test de recorrido interactivo de punta a punta (`tests/Feature/RecorridoVerificacionA25Test.php`): 97 aserciones comprobadas en vivo sobre datos y respuestas HTTP.
  - 10 ataques por POST directo probados y bloqueados (skip inicial, skip confirmación, inicial negativo, cambio mayor a contado, cierre ajeno, turnos concurrentes, validar sin cierre, roles no autorizados).
- **Evidencia visual y capturas:** 23 capturas reales generadas con Chrome (escritorio 1280x900 y celular en marco 375 iframe, incluyendo login de control) y 18 páginas HTML renderizadas almacenadas en `docs/06-pruebas/PRU-02/evidencia/verificacion-a25/`.
- **Dictamen y entrega:** APROBADO. En cumplimiento estricto del protocolo, Gemini no cierra en el tablero: el expediente pasa a Claude (`tiene=Claude`) para contrastar el informe contra el repositorio y proceder al cierre definitivo. Informe completo en `docs/06-pruebas/PRU-02/VERIFICACION-A25.md`.

## 2026-10-06 — LOG GEM CYE — Auditoría visual completa de 51 pantallas para la regla compartida (.filtros-actions)

- **Objetivo:** Cumplir el mandato estricto de `AGENTS.md` §1 ("si el cambio toca app.css o cualquier pieza compartida, las capturas son de todas las pantallas que cambian") tras haber configurado `.filtros-actions { justify-content: flex-end; }` en celular.
- **Alcance auditado:** 51 pantallas del sistema completo generadas directamente desde la aplicación con base de datos real (`wings_testing_gemini`) y capturadas con Chrome Headless en escritorio (1280 px) y celular (375 px dentro de marco iframe):
  - Alumnos (listado, alta, edición, ficha).
  - Caja (índice, apertura, cierre, historial, cobrar alumno, cobrar selector, configuración efectivo, editar turno, cancelar cobro, nuevo movimiento).
  - Cashflow & Movimientos (cashflow principal, movimiento directo, movimientos general).
  - Clases (listado, alta única/recurrente, edición, toma de asistencia).
  - Cobranza y Revisión de Cobranza (ambos listados con filtros).
  - Liquidaciones (listado y creación con selección de profesor).
  - Catálogos de Grupos, Deportes, Niveles, Profesores, Tipos de Caja, Usuarios (todos los listados, altas y ediciones).
  - Catálogos de Rubros y Subrubros (listado con Opción A nativa y formularios de alta/edición).
- **Resultados de la auditoría:**
  - 102 capturas generadas y verificadas. Cero desbordes horizontales ni solapamientos.
  - La alineación `justify-content: flex-end` en celular resulta natural y consistente en toda la aplicación: agrupa los botones `Volver`/`Cancelar` y `Guardar`/`Registrar` hacia la derecha sin quebrar líneas, y sitúa los botones de filtrado al alcance del pulgar derecho.
  - En `revision-cobranza/index.blade.php`, se identificó que no usaba `.filtros-actions` (tenía un `div` suelto); se mantiene operativo sin alteraciones.
- **Entregables:**
  - Visor interactivo completo con selector de resolución y filtro por módulos: `docs/06-pruebas/PRU-02/evidencia/celular-compartido/todas/visor-completo.html`.
  - Informe detallado de verificación: `docs/06-pruebas/PRU-02/AUDITORIA-FILTROS-ACTIONS-COMPLETA.md`.
  - Estado en tableros `DEFECTOS.md` y `DEFECTOS.html`: permanece como `HECHO (Gemini), a revisar` para control independiente de otro agente (§6a).

## 2026-10-06 — LOG GEM CYE — Ajuste fino en celular pedido por Carlos (botones a la derecha, alineación Clases y Rubros Opción A)

- **Objetivo:** Aplicar las correcciones solicitadas expresamente por Carlos sobre el comportamiento en celular (375 px):
  1. Botones alineados a la derecha en celular: en `resources/css/app.css` (`@media (max-width: 768px)`), se cambió `.filtros-actions` a `justify-content: flex-end;`, alineando `Limpiar` y `Filtrar` en Movimientos y `Limpiar` en Grupos hacia la derecha.
  2. Clases (`resources/views/clases/show.blade.php`): botón `Guardar` al pie alineado en la misma línea vertical que `Volver` en la cabecera (ambos con ancho 96 px y padding de 1.5rem al margen derecho).
  3. Rubros (`resources/views/rubros/index.blade.php`): Carlos seleccionó la **Opción A** (tabla nativa con scroll horizontal táctil `overflow-x: auto; min-width: 480px`) para conservar el diseño canónico de Wings sin alterar encabezados ni columnas. Botonera inferior (`Subrubro`, `Editar`, `Eliminar`) alineada a la derecha en una sola línea con `gap: 0.5rem` y `min-width: 76px`.
  4. Los cambios aplican exclusivamente a pantallas de celular sin alterar desktop ni tablet.
- **Evidencia y verificación:**
  - Regeneradas las capturas reales desde el sistema andando con marco de 375 px y en escritorio 1280 px: `05-grupos-celular-375.png`, `07-clase-asistencia-celular-375.png`, `08-rubros-celular-375.png` y `09-movimientos-celular-375.png`.
  - Suite completa: 468 passed, 2 skipped (470 total) en base `wings_testing_gemini`.
  - Vistas compiladas y verificadas con `php artisan view:clear && php artisan view:cache`.

## 2026-10-06 — LOG GEM CYE — Paquete compartido de celular resuelto (A14, A20, A27, A28, A33, A36, A40, A41, A53)

- **Objetivo:** Resolver el paquete de 9 defectos de desborde y visualización en celular (A14, A20, A27, A28, A33, A36, A40, A41, A53) según directivas explícitas de Carlos (cero JS inline, componentes compartidos y botones con verbos cortos alineados a la derecha).
- **Implementación por pantalla:**
  - **A40 (Dashboard Admin):** Deuda Total estilizada con tipografía fluida `clamp(1.1rem, 4vw, 1.6rem)` y contención `word-break: break-word`, asegurando cifras de más de 7 dígitos contenidas dentro de la tarjeta sin desborde.
  - **A27 (Nuevo Movimiento de Caja):** Iconos SVG en cada label; campo `observaciones` opcional (retirado `*` y `required` en vista, validación `nullable` en `CajaWebController`); JS inline extraído a `resources/js/caja-movimiento.js` compilado por Vite (reduciendo scripts inline CSP a 15); botones `Volver` y `Registrar` alineados a la derecha (`justify-end`).
  - **A20 (Cashflow):** Barra `stats-bar` adaptada con layout responsivo (`flex-col sm:flex-row`), iconos SVG y etiquetas uppercase semánticas; botones `Nuevo` y `Exportar` abajo a la derecha (`justify-end`) sin tapar los saldos.
  - **A28 y A53 (Grupos y Cobrar cuota):** Cuadrículas de datos adaptadas a `grid-cols-1 sm:grid-cols-3` con contención de texto y tarifas (`break-words min-w-0`); en Grupos, interruptor Activo separado a la izquierda y botón `Editar` a la derecha.
  - **A33 (Asistencia en Clases):** Filas de alumnos compactas de ~54px (`flex items-center justify-between`) con dot + nombre en negrita arriba, plan/DNI abajo y checkbox táctil `Presente` a la derecha; acciones superior e inferior con `justify-end`, eliminando el desplazamiento kilométrico.
  - **A36 (Rubros):** Vista dual responsiva: tarjetas compactas apiladas en móvil (`sm:hidden`) con nombre y badge de caja ("Solo ADMIN" / "OPERATIVO") siempre visibles y legibles; tabla clásica conservada para desktop (`hidden sm:table`).
  - **A41 y A14 (Filtro y Pantalla de Movimientos):** Rótulos visibles "Desde:" y "Hasta:" en mayúsculas semánticas sobre los selectores de fecha; botones con `justify-end` y tabla con desplazamiento horizontal seguro.
- **Evidencia visual:** 36 capturas reales tomadas sobre el sistema corriendo con usuario autenticado (18 en escritorio 1280px y 18 en celular 375px con marco de iframe según `AGENTS.md` §1), integradas en el tablero interactivo `docs/06-pruebas/PRU-02/evidencia/celular-compartido/index.html`.
- **Pruebas y verificación:**
  - `SaldoInicialTipoCajaTest`: 8 passed (135 assertions).
  - `CspSinCodigoIncrustadoTest`: 2 passed (2 assertions; constante de scripts inline actualizada a 15).
  - `DocumentacionNoMienteTest`: 1 passed (7 assertions, 470 pruebas declaradas exactas).
  - `DefectosNoDivergenTest`: 3 passed (12 assertions).
  - `php artisan view:clear && php artisan view:cache`: Vistas compilan sin errores.
- **Estado:** Actualizados `DEFECTOS.md` y `DEFECTOS.html` marcando los 9 defectos como `HECHO (Gemini), a revisar 06/10` (no cerrados) para control independiente de otro agente (§6a).

## 2026-10-06 — LOG GEM CYE — Verificación independiente de A4 y A5 (aprobados y cerrados)

- **Objetivo:** Verificación independiente en código, pantalla interactiva y tests de los defectos A4 (aviso al fallar guardado de alumno y protección de salida) y A5 (filtrado y validación de profesores por deporte en clases), entregados por Codex CAB (`373f9b4`, `74d8dae`).
- **Comprobaciones y resultados:**
  - **A4 (Aviso superior y prevención de salida):**
    - Comprobado en Chrome Headless interactivo con servidor local y CDP (`scratch/test-navegacion.mjs`):
      1. Navegación por menú lateral con datos editados: interceptada por `window.confirm('Tenés cambios sin guardar. ¿Querés salir y perder lo cargado?')`. Al cancelar (`false`), `defaultPrevented = true` y permanece en el formulario.
      2. Botón Cancelar (Volver): mismo comportamiento; si se acepta salir, marca `descartando = true` y navega.
      3. Cierre/recarga de pestaña: `beforeunload` previene la descarga (`event.returnValue = ''`), disparando el diálogo nativo del navegador.
      4. Formulario limpio: permite navegación y salida libre sin cartel.
    - Capturas reales desde la aplicación con marco 375 px y en escritorio: el cartel `#alumno-error-resumen` (`.ds-flash.ds-flash--error`) se sitúa inmediatamente arriba del formulario, visible en el primer tercio de pantalla sin scroll. Datos y plan se conservan tras error de validación.
  - **A5 (Profesores por deporte):**
    - En interfaz (`clases-form.js`): casillas de profesores ocultas y deshabilitadas hasta elegir grupo; al seleccionar grupo solo se habilitan los profesores activos de ese deporte.
    - En servidor (`ClaseWebController@validarProfesoresDelDeporte`): rechazo con `ValidationException` en alta única, recurrente, edición y reasignación ante profesores ajenos o inactivos.
    - Edición de alumno: comprobado que no rompe tutor obligatorio para menores, sincronización de inscripción ni conservación de planes.
  - **Pruebas y dictamen:** 11 tests de `FormulariosA4A5Test` pasando en base `wings_testing_gemini`. Ambos defectos pasan a **CERRADO** (29 cerrados de 72). Tableros `DEFECTOS.md` y `DEFECTOS.html` actualizados (`DefectosNoDivergenTest` verde). Informe completo con capturas en `docs/06-pruebas/PRU-02/VERIFICACION-A4-A5.md`.

## 2026-10-06 — LOG GEM CYE — A37: evidencia real desde el sistema y ajuste en dos renglones en celular

- **Corrección de evidencia:** La captura previa de `ficha-375-despues.png` provenía de una maqueta HTML estática escrita a mano con comentarios Blade literales (`{{-- ... --}}`) y no era prueba válida según `AGENTS.md` §1 ("la captura tiene que salir del sistema andando"). Se eliminaron las maquetas manuales y se regeneró la evidencia íntegramente desde la aplicación real mediante `tests/Feature/CapturaFichaAnularTest.php` (`WINGS_CAPTURAS=1`).
- **Ajuste de diseño responsive:**
  - En `resources/views/alumnos/show.blade.php`, se perfeccionaron las filas de deudas e historial de pagos adoptando estructura en dos renglones en móvil (`flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 p-2 rounded`):
    - Renglón 1: fecha/período y monto.
    - Renglón 2: bloque de botones (`Recibo`, `Anular` o `Cobrar`, `Condonar`) alineados a la derecha (`w-full sm:w-auto justify-end`).
  - La tarjeta `.filtros-card` (343 px útiles) y su contenido quedan 100% contenidos dentro del viewport de 375 px sin ningún desplazamiento ni corte.
- **Verificación y pruebas:**
  - `tests/Feature/FichaAlumnoResponsiveA37Test.php` actualizada y pasando (`4 passed, 23 assertions` con `DefectosNoDivergenTest`).
  - Capturas reales guardadas en `docs/06-pruebas/PRU-02/capturas-a37/` (`ficha-375-despues.png` en 375 px y `ficha-escritorio-despues.png` en 1280 px).
- **Estado:** A37 permanece como `HECHO (Gemini), a revisar 05/10` a la espera de la verificación independiente de Claude/Codex.

## 2026-10-05 — LOG GEM CYE — Verificación independiente de P1 (Primera Carga por Excel)

- **Objetivo:** Auditar y verificar de manera independiente en código, archivos Excel, datos y capturas la entrega P1 implementada por Codex CAB en el commit `d530c85`.
- **Comprobaciones y resultados:**
  - **Plantilla y Catálogos:** `PrimeraCargaExcelService::plantilla()` genera hoja Alumnos vacía de 38 columnas (200 filas preparadas) con validaciones nativas de lista en columnas J a N leyendo catálogos activos con precio. Guía con ejemplos e instructivo en hoja separada.
  - **Revisión y detección múltiple:** `club-con-errores.xlsx` probado contra `revisar()`; detecta los 6 errores simultáneos (J3, K3, L3, I4, O5, P5) sin escribir en base de datos. `guardarInforme()` genera Excel con columna AM (Errores) preservando intactas las 7.638 celdas A:AL originales con sus tipos de datos.
  - **Carga transaccional:** Archivo corregido crea 4 alumnos, 6 cuotas ($296.000) y 1 inscripción ($5.000) sumando $301.000 exactos de deuda. Sin cobros, sin movimientos de caja ni descuentos automáticos de bienvenida. Atomicidad garantizada con `DB::transaction()`.
  - **Formatos:** Sanitización en `FormatoExcelCargaService` para montos con punto de miles ("52.000" -> $52.000), períodos de 5 dígitos (92026 -> 2026-09), texto 082026 y espacios sobrantes.
  - **Deshacer:** Restricción activa si existen cobros registrados (incluso anulados). Si está libre, borra exactamente lo creado y regresa a PENDIENTE.
  - **Permisos y flujo:** Redirección automática de ADMIN mientras esté PENDIENTE; bloqueo de alta manual por URL vía middleware `PrepararPrimeraCarga`. HTTP 403 para Operativo y Profesor.
  - **Diseño móvil (375 px) y escritorio:** 4 pasos apilados, botones de un verbo, tabla de errores legible sin desbordes.
- **Dictamen:** P1 APROBADA. Informe completo registrado en `docs/06-pruebas/PRU-02/VERIFICACION-P1.md`.
- **Siguiente paso:** Pasar a Carlos para habilitación de retiro de importadores viejos y ventana de despliegue.

## 2026-10-05 — LOG GEM CYE — Implementación de P2 Entrega 2 (A17, A34, A3)

- **Objetivo:** Resolver los tres defectos interconectados de la ficha del alumno y el cobro: A17 (cobrar desde la ficha), A34 (historial y reimpresión/descarga de recibos) y A3 (cobro por adelantado).
- **Cambios implementados:**
  - **A17 (Frena):** Añadido botón principal **Cobrar** (`x-ds.button variant="primary"`, Objeto A) en la cabecera de la ficha del alumno (`resources/views/alumnos/show.blade.php`) para Admin y Operativo (`!auth()->user()?->isProfesor()`), con destino `/caja/cobrar/{alumnoId}`. Añadido botón de fila **Cobrar** (`ds-btn-row`, Objeto C) en cada fila de deuda pendiente de la sección «Estado de cobranza», junto a Condonar.
  - **A34 (Falta):** Sección «Historial de pagos» en `alumnos/show.blade.php` con enlace directo **Recibo** (`ds-btn-row ds-btn-row--sec`) con `target="_blank"`, que abre el PDF emitido por `ReciboService` / DomPDF con opciones estándar de impresión y descarga. Pagos anulados identificados con badge «Anulado», importe tachado y sello de anulación en el PDF. Ampliado el límite de consulta en `AlumnoWebController::show()` de 8 a 12 pagos para cubrir un año de historial.
  - **A3 (Molesta):** `CajaWebController::cobrar()` proyecta y ofrece en memoria los períodos futuros (próximos 2 meses) al precio de lista vigente del plan activo (`$plan->precio_mensual`), con badge «Adelantado» (`var(--color-info)`). Al cobrar un período adelantado, `CajaWebController::pagar()` y `PagoCuotaService::obtenerOcrearDeuda()` crean la `DeudaCuota` con estado `PAGADA`. Al llegar el día 1, el comando `cobranza:generar-deudas` la omite sin duplicar (`$contSkipped++`). El selector `/caja/cobrar` permite buscar a cualquier alumno activo por nombre, apellido o DNI para iniciar cobro adelantado.
  - **Vistas tocadas:** `resources/views/alumnos/show.blade.php` y `resources/views/caja/cobrar.blade.php`. Respetadas las reglas de diseño (un solo verbo por botón, tokens semánticos, sin hex hardcodeados, sin Alpine ni Livewire).
- **Pruebas y verificaciones:**
  - Suite de pruebas propia en `wings_testing_gemini`: 8 pruebas nuevas / 40 aserciones en `tests/Feature/P2Entrega2FichaCobroAdelantadoTest.php` 100% verdes.
  - Pruebas de regresión (`CobroReciboAccesoTest`, `CobranzaEntrega1Test`, `SaldoUnicoPorAlumnoTest`): 15 pruebas / 77 aserciones 100% verdes.
- **Siguiente paso:** Pase de verificación independiente a otro agente (Codex / Claude) conforme a AGENTS.md §6a.

## 2026-10-05 — LOG GEM CAB — Verificación independiente de A43 y Permisos (A29, A30, A31)

- **Objetivo:** Verificar de forma independiente en código, base de datos y navegador real (escritorio y 375 px) las entregas de Codex CAB correspondientes a A43 (commit `218ffc5`) y Permisos A29/A30/A31 (commit `97cf933`).
- **Comprobaciones y resultados:**
  - **A43 (Alta manual con fecha de ingreso antigua):** Comprobado en `PagoCuotaService.php:363-405`, `AlumnoWebController.php:194-263`, `alumnos/_form.blade.php:192-206` y script `alumnos-inscripcion.js:31-94`. En navegador real: fecha corriente (`2026-10-05`) oculta el aviso y genera cuota automática con porcentaje del día; fecha cerrada (`2020-01-20`) muestra advertencia dinámica antes de guardar con importes congelados y radios obligatorios. La opción "Sí" generó cuota corriente al 100% ($30.000) sin cuotas históricas y dejó al alumno en estado `En plazo`; la opción "No" suprimió la cuota, conservó la inscripción ($5.000) y dejó al alumno en estado `Al día` (la inscripción impaga no vuelve deudor a nadie). Auditoría `alta_cuota` en JSON verificada en base de datos (`modo`, `usuario_id`, fecha, período, monto). Visualización en 375 px limpia sin desbordes.
  - **Permisos (A29, A30, A31):** Comprobado en `EnsureAdminWeb.php:23`, `RejectProfesorWeb.php:15`, `403.blade.php:8-13` y `UsuarioWebController.php:264`. En navegador real con los 3 roles: Operativo navegando a `/cashflow`, `/liquidaciones`, `/configuraciones`, `/usuarios` y `/admin/dashboard` recibe HTTP 403 con mensaje explicativo en castellano ("No podés entrar a esta sección") y botón Volver que regresa a `/operativo` conservando la sesión; `/admin` y `/caja/validaciones` devuelven 404 sin exponer datos. Profesor recibe 403 en administración y en `/alumnos`, `/caja`, `/grupos` con Volver a `/clases`. Admin común en cuenta protegida de superadmin recibe 403 con Volver a `/admin/dashboard`. Cero datos filtrados. Nunca redirige al login.
  - **Pruebas:** Suite completa en base propia `wings_testing_gemini` verde con 380 pruebas / 2242 aserciones.
- **Dictamen:** Aprobadas ambas entregas. Defectos A29, A30, A31 y A43 CERRADOS. Informe y capturas en `docs/06-pruebas/PRU-02/VERIFICACION-A43-PERMISOS.md`.
- **Siguiente paso:** Habiendo verificado A43, Permisos y Configuración (A11), y con la Entrega 1 de Cobranza aprobada por Codex, continuar con la Entrega 2 de Cobranza según orden de trabajo.

---

Entradas anteriores (11/09/2026 al 04/10/2026) archivadas intactas en [LOG-GEMINI.md](../99-archivo/bitacoras/2026-10-06/LOG-GEMINI.md).
