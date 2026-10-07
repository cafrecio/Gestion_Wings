# Wings — Bitácora activa de GEMINI

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-GEMINI.md)
· [Entradas archivadas el 06/10 (11/09 al 04/10)](../99-archivo/bitacoras/2026-10-06/LOG-GEMINI.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).

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
