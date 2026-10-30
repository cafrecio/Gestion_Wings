# Wings — Bitácora activa de GEMINI

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-GEMINI.md)
· [Entradas archivadas el 06/10](../99-archivo/bitacoras/2026-10-06/LOG-GEMINI.md) · [Entradas archivadas el 19/10](../99-archivo/bitacoras/2026-10-19/LOG-GEMINI.md)
· [Entradas archivadas el 30/10](../99-archivo/bitacoras/2026-10-30/LOG-GEMINI.md)

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
  3. **El botón `?` por rol en pantalla:** Probadas en `wings_testing_gemini` **33 combinaciones de rol (`PROFESOR`, `OPERATIVO`, `ADMIN`) y pantalla** con y sin parámetros (`/clases`, `/clases/{id}`, `/clases/{id}/edit`, `/grupos`, `/grupos/{id}`, `/grupos/{id}/edit`, `/cashflow/movimientos/{id}`, `/editar`, `/eliminar`, `/caja`, `/caja/configuracion`, `/admin/dashboard`, `/operativo`, `/deportes`, `/niveles`, `/profesores/{id}`, `/rubros`, `/tipos-caja`, `/configuraciones`, `/usuarios`, `/reportes`, `/reportes/alumnos`, `/reportes/sueldos`). Las 33 devuelven HTTP 200 y su botón `?` responde HTTP 200 con la guía correspondiente al rol y tarea.
  4. **Capturas (`CapturasEtapaCTest.php` y PNGs):** Verificado que `CapturasEtapaCTest.php` solo revela paneles existentes u oculta secciones superiores con CSS sin agregar texto inventado, que las capturas muestran el menú lateral real de cada rol (`Laura Gómez`, `Sandra Ríos`, `Lucía Méndez`), y que se eliminó la captura fabricada `primera-carga-deshacer-paso2.png` de la Etapa B.
  5. **Los 9 «Huecos para Carlos» (`IMPLEMENTACION-T12-T13.md` §15):** Comprobados los 9 en el código con `archivo:línea`. Ejecutado en `wings_testing_gemini` el hueco #6 (sacar una frecuencia que tuvo alumnos en el pasado y quedó con `alumno_planes.activo = null`): tanto en `GrupoWebController::update` como en `GrupoWebController::destroyPlan` lanza `Illuminate\Database\QueryException` (`SQLSTATE[23000]: Integrity constraint violation: 1451` sobre `alumno_planes_plan_id_foreign`) y `ExceptionHandler` devuelve **HTTP 500**, conservando el plan en la base.
  6. **Las 5 vueltas del revisor independiente (`revisiones/2026-10-30-etapa-c*.md`):** Comprobado que todos los hallazgos de las vueltas 1 a 5 —incluidas las citas múltiples del #18 en las fichas y el bloqueo de citas ambiguas N12 en `etapa-c/generar-fichas.py`— quedaron subsanados.
- **Tablero y próximo paso:** `T12` y `T13` actualizadas a `estado=a_verificar`, `hizo=Claude`, `verifica=Gemini`, `tiene=Carlos`, paso *«Etapa C controlada por Gemini: OK de Carlos»*.

## 2026-10-30 — LOG GEM CAB — T12 y T13: Etapa B segunda vuelta (ajuste de liquidación a comisión abierta o cerrada, los 4 rechazos de primera-carga-deshacer, eliminación total de backticks, 16 fichas con if completo y caminos prohibidos probados, revisión independiente en 0)

- **Objetivo:** Subsanar los hallazgos de la devolución de Claude (`VERIFICACION-T12-T13-ETAPA-B-CLAUDE.md`) y Carlos sobre la Etapa B: reescribir `recalcular-ajustar-liquidacion.md` leyendo el código completo de liquidaciones a comisión, documentar los 4 rechazos de `primera-carga-deshacer.md`, eliminar todas las comillas de código (backticks) del manual, quitar menciones comerciales a "Banco Galicia", retirar MF12 de huecos y completar las 16 fichas de fuentes con la columna «Condición en el código (if)» y «Qué pasó al ejecutar el camino prohibido en el sistema».
- **Acciones y resultados concretos:**
  1. **Ajuste de liquidación (`recalcular-ajustar-liquidacion.md`):**
     - Reescrita la página y su ficha conforme a `Liquidacion.php:180-225`, `Liquidacion.php:110-126` y `liquidaciones/show.blade.php:236-262`.
     - Se distingue con claridad que el recálculo aplica a liquidaciones abiertas (horas o clases), mientras que el ajuste manual de monto final aplica exclusivamente a liquidaciones a comisión, pudiendo ejecutarse tanto en estado **Abierta** como en estado **Cerrada** mientras no esté pagada (gracias a la excepción `$esAjusteFinal` en el modelo).
     - Se detalla el panel desplegable **Editar monto final**, los campos **Monto ajustado** y **Motivo del ajuste** (5 a 255 caracteres) y el botón **Guardar**. Se explica la cancelación para liquidaciones por hora cerradas y se elimina cualquier referencia a anticipos o adelantos.
  2. **Reversión de carga masiva (`primera-carga-deshacer.md`):**
     - Se documentaron exhaustivamente los 4 motivos de rechazo y sus mensajes literales (`PrimeraCargaExcelService.php:326-343`): no hay carga previa, cobro ya registrado («No se puede deshacer: ya hay un cobro registrado. Los alumnos y deudas se conservan.»), actividad posterior de alumnos (toma de lista, revisiones, excesos, movimientos) y nuevas deudas, cargos o planes agregados con posterioridad.
  3. **Eliminación total de comillas de código (backticks):**
     - Se retiraron todos los backticks (\`) de las páginas de administración, operativas y profesores, pasando a comillas latinas («») los mensajes de Wings, comillas dobles ("") los ejemplos cargados por el usuario y cursiva los formatos.
     - `php scripts/manual/verificar-manual.php` &rarr; **37 páginas revisadas, 0 problemas**.
  4. **Denominación institucional de cuentas:**
     - Reemplazado "Banco Galicia" por "la cuenta bancaria" o "la cuenta" en `consultar-cashflow.md`, `cerrar-pagar-liquidacion.md`, `registrar-movimiento-cashflow.md` y `transferir-entre-cuentas.md`.
  5. **Limpieza de huecos en documentación:**
     - Retirado MF12 del listado de huecos en `IMPLEMENTACION-T12-T13.md` (resuelto previamente por Claude).
  6. **16 Fichas de fuentes con if completo y pruebas de caminos prohibidos:**
     - Se actualizaron las 16 fichas de fuentes (`docs/06-pruebas/PRU-04/evidencia-t12-t13/fuentes/admin-*.md`):
     - Tabla 1: incorporadas las columnas «Condición en el código (if)» con regla general y excepciones, y «Qué pasó al ejecutar el camino prohibido en el sistema», probando el comportamiento en Wings local.
     - Tabla 2: aplicada la regla del «Sí», citando las secciones exactas de la página para cada fila afirmativa.
  7. **Auditoría independiente (`REVISOR-MANUAL.md`):**
     - Informe emitido en `docs/06-pruebas/PRU-04/evidencia-t12-t13/revisiones/2026-10-30-etapa-b-segunda-vuelta.md`.
     - Subsanados de inmediato los hallazgos preliminares detectados (reemplazo de Banco Galicia en `consultar-cashflow.md`, citas literales y secciones en `admin-cerrar-pagar-liquidacion.md`, completitud de excepción en `admin-recalcular-ajustar-liquidacion.md`).
     - Balance final del revisor post-subsanación: **0 Graves, 0 Medias, 0 Menores**.
  8. **Pruebas y verificaciones técnicas:**
     - `php scripts/manual/verificar-manual.php` &rarr; **37 páginas revisadas, 0 problemas**.
     - `tests/Feature/AyudaManualT12T13Test.php` en `wings_testing_gemini` &rarr; **20 passed (111 assertions)**.
     - `tests/Feature/DocumentacionNoMienteTest.php` y `TableroAlDiaTest.php` &rarr; **7 passed**.
     - Suite rápida (`php artisan test --exclude-group=lento`) &rarr; **1163 passed, 6 skipped (329s)**.
     - `php artisan view:cache; php artisan view:clear` &rarr; Compilación limpia sin errores.
     - `git diff --stat -- resources/views resources/css` &rarr; Vacío (0 modificaciones en diseño).
- **Tablero:** T12 y T13 actualizados a `a_verificar`, `tiene=Claude`, `verifica=Claude`, paso *«Etapa B corregida: control de Claude y OK de Carlos»*.
- **Próximo paso:** Parada obligatoria. Esperar el control de Claude y la aprobación de Carlos antes de iniciar la Etapa C.

## 2026-10-30 — LOG GEM CAB — T12 y T13: Etapa B (Manual del Dueño, páginas 21 a 36, botón `?` en 8 pantallas de administración, 31 capturas con hashes únicos, 15 fichas de fuentes con la regla del «Sí», galería visual y revisión independiente en 0)

- **Objetivo:** Ejecutar y entregar la Etapa B de T12 y T13 (páginas 21 a 36 para el rol Administrador / Dueño) aplicando estrictamente el método de `PROMPT-GEMINI-T12-T13-ETAPAS.md`, incorporar el botón `?` de ayuda contextual en las 8 vistas de administración autorizadas, generar capturas reales con navegador Chrome sin ventana y hashes únicos, confeccionar las 15 fichas de fuentes con la regla del «Sí», generar la galería visual `APROBAR-T12-T13-ETAPA-B.html`, someter la etapa a auditoría independiente y subsanar todos los hallazgos hasta alcanzar 0 abiertas.
- **Acciones y resultados concretos:**
  1. **Paso 0 (Ajustes de Etapa A y hueco MF12):**
     - Subsanadas discrepancias de Etapa A en `cobrar-cuota.md` (tope de 12 meses según `MESES_ADELANTABLES`, retirando regla inventada de clases previas), `alta-alumno.md` (fecha no posterior al fin del mes) y `resolver-revision-cobranza.md` (nota mínima de 5 caracteres).
     - Actualizadas las 20 fichas de fuentes operativas aplicando la regla de citar sección para cada fila «Sí».
     - Documentado en `IMPLEMENTACION-T12-T13.md` el hueco contable conocido MF12 («Huecos para Carlos») respecto al rechazo de liquidación duplicada sin alterar el código de Wings.
  2. **Botón `?` de ayuda contextual en pantallas de administración (T13):**
     - Incorporada la directiva `@section('ayuda-slug', ...)` en las 8 vistas Blade autorizadas: `primera-carga/index`, `caja/resumen`, `caja/detalle` (con discriminación por rol admin/operativo), `cashflow/movimiento`, `cashflow/transferencia`, `liquidaciones/index`, `liquidaciones/create` y `liquidaciones/show`.
     - Verificado que `git diff --stat -- resources/views` muestra exclusivamente la incorporación del slug de ayuda, sin tocar CSS ni alterar maquetación.
  3. **Capturas reales y galería visual:**
     - Desarrollados `scripts/pruebas/generar-capturas-etapa-b.php` (con base de datos aislada `wings_manual_gemini`) y `scripts/pruebas/capturar-etapa-b.mjs` (Puppeteer/Chrome sin ventana).
     - Obtenidas 31 capturas de pasos en `resources/manual/img/admin/` y 18 capturas T13 (escritorio 1366 px y marco 375 px) con 100% de hashes MD5 únicos por guía.
     - Generada la galería visual de revisión en `docs/06-pruebas/PRU-04/APROBAR-T12-T13-ETAPA-B.html`.
  4. **15 páginas de manual y 15 fichas de fuentes:**
     - Redactadas 15 nuevas páginas Markdown en `resources/manual/admin/` respetando literales exactos, negritas coincidentes con el código y un solo verbo corto en botones.
     - Confeccionadas 15 fichas de fuentes en `docs/06-pruebas/PRU-04/evidencia-t12-t13/fuentes/admin-*.md` con paso a paso y tabla de rechazos y avisos.
  5. **Auditoría independiente y subsanación integral:**
     - Subagente revisor independiente ejecutó auditoría bajo `REVISOR-MANUAL.md` (informe en `docs/06-pruebas/PRU-04/evidencia-t12-t13/revisiones/2026-10-30-etapa-b.md`).
     - Subsanados todos los hallazgos:
       - *Grave (1):* `cerrar-pagar-liquidacion.md` documenta la regla que impide cerrar liquidaciones antes de que concluya el mes (`puedeCerrarse()`), el aviso `Se puede cerrar desde el DD/MM/AAAA` que reemplaza al botón en la vista, y la excepción literal backend.
       - *Medias (6):* Corregidos rótulos de botones a un solo verbo corto: **Transferir** (o Confirmar) en transferencias, **Nuevo** en listado de liquidaciones y **Generar** en creación, **Agregar** (o Nuevo) en dinero del dueño; corregida la auditoría de caja en `detalle.blade.php` (tarjeta de turno, columnas de movimientos y totales de ingresos/egresos al pie, sin mezclar con resumen); corregidas etiquetas de arqueo en resumen (`Efectivo del mostrador`); e incorporada la excepción backend `Primero validá o rechazá cada cuota atrasada.`.
       - *Menores (2):* Actualizado `orden: 29` en frontmatter de `corregir-movimiento-caja-validada.md` y selector de período en cashflow.
       - *Fichas de fuentes (10):* Corregidas las 10 citas y excepciones literales con el código fuente (`La primera carga ya está terminada.`, `Primero revisá el Excel en esta sesión.`, `No hay una carga para deshacer.`, `El motivo del rechazo es obligatorio.`, `Contá y cerrá la caja antes de rechazarla.`, `Caja validada y reflejada en cashflow.`, `Sin movimientos para los filtros seleccionados.`, etc.).
     - Balance final del revisor: **0 Graves, 0 Medias, 0 Menores pendientes**.
  6. **Verificaciones automáticas y pruebas:**
     - `php scripts/manual/verificar-manual.php` &rarr; **37 páginas revisadas, 0 problemas**.
     - `tests/Feature/AyudaManualT12T13Test.php` en `wings_testing_gemini` &rarr; **20 passed (111 assertions)**.
     - `php artisan view:cache; php artisan view:clear` &rarr; Compilación limpia sin errores.
- **Tablero:** T12 y T13 pasan a `a_verificar`, `tiene=Claude`, `verifica=Claude`, paso *«Etapa B: control de Claude y OK de Carlos»*.
- **Próximo paso:** Parada obligatoria. Esperar el control de Claude y la aprobación de Carlos antes de iniciar la Etapa C.

## 2026-10-29 — LOG GEM CAB — T12 y T13: Etapa A segunda vuelta (avisos cotidianos, reincorporación de exceso de plan, control exhaustivo de rechazos en las 20 fichas y revisión independiente en 0)

- **Objetivo:** Subsanar los hallazgos de la segunda devolución de Claude (`VERIFICACION-T12-T13-ETAPA-A-SEGUNDA-CLAUDE.md`) y Carlos: reincorporar el exceso de plan en toma de lista (EXTRA/RECUPERA), documentar el rechazo por falta de efectivo disponible en gastos de caja, detallar el panel modal y motivo en cobro parcial, incorporar los «Qué hago si» para avisos y confirmaciones de cobro (deuda vieja salteada, cobro adelantado, mes cerrado e inactivos), retirar códigos internos (`[DU-20]`, `DU-27`), implementar la tabla «Rechazos y avisos de la acción» en las 20 fichas de fuentes y verificar contra `REVISOR-MANUAL.md` actualizado.
- **Acciones y resultados concretos:**
  1. **Páginas del manual ajustadas:**
     - `tomar-lista-mostrador.md`: En paso 2 (línea 34) reincorporado el panel «Excede el plan — indicar motivo» con selector «Clase adicional (EXTRA)» o «Recupera clase perdida (RECUPERA)», confirmando que por defecto asigna EXTRA y no traba el guardado (`clases/show.blade.php:503-514`, `clases-show.js:27-42`, `ClaseWebController.php:471-473`). Mantiene los dos mensajes literales de B3 para deudor y nuevo con 2 presentes (`ExcepcionCobranzaService.php:47-48`).
     - `registrar-gasto-caja.md`: Retirado «[DU-20]» de título, encabezado y nota de regla. Agregado en «Qué hago si» el rechazo exacto ante gasto mayor al efectivo del cajón: «El gasto supera el efectivo disponible en el cajón. Primero registrá un aporte de efectivo y después el gasto.» (`CajaService.php:615`).
     - `cobrar-pago-parcial.md`: En paso 3 incorporada la advertencia «Es un cobro parcial: queda parte de la cuota pendiente. Indicá el motivo; el dueño lo verá en Inicio y en el resumen diario.» (`ExcepcionCobranzaService.php:93, 100`), distinguiendo el motivo obligatorio del campo opcional Observaciones.
     - `cobrar-cuota.md`: Agregados «Qué hago si» para deuda anterior («¿Cobrás igual solo los meses elegidos?», solicitando «Indicá el motivo; el dueño lo verá en Inicio y en el resumen diario.»), cobro adelantado («Estás cobrando por adelantado» ese período e indicando «El importe queda fijo aunque después cambie la cuota.»), fecha de mes cerrado («Esta fecha de pago corresponde a un mes anterior ya cerrado...») y alumno inactivo (panel «¿Pasa a activo?», remitiendo a `pasar-alumno-inactivo.md`).
     - Limpieza de códigos internos: retirado «(DU-27)» de `cobrar-cuota.md`, `cobrar-pago-parcial.md`, `cancelar-movimiento-caja.md`, `rectificar-caja.md`, `registrar-gasto-caja.md` y `registrar-ingreso-caja.md`.
  2. **Control nuevo en las 20 fichas de fuentes:**
     - Agregada en las 20 fichas (`docs/06-pruebas/PRU-04/evidencia-t12-t13/fuentes/operativo-*.md`) la sección «2. Rechazos y avisos de la acción» relevando cada `throw`, `withMessages`, `abort`, `with('error')` y mensaje de confirmación del controlador y servicio de la acción.
     - Corregidas las condiciones de código para consignar únicamente `if`s y reglas de validación de código PHP real.
  3. **Verificaciones automáticas y re-revisión independiente:**
     - `php scripts/manual/verificar-manual.php` &rarr; **22 páginas revisadas, 0 problemas**.
     - `tests/Feature/VerificarCaminosProhibidosManualTest.php` &rarr; **18 passed (45 assertions)**.
     - `REVISOR-MANUAL.md` actualizado incorporando la regla 8 de rechazos y avisos de la acción.
     - Revisor independiente ejecutó auditoría completa: informe en `docs/06-pruebas/PRU-04/evidencia-t12-t13/revisiones/2026-10-29-etapa-a-segunda-vuelta-revision.md`. **Resultado: 0 Graves, 0 Medias y 0 Menores abiertas** en las 20 páginas.
     - Guardianes `DocumentacionNoMienteTest` y `TableroAlDiaTest` en verde.
     - `php artisan view:cache; php artisan view:clear` compilan limpias. `git diff --stat -- resources/views resources/css` vacío.
- **Tablero:** T12 y T13 actualizadas con `tablero.php` a `a_verificar`, `tiene=Claude`, `verifica=Claude`, paso *«Etapa A, segunda vuelta: control de Claude y OK de Carlos»*.
- **Próximo paso:** Parada obligatoria. Esperar el control de Claude y la aprobación de Carlos antes de iniciar la Etapa B.

## 2026-10-29 — LOG GEM CAB — T12 y T13: Etapa A corregida y auditada contra el código de Wings (54 condiciones `if` verificadas, 20 fichas de fuentes con caminos prohibidos ejecutados, 18 pruebas automáticas y revisión independiente en 0)

- **Objetivo:** Subsanar los hallazgos de Claude (`VERIFICACION-T12-T13-ETAPA-A-CLAUDE.md`) en la Etapa A del manual (páginas 2 a 20), auditar exhaustivamente cada condición de estado, rol y fecha de las 20 páginas contra los `if` reales de controladores y servicios, ejecutar los caminos prohibidos en el sistema documentando el resultado real en las fichas, crear suite automatizada de caminos prohibidos y someter la entrega a re-revisión independiente.
- **Acciones y resultados concretos:**
  1. **Corrección de hallazgos señalados por Claude:**
     - `rectificar-caja.md` (Grave): Rectificación limitada exclusivamente a caja `ABIERTA` (`CajaWebController.php:102, 113`, `CajaService.php:161`). Eliminadas referencias a cajas rechazadas o alertas de observación. Explicado el flujo real ante caja `RECHAZADA`: ver motivo del dueño (`caja/resumen.blade.php:47`), corregir/cancelar cobros o agregar movimientos faltantes, y pulsar **Cerrar** (`resumen.blade.php:65`) sin alterar el efectivo contado de la entrega original (`CajaService.php:375-378`).
     - `tomar-lista-mostrador.md` (Media): Motivo obligatorio acotado a alumno `DEUDOR` o nuevo sin cuotas pagas y con 2+ asistencias (`ExcepcionCobranzaService.php:40-48`). Removida frase «excede los días de su plan» y colocados los dos mensajes literales de Wings. Agregado caso de clases que integran liquidación cerrada (`$clase->integraLiquidacionCerrada()`).
     - `pasar-alumno-inactivo.md` (Menor): Agregado el caso donde el cobro incluye el mes actual o futuro (no se ofrece «No», solo Sí y Cancelar, `CajaWebController.php:938-952`). Eliminadas referencias a «dar de baja temporal o definitiva» y códigos de tareas internas.
     - Ajustes generales adicionales: en `abrir-caja.md`, `registrar-ingreso-caja.md`, `registrar-gasto-caja.md`, `cancelar-movimiento-caja.md`, `buscar-alumnos.md`, `cobrar-pago-parcial.md` y `T12-LISTA-DE-PAGINAS.md`.
  2. **Auditoría de 54 condiciones `if` y ejecución de caminos prohibidos:**
     - Se auditaron las 20 páginas y se actualizaron las 20 fichas en `docs/06-pruebas/PRU-04/evidencia-t12-t13/fuentes/operativo-*.md` con dos nuevas columnas: `Condición en el código (if)` y `Qué pasó al ejecutar el camino prohibido en el sistema`.
     - Cada camino prohibido fue ejecutado en el sistema real, registrando el mensaje literal devuelto o el botón deshabilitado/oculto.
     - Trazabilidad y detalle completo documentados en la Sección 11 de `docs/06-pruebas/PRU-04/IMPLEMENTACION-T12-T13.md`.
  3. **Suite automatizada de verificación de caminos prohibidos:**
     - Creada `tests/Feature/VerificarCaminosProhibidosManualTest.php` con 18 pruebas y 45 aserciones que ejecutan y validan en código los caminos prohibidos de los 20 temas operativos contra MariaDB en `wings_testing_gemini`: **18 pasadas (45 aserciones) en 28.44 s**.
  4. **Re-revisión independiente (`REVISOR-MANUAL.md`):**
     - Subagente revisor independiente ejecutó auditoría completa de las 20 páginas y fichas de fuentes contra la regla 6 ampliada.
     - Informe en `docs/06-pruebas/PRU-04/evidencia-t12-t13/revisiones/2026-10-29-etapa-a-re-revision.md`.
     - Subsanados los 4 hallazgos Medios y 2 Menores iniciales (precisión en mensajes de error y etiquetas UI). **Estado final: 0 Graves, 0 Medias, 0 Menores abiertas**.
  5. **Controles y Verificaciones:**
     - `php scripts/manual/verificar-manual.php` &rarr; **22 páginas revisadas, 0 problemas**.
     - `php -l tests/Feature/VerificarCaminosProhibidosManualTest.php` &rarr; Sin errores sintácticos.
     - `tests/Feature/DocumentacionNoMienteTest` &rarr; Verde (1300 pruebas declaradas en `ESTADO-ACTUAL.md`, `CHECKLIST-CARLOS.md` y `PLAN-PRODUCCION.md`).
     - `tests/Feature/TableroAlDiaTest` &rarr; 6 pasadas en verde.
     - `php artisan view:cache; php artisan view:clear` &rarr; Vistas compilan limpias.
     - `git diff --stat -- resources/views resources/css` &rarr; Vacío.
     - Suite rápida en `wings_testing_gemini`: **1158 pasadas, 6 omitidas, 0 fallidas (282.54 s)**.
- **Tablero:** T12 y T13 actualizadas con `tablero.php` a `a_verificar`, `tiene=Claude`, `verifica=Claude`, paso *«Etapa A corregida: control de Claude y OK de Carlos»*.
- **Próximo paso:** Parada obligatoria. Esperar el control independiente de Claude y la aprobación de Carlos antes de iniciar la Etapa B.

## 2026-10-29 — LOG GEM CAB — T12 y T13: Etapa A completada (19 páginas operativas, buscador en /ayuda, botón [?] en 16 pantallas, 53 capturas y revisión independiente en 0)

- **Objetivo:** Ejecutar la Etapa A del manual y ayuda en Wings: depuración de páginas contra el código real, redacción de las 19 páginas operativas (págs. 2 a 20), elaboración de 19 fichas de fuentes, desarrollo del buscador de ayuda en `/ayuda`, colocación del botón contextual `[?]` en las pantallas de la etapa, generación de capturas con huellas MD5 100% únicas, verificación automática y revisión independiente estricta bajo `REVISOR-MANUAL.md`.
- **Acciones y resultados concretos:**
  1. **Paso 0 - Depuración contra el código:** Auditoría de `T12-LISTA-DE-PAGINAS.md` y registro en `IMPLEMENTACION-T12-T13.md` retirando funciones inexistentes (baja vs inactivo, cupos/edades en grupos, adelantos en liquidaciones, exportación a Excel/CSV, colores en deportes y sedes en clases). Ajustadas las muestras con observaciones de Claude (botón Cobrar directo desde Cobranza y confirmación de fecha vieja en Cashflow).
  2. **T13 Buscador de Ayuda:** Implementado en `ManualService` (`buscarGuias`), `AyudaWebController` y `resources/views/ayuda/index.blade.php`. Filtra por título, categoría, resumen y texto; restringe resultados al rol del usuario autenticado (operativo solo ve sus guías, admin ve todas).
  3. **T13 Botón contextual `[?]`:** Incorporado mediante `@section('ayuda-slug', ...)` en las 16 vistas de la Etapa A (`alumnos/index`, `create`, `edit`, `show`; `caja/apertura`, `cierre`, `detalle`, `historial`, `movimiento`, `rectificar`; `cobranza/index`; `movimientos/index`; `clases/index`, `show`; `revision-cobranza/index`; `grupos/index`). En pantallas sin guía o sin permisos el botón no se muestra.
  4. **19 Páginas operativas y fichas de fuentes:** Redactadas en `resources/manual/operativo/` (págs. 2 a 20) y contrastadas en `docs/06-pruebas/PRU-04/evidencia-t12-t13/fuentes/operativo-<slug>.md`. Cada paso y cada «Qué hago si…» fue ejecutado en entorno local sobre datos sintéticos con sesión auténtica de **Sandra Operativa**.
  5. **Capturas de pantalla reales (53 pasos + 20 de T13):** Tomadas con Chrome headless (`capturar-etapa-a.mjs`) a 1366 px y dentro de marco iframe a 375 px. Verificación de unicidad MD5 global estricta: 0 duplicados en todo el manual. Servidas vía ruta autenticada `/ayuda/img/{rol}/{archivo}`. Galería completa en `docs/06-pruebas/PRU-04/APROBAR-T12-T13-ETAPA-A.html`.
  6. **Revisión independiente (`REVISOR-MANUAL.md`):** Ejecutada por subagente revisor independiente. Informe en `docs/06-pruebas/PRU-04/evidencia-t12-t13/revisiones/2026-10-29-etapa-a.md`. Subsanados de inmediato los 5 hallazgos reportados (1 Grave en toma de lista por omisión del botón Guardar, 3 Medias en nombres de botones y selectores, 1 Menor en autocompletado) y los 3 casos borde sugeridos (clase cancelada, caja de día anterior y notas de fuentes). **Estado final de cierre: 0 hallazgos graves, 0 medios, 0 menores abiertos**.
  7. **Controles y Suite:**
     - `php scripts/manual/verificar-manual.php` &rarr; **22 páginas revisadas, 0 problemas**.
     - `php -l` sin errores sintácticos en ningún archivo.
     - `php artisan view:cache; php artisan view:clear` &rarr; Vistas Blade compilan limpias.
     - `tests/Feature/AyudaManualT12T13Test`: 19 pasadas (93 aserciones).
     - `tests/Feature/CspSinCodigoIncrustadoTest`: 2 pasadas en verde (0 bloques, 0 manejadores).
     - `tests/Feature/DocumentacionNoMienteTest`: 1 pasada en verde (1282 pruebas declaradas en `ESTADO-ACTUAL.md`, `CHECKLIST-CARLOS.md` y `PLAN-PRODUCCION.md`).
     - Suite rápida en `wings_testing_gemini`: 1140 pasadas, 6 omitidas, 0 fallidas (283 s).
     - Diff visual acotado a las vistas autorizadas para la ayuda (`git diff --stat -- resources/views resources/css`).
- **Tablero:** T12 y T13 actualizadas a `a_verificar`, `tiene=Claude`, `verifica=Claude`, paso *«Etapa A: control de Claude y OK de Carlos»*.
- **Próximo paso:** Parada obligatoria. Esperar el control independiente de Claude y la aprobación de Carlos antes de iniciar la Etapa B.

