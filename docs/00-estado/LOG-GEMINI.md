# Wings — Bitácora activa de GEMINI

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-GEMINI.md)
· [Entradas archivadas el 06/10](../99-archivo/bitacoras/2026-10-06/LOG-GEMINI.md) · [Entradas archivadas el 19/10](../99-archivo/bitacoras/2026-10-19/LOG-GEMINI.md)
· [Entradas archivadas el 30/10](../99-archivo/bitacoras/2026-10-30/LOG-GEMINI.md)

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
