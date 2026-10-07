# Wings — Estado actual
 
## A14/A27/A53 — CERRADOS 07/10/2026 (verificados por Gemini, contrastados por Claude)

Corrección de Codex (`3d1808a`) verificada de punta a punta con emulación real de navegador (Chrome Headless controlado vía CDP nativo en Node.js v22 con `verificar_interactivo.mjs` a 375×667):
- **A14 (Formularios largos):** Botones `.mobile-form-actions` visibles al abrir (`top: 610, bottom: 667, height: 57`) y durante todo el scroll (`position: fixed`). Margen libre con el último campo (147.6 a 610 px) sin solapamientos. Resumen dinámico muestra errores en vacío (6 en alumnos, 8 en profesores); al completar un campo desaparece en tiempo real, al vaciarlo reaparece, y al completar todos se oculta. Con DNI repetido (`40111222`), rechazo 422 del servidor muestra mensaje y al modificar el campo pasa a «DNI: dato modificado; se comprueba al guardar». Cartel a 128 px debajo del encabezado sin solaparse.
- **A27 (Movimiento de caja):** Registrar y Cancelar visibles en `y=610` (antes quedaban en `y=740`). Campo Observaciones alcanzable con 131 px libres. Resumen de errores dinámico. Registro real de movimiento completado con OPERATIVO (Egreso, $1.500) persistido en `movimientos_operativos` (id 1) y con redirección exitosa a `/caja`.
- **A53 (Grupos y Cobro):** Grupo largo con 3 tarifas de 7 cifras ($1.250.000, $2.450.000, $3.850.000) y tarjeta de alumna en selector de cobro legibles al 100% sin recortes (`scrollWidth <= clientWidth`) y `document.documentElement.scrollWidth = 375` (cero desborde horizontal).
- **Regresiones:** Salir sin guardar (A4) activo con diálogo `confirm`. En escritorio 1280×900 las 7 vistas conservan layout de escritorio sin barra fija ni cartel móvil. `CspSinCodigoIncrustadoTest` 2/2 PASSED.
- [Informe completo](../06-pruebas/PRU-02/VERIFICACION-A14-A27-A53.md) · Evidencia y 30 capturas en `docs/06-pruebas/PRU-02/evidencia/verificacion-a14-a27-a53/`. Dictamen: APROBADO. Pasa a Claude para control cruzado y cierre definitivo (§6a).

## A15/A16 — CERRADOS 07/10/2026 (verificado por Gemini, contrastado por Claude)

Carlos eligió aviso y confirmación de bloques del reloj y aprobó capturas escritorio/375.
Crear avisa sin escribir; Confirmar permite continuar con los horarios revisados.
Cada día de una serie tiene su horario: seis POST guardan 76 clases de seis grupos,
una serie por grupo; conflicto en cualquier fecha revierte toda la tanda.
18 pruebas nuevas; suite completa final 486 aprobadas/2 omitidas, 3911 aserciones, sin fallas.
Build/PHP/Blade correctos; capturas reales y login de control a 375 comprobados.
[Entrega](../06-pruebas/PRU-02/IMPLEMENTACION-A15-A16.md) · [Contrato V2](../02-contratos/Wings-Contrato-Clases-Asistencias-V2.md).
Solo creación web; no reservas, tarifas, edición masiva ni despliegue. Verifica otro agente.

## Paquete de celular — verificado por Codex CyE 06/10/2026

A20, A28, A33, A36, A40 y A41: CERRADOS, verificado Codex CyE 06/10. A14, A27 y A53: DEVUELTOS a Gemini; T1 también devuelto por cobertura incompleta y recortes.

84 capturas propias, 102 originales inspeccionadas; tres roles y datos ficticios de estrés.
A36 conserva tabla Opción A, no tarjetas; A33 mide 61,61 px por fila.
Suite 489 aprobadas/2 omitidas, 3924 aserciones, 187,17 s, wings_testing_codex.
[Dictámenes, cobertura y límites](../06-pruebas/PRU-02/VERIFICACION-CELULAR-COMPARTIDO.md).
Sin cambios de aplicación ni despliegue.

## A25 CERRADO (verificado por Gemini, contrastado por Claude) — 06/10/2026

Apertura explícita con recibido confirmado, herencia del último cierre del club y motivo
si difiere. Un cajón compartido, un turno abierto. ADMIN configura el medio físico una
vez, guarda aparte sus cobros y cuenta/cierra antes de validar o rechazar.
Cierre con esperado/contado/diferencia, cambio que queda y entrega; permite diferencias
para revisión ADMIN, sin crear asientos de ajuste. Rechazadas conservan conteo/entrega y
actor/fecha originales, sin cambiar la apertura siguiente. Históricos NULL no se rellenan.
26 pruebas permanentes (142 aserciones) pasando en suite compartida, sin fallas.
Carlos aprobó las cinco pantallas escritorio/375; sin CSS ni componentes compartidos tocados.
[Entrega y capturas](../06-pruebas/PRU-02/IMPLEMENTACION-A25.md) · [Contrato V5](../02-contratos/Wings-Contrato-Caja-Cashflow-V5.md).
Verificado por Gemini en una segunda vuelta, con recorrido propio por HTTP y 23 capturas; Claude reprodujo el recorrido y contrastó el informe; CERRADO 06/10 ([Informe](../06-pruebas/PRU-02/VERIFICACION-A25.md)). Sin sesión interactiva de navegador. Base del club y servidor intactos.

## A13/B1/A55 cerrados; A48/A49 asentados — 06/10/2026

Rama a55-inscripcion integrada entera en main `6d3f68a`, conservando ambos logs.
A13/B1/A55 CERRADOS por Codex CyE: cobro ADMIN sin caja, anulación desde ficha con
motivo, historial con períodos originales, contraasientos negativos como egreso y
una inscripción por persona coherente entre selector y ambas fichas.
Regresión A25: esperado operativo $58.000 ($10.000 inicial + $48.000 propios),
sin sumar cobro directo ADMIN de $101.000 ni abrirle caja.
A48/A49 CERRADOS, verificado Claude; búsqueda repetida sobre main coincide.
**41/72 cerrados, 31 abiertos, 0 frenan.** A54 conserva su cierre anterior.
El primer control rechazado queda como antecedente, no como estado actual.
[Informe y capturas](../06-pruebas/PRU-02/VERIFICACION-A13-B1-A55-CIERRE.md). Sin deploy.

## A37 resuelto (Ficha del alumno en celular 375px) — 05/10/2026

- **Defecto A37:** La ficha del alumno (`/alumnos/{id}`) desbordaba horizontalmente en celulares a 375px, cortando los botones de cabecera (`Editar`) y filas de historial (`Recibo`, `Anular`).
- **Solución implementada:**
  - `resources/views/alumnos/show.blade.php`: la barra superior de acciones reemplazó el `justify-content: flex-end` inline por `flex flex-wrap items-center gap-2 mb-4 justify-start sm:justify-end`.
  - Las filas de deudas pendientes e historial de pagos adoptaron `flex flex-wrap sm:flex-nowrap justify-between items-center gap-2 p-2 rounded` con bloque de acciones `w-full sm:w-auto justify-end` para adaptarse en 375px sin desbordes.
  - Botón Volver inferior actualizado a `justify-start sm:justify-end`.
  - Cubierto con `tests/Feature/FichaAlumnoResponsiveA37Test.php` y capturas comparativas en `docs/06-pruebas/PRU-02/capturas-a37/`. Marcado como `HECHO (Gemini), a revisar`.

## P2 Entrega 2 implementada y probada (A17, A34, A3) — 05/10/2026

Implementación de los tres defectos de la ficha del alumno y cobro:
- **A17 (Cobrar desde la ficha):** Botón principal **Cobrar** (`x-ds.button variant="primary"`) incorporado en la cabecera de la ficha del alumno para Admin y Operativo, conduciendo a `/caja/cobrar/{id}`. En la sección de cobranza, cada cuota impaga cuenta con botón de fila **Cobrar** (`ds-btn-row`).
- **A34 (Historial y reimpresión/descarga de recibos):** Sección «Historial de pagos» con enlace directo **Recibo** (`ds-btn-row ds-btn-row--sec`) con `target="_blank"`, que abre el PDF oficial generado con DomPDF (permitiendo imprimir y descargar). Pagos anulados identificados con badge e importe tachado. Historial ampliado a 12 registros en `AlumnoWebController::show`.
- **A3 (Cobro adelantado sin duplicación):** `CajaWebController::cobrar()` proyecta y ofrece en memoria los períodos futuros (próximos 2 meses) al precio de lista vigente del plan activo, con badge «Adelantado». Al cobrar un período adelantado, se crea la `DeudaCuota` como `PAGADA`. Al llegar el día 1, el comando `cobranza:generar-deudas` la omite sin duplicar (`$contSkipped++`). Búsqueda en `/caja/cobrar` permite encontrar a cualquier alumno activo aunque no tenga deuda pendiente.
- **Suite:** 8 pruebas nuevas con 40 aserciones en `tests/Feature/P2Entrega2FichaCobroAdelantadoTest.php`, 100% aprobadas en `wings_testing_gemini`. Regresiones de Cobro y Cobranza (`CobroReciboAccesoTest`, `CobranzaEntrega1Test`, `SaldoUnicoPorAlumnoTest`) 100% verdes.
- **Pendiente:** Control cruzado independiente por otro agente (§6a). Sin despliegue.

## P2 Entrega 1 aprobada en segunda verificación — 05/10/2026

Codex CAB verificó `abc346a` sobre HEAD `4fb185e` en código y navegador:
A51/A52 resueltos, deuda por registro estable al filtrar, ayuda por otro deporte
solo con saldo y estado mensual sin inscripción en listado/ficha/resumen.
Filtros compartidos recorridos a 375px; build verde. Suite desde el repo en
`wings_testing_codex`: **384 pruebas / 2968 aserciones**, todas verdes.
Datos visuales ficticios en copia local alineada a HEAD; sin tocar el padrón ni
el servidor, sin cobros ni despliegue. [Informe y capturas](../06-pruebas/PRU-02/VERIFICACION-ENTREGA1.md).
A53/A54/A55 surgieron fuera de Entrega 1. Estado posterior al control registrado 06/10:
A53 DEVUELTO a Gemini por Codex CyE 06/10; A54/A55 corregidos por Claude y cerrados por Codex.
Ver control sobre main integrado arriba. Gemini puede continuar Entrega 2.

## A43 y permisos A29/A30/A31 verificados y cerrados — 05/10/2026

Verificación independiente realizada por Gemini sobre commits `218ffc5` (A43) y `97cf933` (Permisos).
- **A43:** Ingreso en mes cerrado (`2020-01-20`) muestra aviso dinámico con importes congelados y radios obligatorios; opción Sí genera cuota corriente 100% ($30.000) sin cuotas históricas y deja al alumno En plazo; opción No no genera cuota, conserva inscripción ($5.000) y deja al alumno Al día. Ingreso en mes corriente (`2026-10-05`) oculta el aviso y genera cuota automática con porcentaje del día. Registro de auditoría `alta_cuota` en JSON (`modo`, `usuario_id`, fecha, período, monto).
- **Permisos (A29, A30, A31):** Redirecciones silenciosas eliminadas; Operativo recibe 403 con mensaje en castellano en administración y Volver a `/operativo` con sesión activa; `/admin` y `/caja/validaciones` responden 404 sin exponer datos. Profesor recibe 403 en administración y en `/alumnos`, `/caja`, `/grupos` con Volver a `/clases`. Admin común en cuenta protegida recibe 403 con Volver a `/admin/dashboard`. Cero datos filtrados. Probado en escritorio y móvil (375 px).
- **Suite completa:** 384 pruebas / 2968 aserciones aprobadas en `wings_testing_gemini`.
- [Informe de verificación](../06-pruebas/PRU-02/VERIFICACION-A43-PERMISOS.md). Defectos A29, A30, A31 y A43 CERRADOS. Pendiente de despliegue con migración `alta_cuota`.

## P2 / A11 — Entrega 1 corregida y A11 aprobada, 04/10/2026

Entrega 1 de Cobranza: correcciones de A51 (fila por registro con deporte + DNI, columna Deuda y renglón chico de ayuda), A52 (cálculo de estado unificado basado solo en cuotas, inscripción no convierte en deudor) y A18/A19 (filtros responsive en 375px) implementadas en commit `abc346a`. Carlos aprobó el diseño tras inspeccionar las 5 pantallas a 375px. Suite completa 356/2073 verde en `wings_testing_gemini`.
A11 (Configuración): verificada de forma independiente por Gemini en código y navegador (escritorio y móvil 375px). Grupos «La plata», «La cobranza», «Los avisos», validación estricta en servidor, persistencia de errores, guardado asíncrono y generación mensual fija validados. [Informe de verificación](../06-pruebas/PRU-02/VERIFICACION-A11.md); defecto A11 cerrado. Sin despliegue.

## P1 — primera carga por Excel verificada y aprobada, 05/10/2026

P1 implementada por Codex CAB (`d530c85`) y **verificada de forma independiente por Gemini CyE** (`LOG GEM CYE`).
- **Plantilla y Catálogos:** Generación de plantilla con catálogos reales (deportes, grupos y planes activos con precio) y validación de lista desplegable en hoja Alumnos vacía de 38 columnas. Hoja Guía con instructivo y ejemplos.
- **Revisión y reporte:** Detección de múltiples errores simultáneos (6 errores en `club-con-errores.xlsx`) sin persistencia en base de datos. Exportación de informe con columna AM (`Errores`) preservando intactas las 7.638 celdas A:AL originales y sus tipos de datos.
- **Carga atómica:** Importación transaccional (`DB::transaction`) de 4 alumnos, 6 cuotas ($296.000) y 1 inscripción ($5.000) sumando $301.000 exactos de deuda. Sin cobros, sin movimientos de caja ni descuentos indebidos.
- **Formatos:** Sanitización unificada en `FormatoExcelCargaService` para montos con punto de miles ("52.000"), períodos numéricos de 5 dígitos (92026 -> 2026-09), texto con cero y DNI.
- **Deshacer:** Restricción estricta si existen cobros registrados (incluso anulados) o actividad posterior. Si está libre, revierte íntegramente la carga y restaura el estado a `PENDIENTE`.
- **Permisos y flujo:** Redirección automática de ADMIN mientras esté `PENDIENTE`, bloqueo de alta manual por URL vía middleware `PrepararPrimeraCarga`, y respuesta HTTP 403 para roles Operativo y Profesor.
- **Diseño responsive:** Recorrido en 4 pasos apilados conforme a tokens del Design System, validado en escritorio y móvil (375 px).
- [Informe de verificación independiente](../06-pruebas/PRU-02/VERIFICACION-P1.md). P1 APROBADA.
- **Pendiente de despliegue:** Migración `2026_10_05_180000_create_primera_carga.php`, build de Vite y retiro posterior de importadores antiguos (`wings:importar-padron` y `wings:importar-deuda-inicial`).

## P0 — primera carga, 04/10/2026

## A2/B2 — implementados localmente, 23/09/2026

Cuota del mes creada en el alta, atómica con alumno/plan/inscripción, con importe y
porcentaje congelados. Cobranza clasifica por saldo pendiente, sin exigir pagos previos.
Suite completa 331/1900; sin vistas ni despliegue. Requiere migración
`2026_09_23_120000_add_porcentaje_alta_to_deuda_cuotas.php` al desplegar.
**Pendiente de verificación independiente en código y pantalla; no cerrados.**
[Evidencia y límites](../06-pruebas/PRU-02/IMPLEMENTACION-A2.md).

## Sitio de prueba — automatización, 23/09/2026

Cron exclusivo de `wingstest` instalado y ejecutado realmente, incluso con nologin
y contraseña bloqueada. Sin heartbeats de producción; instalación repetible al
montar test. Figuran deudas mensuales y resumen diario a las 08:00.
Destinos guardados en Configuración. **Telegram recibido y confirmado por Carlos**;
chat verificado por API. Bot/correo autorizados. **Correo no entregado**: Postfix
rechaza por error de sus consultas MySQL de alias; pendiente reparar transporte
compartido o disponer de SMTP externo. No se tocó producción.
[Verificación y pendientes](../06-pruebas/PRU-02-AUTOMATIZACION-TEST-2026-09-23.md).

## ENT-01 — implementada, 22/09/2026

[Diseño aprobado](../05-pendientes/ENT-01-PROPUESTA-CARGOS-ADICIONALES.md): inscripción
por DNI y cargo separado de cuotas; corte retirado en P0 del 04/10. Pago parcial cubre primero
inscripción; caja y recibo desglosan conceptos. Comisión y estado mensual excluyen
inscripción. Corregir ingreso sin pagos conserva el cargo con auditoría; con pagos se rechaza.
Autorización visual y reemplazo de §5 de Punitorios confirmados por Carlos el 22/09.
FIN-14 conserva motor y configuración pendientes. Sin deploy; actualización por Claude.
[Evidencia y límites](../06-pruebas/ENT-01-INSCRIPCION-2026-09-22.md).

## Decisión nueva 21/09/2026 — canchas y liquidaciones de clubes (POS-07)

[Plan v2026-09-21](../07-evaluacion/PLAN-CANCHAS-LIQUIDACIONES-v2026-09-21.md)
documentado; implementación pendiente. Ubicaciones, canchas físicas y tarifas por
hora; bloques completos del reloj y total de clase editable por ADMIN, protegido de
aumentos automáticos. Costos y decisión económica de cancelación inaccesibles al
OPERATIVO: conserva cancelar clases, dejando revisión financiera al ADMIN.
Liquidar por mes o fechas elegidas, solo clases dictadas hasta el corte y pendientes
de liquidación; las incluidas en otra vigente no se repiten, aunque no estén pagadas.
Reporte Club → Deporte → Nivel. Sin código, base ni despliegue en esta tarea.
Reemplaza el planteo POS-07 «mejora futura sin fórmula» y la propuesta histórica de
Reportes V1 de repartir alquiler por cantidad de clases. No cambia liquidación de
profesores por minutos ni resuelve el cálculo pendiente de gastos generales en Reportes.

## Decisión nueva 13/09/2026 — particulares (POS-06)

[Contrato funcional](../02-contratos/Wings-Contrato-Clases-Particulares-V1.md) documentado; implementación pendiente. Solo documentación en este turno, sin verificación funcional nueva. La regla previa de cierre inmutable se enmienda por decisión expresa de Carlos: ADMIN puede cancelar una cerrada no pagada para revisar asistencias; pagadas intactas y ajustes en la siguiente. Rige para todas las clases y perfiles. También se documentan excepciones de particulares sobre agenda, permisos, cancelación y remuneración; no trasladarlas a clases ordinarias fuera del alcance general declarado. [Pendiente y continuidad de Reportes](../05-pendientes/CLASES-PARTICULARES.md).

> Continuidad reorganizada el 12/09, sin revalidación funcional:
> [Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo](PROTOCOLO-CONTINUIDAD.md).
> Logs activos abreviados; originales íntegros en `docs/99-archivo/bitacoras/2026-09-12/`.
> Leer esta página por sección de tarea, no para reconstruir toda la historia.

> **Actualizado:** 12/09/2026
> **Plan vigente:** `docs/07-evaluacion/PLAN-TRABAJO-IA-v2026-09-08.md`,
> version 2026-09-08.v4.
> Si otro documento contradice este estado, no improvisar: verificar y corregir.

## 1. Estado general

**FIN-12 CERRADA el 21/09/2026:** Cancelar liquidación cerrada no pagada implementada y probada (`CancelarLiquidacionCerradaTest` con 9 tests y `CancelarLiquidacionConcurrenteTest` con 2 tests con MariaDB real en dos conexiones, suite en 274 tests / 1675 aserciones). Permite a ADMIN cancelar con motivo obligatorio (mínimo 5 caracteres) una liquidación en estado `CERRADA` con `estado_pago = PENDIENTE`, preservando detalle y auditoría (`usuario_cancelacion_id`, `cancelada_at`, `motivo_cancelacion`). Liquidaciones en estado `PAGADA` son estrictamente intocables para todos los perfiles. La cancelación concurrida con el pago está coordinada transaccionalmente con `lockForUpdate()`. Mientras la liquidación esté cerrada, las asistencias de las clases involucradas (HORA y COMISIÓN) no pueden ser alteradas por ningún perfil; la cancelación las desbloquea para corrección y habilita una nueva liquidación vinculada por `reemplazada_por_id`. Diseño mínimo autorizado por Carlos en show e index con badge `Cancelada` y formulario con confirmación nativa `data-confirmar` sin inline handlers (CSP en 22 bloques y 10 manejadores intacta). Requiere migración `2026_09_21_120000_permitir_cancelar_liquidacion_cerrada_no_pagada.php`. Sin deploy.

**FIN-09 CERRADA el 19/09/2026:** Límites de fecha y confirmación en movimientos y cobros implementada y probada (`LimiteFechasMovimientosTest`, 8 tests nuevos, suite en 263 tests / 1505 aserciones). Fechas futuras rechazadas con `before_or_equal:today` en alta de caja (`editarStore`), edición de caja (`updateMovimiento`), alta directa de cashflow (`store`) y cobro de cuotas (`pagar`). Fechas del mes en curso se guardan sin aviso. Fechas del mes anterior o más viejas exigen confirmación en pantalla antes de registrar: en caja y cashflow mediante banner informativo server-side y botón `Confirmar` con campo oculto `confirmar_fecha_vieja` (sin scripts nuevos, CSP en 22 bloques intacta); en cobro de cuotas mediante respuesta 409 y confirmación del usuario. El movimiento conserva su fecha real para fines contables/históricos y entra en la caja abierta actual, sin alterar cajas cerradas o validadas. Punto marcado con comentario TODO para futuras alertas por Mail/Telegram al ADMIN.

**ENT-03 CERRADA el 12/09/2026:** Favicon definitivo de patín artístico alado aprobado por Carlos (Opción 1 - Zoom Máximo 95% superficie). Se abandonó la paleta previa negra/roja/blanca en favor de la identidad de patín artístico (bota blanca con taco, alas fucsia y cian sobre degradé claro hielo/lavanda, ruedas oscuras con contraste óptimo en pestaña). Generados `public/favicon.ico` (multi-res 16, 32, 48), `public/favicon-32x32.png`, `public/favicon-16x16.png`, `public/apple-touch-icon.png`, `public/android-chrome-192x192.png`, `public/android-chrome-512x512.png` y `public/site.webmanifest`. Vinculados en el layout raíz `resources/views/layouts/ds-app.blade.php`. Suite de 166 tests (1026 assertions) verde al 100%.

**ENT-04 CERRADA el 12/09/2026:** Ojo para ver/ocultar contraseña implementado en alta y edición de usuarios (`resources/views/usuarios/_form.blade.php`), con botones independientes para "Contraseña" y "Confirmar contraseña" (respetando simetría de columnas y localidad de control). Manejador desacoplado en `resources/js/ds-app.js` compilado con Vite sin alterar CSP (26 scripts incrustados y 24 manejadores HTML en `CspSinCodigoIncrustadoTest`). Diseño autorizado por Carlos el 07/09.

**ENT-05 CERRADA el 13/09/2026:** Acceso directo a 1 clic al recibo de cuota: 1) en la confirmación de cobro (banner flash tras volver al listado de cajas) con botón `Recibo` (`target="_blank"`, `inline=1`), y 2) en la ficha del alumno (`alumnos/show.blade.php`), con botón `Recibo` en cada fila del historial de pagos (soportando tanto pagos activos como anulados). Auto-dismiss de 3s en `ds-app.js` configurado para no descartar banners con enlaces interactivos. Protegido contra rol Profesor. Diseño autorizado por Carlos el 07/09. Suite verde con nueva prueba `CobroReciboAccesoTest`.

**SEG-11 en avance el 13/09/2026:** Migrados los 14 onclick de vistas a `ds-app.js` (`MANEJADORES_PERMITIDOS` bajó a 10). Unificado el validador de disponibilidad en vivo en `ds-app.js` para `niveles` y `tipos-caja`. Migrados los scripts de `grupos` y `usuarios` a archivos dedicados (`resources/js/grupos.js` y `resources/js/usuarios.js`) compilados con Vite y cargados vía `@vite` conforme a DESIGN-RULES.md §8 (`BLOQUES_SCRIPT_PERMITIDOS` bajó de 24 a 22 en `CspSinCodigoIncrustadoTest`). Formateo de precios por frecuencia vía `window.initMoneyInput` verificado. Assets compilados; diseño intacto. Suite completa en 222 tests (1347 assertions).

**FIN-06 CERRADA el 13/09/2026:** Comisión histórica en liquidaciones implementada y probada (`LiquidacionComisionHistoricaTest`, 7 tests nuevos, suite total en 229 tests / 1372 aserciones). Removidos los filtros de estado actual (`activo = true`, `deporte_id`) en `calcularLiquidacionComision()`: sólo hechos históricos deciden la inclusión (pago completado para el mes/año y asistencia confirmada a clase no cancelada del profesor). Congelado el `porcentaje_comision_aplicado` en tabla `liquidaciones` al generar el registro, utilizado en cálculo y recálculo con fallback a comisión actual si es nulo. Migración con backfill para liquidaciones COMISION preexistentes. Expuesto en `show.blade.php` vía controlador en memoria sin modificar vistas. Anulación de pago en recálculo probada. Suite verde.

**COB-05, COB-09 y FIN-02 VERIFICADAS 11/09 sobre e921e5d:** 15 cobros por
Chrome en una misma base sintetica, incluida subida/bajada con descuento y
asistencia, cancelacion/recobro y medios de pago distintos. Suite 161/977.
Supera el freno de a9795c6. Evidencia: `docs/06-pruebas/COB-05-CIERRE-2026-09-11.md`.

FDS-04 verificada el 11/09/2026: Cobranza por rol, dos cobros simples en navegador
y bloqueo manual completo de rubros reservados y sus hijos. Carlos revoco la
excepcion de editar observaciones. Evidencia: `docs/06-pruebas/FDS-04-2026-09-11.md`.

**Bloque 1 de cobros verificado al 11/09, incluido COB-05.** COB-01 a COB-04 y COB-06 a COB-08
verificadas por navegador e integradas en `main`. COB-04 incluyo una correccion
posterior verificada en copia aislada: pantalla y cobro usan ahora un solo calculo del
importe con descuento. COB-05 cruzo cambio de plan, descuento y asistencia sobre
main con COB-09 integrado. Evidencia en `docs/06-pruebas/`.

**Bloque FIN al 11/09:** FIN-02 corregida (el recibo toma el medio de pago del
movimiento exacto), verificada por navegador. FIN-01 no aplica por decision de Carlos.
FIN-03 implementada el 11/09: anulaciones nuevas conservan periodos/importes y
motivo para el PDF, sin imputaciones activas. Contrato Recibos V2; V1 historica.
Suite 166/1026 y PDF real revisado. Pendiente verificacion cruzada de Claude y deploy.

**Nada de esto esta desplegado:** el servidor sigue en `81f27ef`, anterior a las ocho
correcciones. No hay riesgo inmediato porque el club todavia no cargo alumnos.

Las ocho evaluaciones historicas se conservaron sin cambios de contenido en
`docs/07-evaluacion/Evaluaciones previas/`; el plan vigente permanece en la carpeta padre.

Wings esta publicado en `https://wings.gestionar-te.com.ar`, pero el gate final de
produccion no esta firmado. La base del servidor quedo preparada para que el club
cargue sus datos por pantalla y Vanina ya tiene una cuenta ADMIN. Al 11/09, segun
Carlos, **todavia no hay ningun alumno cargado**; el alcance del resto de la carga no
fue inspeccionado.

## 2. Servidor — corte SSH 08/09 y avance por consola 09/09

| Que | Estado |
|---|---|
| Commit desplegado | `81f27ef`, fast-forward verificado por consola el 09/09 |
| Diferencia con `main` al corte | El servidor incorporo documentos y scripts hasta `81f27ef`; los commits posteriores requieren sincronizacion |
| Plataforma | AlmaLinux 9, PHP 8.2.33, Laravel 12.68.0 |
| HTTPS | Activo |
| Cloudflare | Proxy activo; acceso web directo al servidor cerrado |
| Migraciones | Sin pendientes en la ultima verificacion |
| Scheduler | Registrado y ejecutado cada minuto; deuda mensual programada para dia 1 a las 06:00 |
| Backups | Diarios, cifrados, rotados y copiados a Drive |
| Monitoreo | FDS-02 cerrada 09/09: cron y respaldo instalados; fallos y recuperaciones probados. Email y Telegram recibidos por Carlos. Monitor HTTPS y ambos heartbeats Up |

No se pudo demostrar que la corrida mensual del 01/09 haya producido resultado: no
quedo un log que lo pruebe o descarte.

## 3. Base de entrega del servidor

**La base del servidor tiene carga real en curso, pero todavia sin alumnos.** El 09/09
Carlos informo que el club empezo a cargar datos; el 11/09 aclaro que **Vanina aun no
subio ningun alumno**. Lo que haya son catalogos (deportes, grupos, planes y similares),
sin personas, deudas ni cobros. No fue revalidado por SSH desde esta computadora.

Consecuencias inmediatas: no correr `CatalogosSeeder` ni ningun seeder contra esa base
(puede quitar la proteccion de Cuotas y Sueldos; FIN-01 se dio por no aplica porque ningun despliegue lo corre), no usarla para pruebas destructivas, y tratar el
respaldo como la unica red — FDS-03 ya no puede "reconstruir" el estado, porque el
estado ahora incluye datos que solo existen ahi.

Estado con el que fue preparada el 07/09, con respaldo previo — **historico, ya superado
por la carga humana**:

- Sin alumnos, deudas, pagos, clases ni operacion real.
- Se conservan `Cuotas` con `Cuota Mensual` y `Sueldos` vacio porque el codigo los
  busca por nombre.
- Se conservan las configuraciones existentes y las tres reglas de primer pago.
- Deportes, niveles, grupos, planes, tipos de caja y demas catalogos los carga el
  usuario por pantalla.
- Existe una cuenta superadmin protegida y una cuenta ADMIN de Vanina.

FDS-03 esta pausada por Carlos desde el 09/09: ese estado minimo es historico.
No crear un seeder ni limpiar datos reales; redefinir la tarea antes de ejecutarla.

## 4. Estado confirmado del repositorio

| Area | Estado |
|---|---|
| Stack | Laravel 12, PHP 8.2, MariaDB, Blade y Vite |
| **Tests** | **491 pruebas**: 489 aprobadas/2 omitidas, 3924 aserciones, 203,84 s. Suite completa sobre main integrado, base wings_testing_codex, 06/10. A13/B1/A55 CERRADOS por Codex; A48/A49 verificado Claude. [Control y alcance](../06-pruebas/PRU-02/VERIFICACION-A13-B1-A55-CIERRE.md). A15/A16/A25 siguen a revisar; sin despliegue |
| Roles | ADMIN, OPERATIVO y PROFESOR; superadmin protegido |
| Cobranza | ADMIN y OPERATIVO entran; PROFESOR rechazado; Entrega 1 aprobada por Codex 05/10 sobre `abc346a`: apertura deudores/morosos por antigüedad, fila por registro deporte + DNI con deuda propia y ayuda por otro deporte, inscripción sin alterar estado, filtros 375 y botones Cobrar/Ver de 64px |
| Alumnos | CRUD, plan vigente, fecha de alta y grupo validado contra deporte |
| Cobros | COB-05 y COB-09 verificadas en main e921e5d: 15 cobros por navegador. FIN-02 verificada: medios correctos en recibos. Evidencia COB-05-CIERRE-2026-09-11.md |
| Caja | A25 CERRADA: apertura declarada, cajón compartido, arqueo/conteo y separación cambio/entrega. Verificado por Gemini 06/10, contrastado por Claude. No desplegada |
| Cashflow | Integra cajas validadas y saldo inicial; definición FIN-04 cerrada 22/09; aplicación del contrato de Reportes pendiente |
| Clases | FIN-10 implementada y probada: edición atómica con control de profesores/presentes, fechas y liquidación cerrada; migración pendiente de deploy |
| Liquidaciones | Generacion, cierre, pago, recibos y cancelacion. FIN-05 corregida el 11/09: dos pagos a la vez de la misma liquidacion ya no registran dos egresos. FIN-06 implementada y probada: comisión histórica y porcentaje congelado en BD. FIN-13 cerrada 17/09: liquidación por duración. FIN-12 cerrada 21/09: cancelación de liquidación cerrada no pagada por ADMIN con auditoría, desbloqueo de asistencias y concurrencia protegida contra pago. Migración pendiente de deploy |
| Carga inicial | P1 implementada el 05/10, pendiente Gemini: plantilla vacía, revisión sin escritura, informe Excel, carga atómica y Deshacer. Inscripción solo por indicación del archivo. Estado persistente y control servidor; sin deploy ni limpieza de bases. Los dos importadores antiguos se conservan hasta la aceptación independiente; retiro en commit aparte |
| Dump | Fuera de Git e ignorado; `DemoSeeder` ya no lo exporta |
| PHP | `composer audit` sin avisos el 08/09 |
| JavaScript | SEG-01: Axios retirado y lock actualizado; audit cero, build y 154 pruebas/920 aserciones en copia aislada el 11/09. Sin deploy |
| CSP | Report-only con endpoint /csp-reporte; quedan 19 bloques script en 17 vistas y 10 manejadores inline |
| Diseño | Protegido por `AGENTS.md` y hook de commit |

## 5. Lo cerrado del 5 al 7 de septiembre

- Suite migrada de SQLite a MariaDB.
- Dump retirado y segunda puerta de exportacion eliminada.
- Falla de copia nocturna a Drive corregida y respaldos revalidados.
- Cloudflare configurado con confianza acotada.
- Precio de planes y frecuencias obligatorias protegidos.
- Descuento de primera cuota limitado al mes de alta.
- Importador de deuda inicial validado y carga de prueba preparada.
- Rubros `Cuotas` y `Sueldos` protegidos en la aplicacion.
- Acceso y menu de Cobranza corregidos para OPERATIVO.
- Menu reorganizado y desplegado en `9fdd03d`.
- Base del servidor preparada y cuenta ADMIN de Vanina creada.

## 6. Orden de trabajo

FDS-01 y FDS-02 cerrados. Alertas reales recibidas el 09/09. El orden restante es:

1. **FDS:** FDS-04 verificada el 11/09. FDS-03 pausada hasta redefinir su objetivo.
2. **COB-05:** verificada junto a COB-09 sobre main e921e5d. Bloque 1 verificado.
3. **FIN:** FIN-02 verificada; FIN-03 implementada, pendiente revision cruzada. Siguen historia, balance y
   concurrencia financiera.
4. **SEG:** npm, sesiones, despliegue, recuperacion, alertas, CI y CSP.
5. **PRU:** recorrido humano completo, proceso mensual y gate.
6. **ENT:** pedidos concretos de Carlos.
7. **POS:** reportes y evolucion posterior; no bloquean por aparecer en una evaluacion.

Los criterios y dependencias estan en el plan vigente. La version HTML marcable es
`docs/07-evaluacion/PLAN-TRABAJO-CARLOS-v2026-09-08.html`.

## 7. Decisiones pendientes de Carlos

- FIN-04 decidido 22/09: saldo acumulado separado de resultados/proyecciones; gastos del club no se descuentan por deporte. Contrato de Reportes enmendado; implementación pendiente.
- Como resolver revisiones con parcial, observaciones e importe historico.
- Limites temporales de movimientos manuales.
- A2/B2: enmienda del 23/09 implementada localmente: cuota en alta y estado basado en
  deuda, sin exigir pagos previos. Pendiente de verificación independiente y despliegue.
- ENT-01 resuelta: cargos separados, prioridad de inscripción, sin comisión.
- Logo, paleta y favicon del club.
- ~~Liquidación por hora~~: CERRADA 17/09 — tarifa × minutos / 60, tarifa y minutos congelados al liquidar; pantalla y recibo autorizados muestran formato "1 h 20 min" y centavos (FIN-13).

- ~~**Contradiccion registrada 17/09 (FIN-08)**~~ **resuelta 21/09:** la revision de cobranza quedo abierta a ADMIN y OPERATIVO y "Inactivo" no condona desde el 19/09. Condonar sigue solo ADMIN.

## 8. Riesgos y limites conocidos

- El rollback del deploy no revierte migraciones.
- Preflight corre despues de reabrir el sitio.
- La restauracion probada importa SQL; no reconstruye sola archivos y configuracion.
- El control historico del restore compara conteos, no contenido financiero completo.
- Un fallo de Drive conserva la copia local y salida 0; desde el 09/09 produce
  alerta diferenciada por Better Stack y Telegram, probada y recibida.
- `MoneyLockingTest` verifica texto del codigo, no concurrencia real.
- Cambiar contraseña no revoca por si solo sesiones y `remember_token`.
- Los errores de recibos pueden devolver el mensaje tecnico de la excepcion.
- Aritmetica monetaria usa `float` en parte del dominio; el daño no esta demostrado.
- `View::composer('*')` ejecuta una consulta global para el badge de clases.

## 9. Contradicciones abiertas

**A56 — cerrado por Codex CyE 06/10/2026:** signo real del contraasiento y gasto normal,
totales y marco 375 verificados en código, HTTP y navegador. Inicial $10.000, cobro y
devolución compensados, gasto $2.500, balance $7.500. Ambos seguimientos 30/72.
[Informe y límites](../06-pruebas/PRU-02/VERIFICACION-A56.md). Sin nuevas pruebas permanentes ni deploy.

**A4/A5 — cerrados 06/10/2026:** verificados de forma independiente por Gemini en pantalla interactiva y código (aviso superior accesible sin scroll, confirm en menú y Cancelar, beforeunload, y rechazo en servidor de profesores ajenos o inactivos). 11 pruebas pasando. [Informe de verificación](../06-pruebas/PRU-02/VERIFICACION-A4-A5.md). Sin despliegue ni base del club tocada. Pasan a 29 cerrados de 72.


FIN-11, 12/09: reproducido y corregido cancelar/validar en ambos órdenes
(dejaba pago ANULADO y deuda pagada 0 con 10.000 en cashflow). Corregidas también
las esperas tardías de cobrar/cancelar y validar/cobrar. Seis casos pasan, 103
aserciones aisladas. Pausa levantada el 13/09: Codex reejecutó la suite completa
en wings_testing, 187 pruebas / 1206 aserciones, sin fallas. Implementada y probada;
sin deploy. Evidencia: ../06-pruebas/FIN-11-CONCURRENCIA-2026-09-12.md.

FIN-07: pausa resuelta por Carlos el 12/09. Solo ADMIN perdona el saldo pendiente,
conservando original, pagos e imputaciones. Condonación transaccional con bloqueo
de la deuda, compartido con el cobro. Tres casos con conexiones reales y prueba
negativa sin bloqueo. Contrato Wings-Contrato-Condonacion-V1.md. Sin deploy.

FIN-03: freno documental resuelto el 11/09. Carlos autorizo Recibos V2 con
FIN-02 y FIN-03; V1 queda como antecedente. No se reconstruyen periodos ya
borrados en anulaciones antiguas. Migracion probada solo en base descartable.

COB-03 y total (COB-06), verificados el 10/09 en `cob-total` (`5238825`), no mergeada:
ambos casos, septiembre existente/virtual, muestran y registran $29.600 con cartel
70%; septiembre conserva $18.000 pendientes. Sin descuento, $38.000 correctos.
Cadena financiera y PDF verificados; suite de esa rama: 133 pruebas, 726 aserciones.
El defecto sigue en main hasta integrar; no confundir verificacion con despliegue.
Evidencia: `docs/06-pruebas/COB-03-VERIFICACION-2026-09-10.md`.

| Tema | Estado real | Proxima accion |
|---|---|---|
| `dia_generacion_deuda` editable | Confirmado 09/09: existe como fila de configuracion y **ningun codigo la lee**. El scheduler usa dia 1 fijo en `routes/console.php:12` | Definir si gobierna la tarea o se retira de pantalla |
| Alumno sin plan en la corrida mensual | `GenerarDeudasMensualesCommand:77-81` lo saltea: no genera deuda, no entra a revision, solo un `warn` que muere en el cron | Que caiga en la cola de revision con motivo propio |
| Recalcular una liquidacion puede cambiar el total de una ya cerrada | `LiquidacionService::recalcularLiquidacion()` chequea si esta cerrada **afuera** de la transaccion y sin tomar la fila. Si otra pestaña la cierra en ese instante, el recalculo cambia el total igual. `cerrarLiquidacion()` y `eliminarLiquidacion()` tienen el mismo patron, con daño nulo o que requiere tres acciones a la vez. El doble clic lo frena el anti doble envio de `ds-app.js`; queda el caso de dos pestañas o dos personas | Encontrado en el barrido de FIN-05. Corresponde a FIN-11 |
| `pagos.monto_base` se guarda mal | `crearPago()` lo reconstruye dividiendo lo cobrado por el porcentaje. Con una seña en el mes de alta da 10.000 / 0,7 = 14.285; con el mes de alta y otro mes en el mismo cobro divide tambien el que no tenia descuento. **Nadie lo lee hoy**: ni pantallas, ni recibos, ni reportes. Es una trampa para el rediseño del recibo, que querria mostrar el precio sin descuento | Definir que tiene que valer en un cobro con seña o con varios meses antes de que algo lo use |
| Descuento a un alumno de carga inicial cobrado en su propio mes de alta | `calcularReglaPrimerPago()` solo exige que el mes de alta este entre los periodos cobrados. Un alumno importado con deuda inicial de su mes de alta recibe el descuento al pagarla. La prueba existente solo cubre cobrarle **otro** mes | Carlos define si un alumno traido de la carga inicial puede recibir descuento de primer pago alguna vez |
| Wings no tenía arqueo | Relevamiento previo 06/10 confirmó que faltaban tanto inicio como conteo/diferencia; no era sumar a un arqueo existente | A25 implementada después de decisiones de Carlos. [Entrega](../06-pruebas/PRU-02/IMPLEMENTACION-A25.md). CERRADO (verificado por Gemini 06/10); no desplegada |
| Balance filtrado de Cashflow | Mezcla saldo inicial historico con movimientos del periodo | Definido 22/09 en contrato Reportes: mostrar saldo y resultado separados. Corrección funcional pendiente (POS-01); descripción previa no revalidada en este turno documental |
| Estado minimo de entrega | El club ya carga datos reales | FDS-03 pausada por Carlos el 09/09; redefinir, no limpiar |
| Tope de 1200px en guia de diseño | `app.css` no lo implementa | Decidir guia o implementacion; no tocar sin autorizacion |

## 10. Fuentes vigentes

| Necesidad | Ruta |
|---|---|
| Plan para IA | `docs/07-evaluacion/PLAN-TRABAJO-IA-v2026-09-08.md` |
| Plan marcable para Carlos | `docs/07-evaluacion/PLAN-TRABAJO-CARLOS-v2026-09-08.html` |
| Evaluacion Codex | `docs/07-evaluacion/Evaluacion Codex 8-9-26.md` |
| Evaluacion Claude | `docs/07-evaluacion/Evaluacion Claude 8-9-26.md` |
| Bitacora Codex | `docs/00-estado/LOG-CODEX.md` |
| Bitacora Claude | `docs/00-estado/LOG-CLAUDE.md` |
| Acciones de Carlos | `docs/00-estado/CHECKLIST-CARLOS.md` |
| Contratos | `docs/02-contratos/` |
| Pruebas funcionales | `docs/06-pruebas/` |
