# Wings — Bitácora activa de GEMINI

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-GEMINI.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).

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

## 2026-10-04 — LOG GEM CAB — Verificación independiente de Configuración (A11, commit 7a8fe09)

- **Objetivo:** Verificar de forma independiente en código y navegador real la entrega de Configuración (A11) realizada por Codex CAB.
- **Acciones y comprobaciones:**
  - Código: inspeccionados `ConfiguracionWebController.php`, `ReglaPrimerPagoWebController.php`, vistas Blade (`configuraciones/index.blade.php`, `_campo.blade.php`, `_regla.blade.php`), script `resources/js/configuraciones.js` e integración Vite.
  - Navegador real (CDP): recorrido completo en escritorio (1280×950) y móvil (375×812).
  - Grupos: «La plata», «La cobranza» y «Los avisos» con títulos y explicaciones claras en castellano.
  - Validación de servidor: rechazo comprobado de inscripción negativa (-100), días de gracia fuera de rango (29) y email con formato inválido. Los errores se despliegan en el resumen superior y junto al campo, y **permanecen visibles** (>5s sin auto-dismiss).
  - Guardado: al ingresar valores válidos se guarda asíncronamente vía PATCH, se eliminan los errores y aparece el estado «Guardado».
  - Parámetro inmutable: la generación de cuotas se presenta como dato fijo (día 1 a las 06:00, hora Argentina) y el controlador rechaza peticiones directas de modificación.
  - Reglas de primer pago: editor inline valida porcentajes inválidos (0) con mensaje en castellano («El porcentaje va del 1 al 100.») y el botón Cancelar restaura la visualización sin guardar.
  - Celular (375px): diseño responsive ordenado, tarjetas y botones contenidos sin desbordes horizontales.
  - Suite de pruebas: `ConfiguracionA11Test` (8 pruebas / 98 aserciones) y `ReglaPrimerPagoSinSuperposicionTest` (5 pruebas / 39 aserciones) 100% verde en `wings_testing_gemini`.
- **Dictamen:** Aprobada. A11 queda marcado como CERRADO en `DEFECTOS.md`. Informe y evidencia en `docs/06-pruebas/PRU-02/VERIFICACION-A11.md`.
- **Siguiente paso:** Pasar Entrega 1 de Cobranza corregida a control de Codex y esperar su verificación antes de arrancar Entrega 2.

## 2026-10-04 — LOG GEM CAB — Correcciones Entrega 1 Cobranza y responsive filtros (commit abc346a)

- **Objetivo:** Resolver observaciones de Codex y Carlos sobre la Entrega 1 de Cobranza (A51, A52, A18, A19), unificar estado de cobranza y verificar que la regla responsive no afecte negativamente otras pantallas.
- **Acciones y cambios:**
  - A51: Vuelta a una fila por registro (deporte + DNI). Columna renombrada a "Deuda" a secas (muestra la deuda propia de esa fila). Renglón chico de ayuda debajo del DNI ("también debe $X en [Deporte]") cuando el mismo DNI tiene deuda activa en otro registro.
  - A52: Cálculo de estado unificado en `CobranzaEstadoService::calcularEstadoDesdeDeudas(...)` basado estrictamente en cuotas. La inscripción impaga suma a la deuda pero no altera la etiqueta mensual ni en listado, ni en ficha, ni en resumen (ENT-01).
  - Eliminado color hex en línea en `cobranza/index.blade.php` (`var(--color-surface)`).
  - A18/A19: Regla `@media (max-width: 768px)` en `resources/css/app.css` para apilar selectores y fechas a ancho completo con altura táctil cómoda (48px). Inspección en pantalla a 375px en las 5 pantallas del sistema (Alumnos, Clases, Movimientos, Profesores, Historial de Cajas) confirmando legibilidad y usabilidad.
  - Aprobación de Carlos con línea `Diseno-autorizado:` y commit `abc346a`.
  - Cierre y actualización de defectos A18, A19, A21, A45, A46, A47, A51 y A52 en `docs/06-pruebas/PRU-02/DEFECTOS.md`.
  - Verificación: suite completa en `wings_testing_gemini` verde con 356 pruebas y 2073 aserciones.
- **Siguiente paso:** Proceder a la verificación independiente de Configuración (A11 / entrega de Codex).

## 2026-10-04 — LOG GEM CAB — Entrega 1 de P2: Cobranza desde cero

- **Objetivo:** Implementar la Entrega 1 de P2 (Cobranza) resolviendo los defectos A1, A21, A22, A45, A46 y A47 con estricto apego al Design System, validación de pantalla en vivo y aprobación explícita de Carlos.
- **Acciones y cambios:**
  - Mockups y capturas reales generadas y revisadas con Carlos en Desktop y Mobile (375px).
  - TDD en rojo primero: `tests/Feature/CobranzaEntrega1Test.php` (5 pruebas / 23 aserciones).
  - `CobranzaEstadoService`: método `listadoCobranza` con consolidación por DNI, deuda consolidada, orden por antigüedad de deuda impaga asc, cálculo de cuotas impagas y tarjeta `total_adeudado` en pesos en `resumenDashboard`.
  - `CobranzaWebController`: filtro por defecto a personas con deuda (`DEUDORES`), integración con selector `TODOS`, `AL_DIA`, etc.
  - `resources/views/cobranza/index.blade.php`: tabla de 7 columnas acordada con Carlos (Alumno, Deporte con badge cromático, Grupo, Deuda desde con período y cuotas impagas, Total deuda en una línea, Estado y Acciones), filtros canónicos en una sola fila (`filtros-row`), botones `Cobrar` y `Ver` fijos de 64px (`ds-btn-row`), y tarjetas superiores con borde superior cromático unificado y soporte para cifras millonarias sin salto de línea.
  - Pruebas en pantalla reales vía CDP: botón `Cobrar` probado en pantalla redirigiendo a la pantalla de cobro del alumno con sus cuotas pendientes cargadas; botón `Ver` probado abriendo la ficha del alumno; filtros combinados (Estado: TODOS + Deporte: Patín + Grupo: Principiantes) probados y validados; botón `Limpiar` probado restableciendo el listado al default de deudores.
  - Verificación: suite completa 100% verde (343 pruebas / 1955 aserciones). `DocumentacionNoMienteTest` y `TablerosNoDivergenTest` en verde.
- **Siguiente paso:** Pase a verificación por parte de Codex antes de iniciar Entrega 2.

## 2026-10-04 — LOG GEM CAB — Reversión de P2 en rama principal

- **Objetivo:** Restaurar `main` al estado previo a P2, revirtiendo todas las vistas y componentes modificados sin autorización previa de diseño de Carlos.
- **Acciones:**
  - Código, controladores, migración y pruebas de P2 resguardados en rama remota `backup/p2-gemini-20261004` para consulta o uso futuro.
  - Vistas Blade (`cobranza`, `alumnos`, `caja`, `clases`), controladores y migración revertidos en `main`.
  - Documentación (`ESTADO-ACTUAL.md`, `DEFECTOS.md`, `PLAN-PRODUCCION.md`) sincronizada con el estado restaurado (338 pruebas en verde).

## 2026-09-23 — LOG GEM CAB — Auditoría visual a simple vista (PRU-02)

- **Objetivo:** Relevar defectos a simple vista en `https://test.gestionar-te.com.ar` como usuario real del club, sin abrir código ni consultar base de datos, en navegador Chrome visible en Desktop (1366x768) y Mobile (375x720) para los tres roles.
- **Acciones y pantallas recorridas:**
  - Sesiones completas: ADMIN (`admin@wings.test`), OPERATIVO (`sandra.vidal@wings.test`) y PROFESOR (`lucia.gaitan@wings.test`).
  - Módulos auditados: Dashboard Admin/Operativo, Cobranza, Caja (mostrador, movimientos, historial), Alumnos (listado, ficha, alta, edición), Clases (listado, ficha, alta, edición, asistencia), Revisión de cobranza, Movimientos, Cashflow, Liquidaciones, Grupos, Niveles, Profesores, Rubros, Tipos de caja, Usuarios y Configuración, junto a intentos de acceso a rutas restringidas por rol.
  - Evidencia: 146 capturas de pantalla registradas en `docs/06-pruebas/PRU-02/evidencia/` con prefijo `audit_*`.
- **Hallazgos incorporados a `docs/06-pruebas/PRU-02/DEFECTOS.md` (A17 a A35):**
  - **Frena:** Ficha de alumno sin botón para cobrar (A17); Cobranza en móvil con tabla rota y textos superpuestos "AEBDORINE" / nombres montados (A18); Interruptor de usuario con estado invertido y auto-desactivación del propio admin (A32).
  - **Molesta:** Barras de filtros colapsadas en cuadrados mudos de 20px con botones desbordados en celular (A19); Botón "Nuevo" de Cashflow tapando el balance en móvil (A20); Cobranza duplicando filas de alumnos con dos deportes (A21); Dashboard admin vacío al 60% sin atajos (A23); Inicio operativo ofreciendo "Cobrar" sin caja abierta (A24); Celular obligatorio para menores en alta de alumno (A26); Formulario de movimiento en móvil con botones fuera de vista (A27); Grupos en móvil con tarifas desbordadas y switch pegado a Editar (A28); Redirección silenciosa a Caja para operativo en administración (A29); Respuestas dispares (403 vs redirect silencioso) ante accesos no autorizados (A30); Botón de pantalla 403 derivando a `/login` (A31); Asistencia en celular con scroll vertical excesivo (A33); Botón redundante "Historial" en historial de cajas (A35).
  - **Falta:** Resumen de Cobranza sin totales monetarios de deuda (A22); Apertura de caja sin campo ni arqueo de cambio inicial (A25); Ficha de alumno sin historial ni descarga de recibos emitidos (A34).
- **Cierre:** Anotados 19 nuevos defectos en `docs/06-pruebas/PRU-02/DEFECTOS.md` con captura y gravedad. Código de diseño (`resources/views` y `resources/css`) intacto.
- **Siguiente paso:** Presentar el informe visual consolidado a Carlos para coordinar la priorización de soluciones.

## 2026-09-23 — LOG GEM CAB — PRU-02 Día 1: Configuración y Carga inicial

- **Objetivo:** Ejecutar la Parte 1 de PRU-02 (la mañana de Vanina desde su casa previa a la apertura del club) en `https://test.gestionar-te.com.ar` en navegador visible (Chrome) y sin tocar SQL/tinker/seeders.
- **Acciones y verificaciones realizadas:**
  - Reloj simulado: fijado a `2026-09-24 09:00` y al concluir restablecido a hora real con `wings:fecha-simulada --real`.
  - Profesores: revisados los 4 existentes y creada Vanina Directora (Patín, tarifa por hora $0), verificando la creación automática de su subrubro de sueldo.
  - Clases del cronograma: cargadas 76 clases recurrentes grupo por grupo (24/09 al 31/10) cubriendo los 6 grupos; verificadas clases en octubre y canchas paralelas el lunes a las 16:00.
  - Intentos a propósito:
    * Solapamiento de profesor: bloqueado con mensaje explicativo en pantalla (captura `dia01_02_intento_solapamiento_profesor.png`).
    * Clase 17:30 a 18:30 (fraccionada / 2 horas de cancha): aceptada sin aviso y cancelada desde su detalle (capturas `dia01_04_clase_1730_dos_horas_cancha.png` y `cancelada.png`).
  - Padrón: inspeccionado `/alumnos` (falta información de plan, celular propio, fecha de ingreso y deuda en listado general).
  - Cobranza: detectada inconsistencia crítica (Dashboard informa 20 con deuda, pero `/cobranza` califica a los 60 alumnos como DEUDORES por falta de pagos registrados en BD).
  - Configuración: verificada tarifa $5.000, 10 días de gracia, reglas primer cobro y destinos de avisos; fecha de corte de solo lectura (23/09/2026); probado cambio de importe y detectada falla silenciosa ante valores inválidos.
  - Rubros y subrubros: confirmados Alquileres; creados EGRESO "Mantenimiento y arreglos" con "Reparaciones menores" (OPERATIVO) e INGRESO "Clases Particulares" con "Clase particular" (OPERATIVO); verificado que Cuotas e Inscripciones son intocables.
  - Tipos de caja: activadas las 3 cuentas bancarias que venían inactivas por defecto.
- **Entregables:** Documentado en `docs/06-pruebas/PRU-02/HALLAZGOS-DIA-01.md` y 16 capturas en `docs/06-pruebas/PRU-02/evidencia/`. Sin tocar tableros ni planes.

## 2026-09-23 — LOG GEM CYE — ENT-01: Verificación de inscripción y cargos adicionales

- **Objetivo:** Verificar la implementación de ENT-01 (commits `11623b6` y `85b4ead` de Codex) sobre la lógica de inscripción, prioridad de cobro, comisiones, anulaciones y pruebas visuales en local.
- **Respuestas técnicas y verificación en código:**
  - Estado de cobranza por inscripción impaga: **NO** cambia (`CobranzaEstadoService:29, 217-254`).
  - Pago solo inscripción como cuota: **NO** cuenta, scope `conCuota()` filtra `monto_cuota > 0` (`CobranzaEstadoService:30, 62`, `Pago:71`).
  - Descuento primer mes: **SOLO A CUOTA**, inscripción son $5.000 fijos (`PagoCuotaService:58-69, 916-934`).
  - Comisión de profesor: **SOLO SOBRE CUOTA** (`LiquidacionService:223`), pagos históricos conservan base previa intacta (`monto_cuota` backfilled en migración).
  - Anulación de cobro: **REVIERTE TODO** (cuota, inscripción y 2 movimientos cancelados) (`PagoCuotaService:871-910`).
  - Unicidad por DNI: **GARANTIZADA EN BD** con índice UNIQUE en `clave_origen` y PK en `inscripcion_personas`.
- **Pruebas visuales en pantalla local (Apache + MariaDB):**
  - Ejecutados los 7 flujos con Chrome Headless CDP (`scratch/test_ent01_suite.mjs`), generando 11 capturas de evidencia: alta pre-corte, alta post-corte con aviso, segundo deporte sin duplicar, cobro completo ($26.000) con desglose en caja, cobro parcial ($3.000 a inscripción), edición sin pagos (aviso/anulación), edición con pago (rechazo), reversión tras anulación y responsivo mobile 375x700.
- **Dictamen `storage/ent01-visual/recibo.png`:** Muestra de Poppler generada por Codex. Dictamen: sacar de `storage/` y mover a `docs/06-pruebas/evidencia/` para mantener `storage/` fuera de git.
- **CSP y Responsivo:** CSP intacta (sin inline handlers en `cobrar.blade.php`); desglose responsivo legible en 375x700 sin desbordes.
- **Suite completa:** 314 pruebas / 1797 aserciones en verde (94.5s), sincronizados documentos de control.
- **Siguiente paso:** Aprobación de Carlos y coordinación para actualización del servidor `test.gestionar-te`.

## 2026-09-22 — LOG GEM CYE — ENT-06: Verificación y Cierre

Todos los entregables y requisitos fueron verificados con evidencia concreta:

- **Pull inicial:** Rama actualizada con `origin/main` al comenzar.
- **Aislamiento de concurrencia con Codex (ENT-01):** Staging quirúrgico sin usar `git add .` ni `git add -A`. Se preservaron intactos los archivos en progreso de Codex.
- **Servicio y formato de avisos (`app/Services/AvisoAdminService.php`):**
  - `resumenDiario(): bool` implementado reutilizando `enviar()` y `AvisoOperativo` por correo y Telegram.
  - Cajas cerradas sin validar: total neto y más vieja con usuario y fecha (`route('web.caja.index')`).
  - Revisiones de cobranza pendientes: más vieja con alumno y fecha (`route('web.revision-cobranza.index')`).
  - Liquidaciones: cerradas sin pagar con total monetario, y abiertas registradas aparte sin sumarse al total (`route('web.liquidaciones.index')`).
  - Si no hay pendientes, retorna `false` sin emitir avisos.
- **Comando y Scheduler:**
  - `app/Console/Commands/ResumenDiarioAvisosCommand.php` (`avisos:resumen-diario`).
  - Programado en `routes/console.php` a las 08:00 (`0 8 * * *`).
- **Pruebas funcionales y suite:**
  - `tests/Feature/AvisoAdminResumenDiarioTest.php`: 5 pruebas aprobadas cubriendo todos los escenarios exigidos.
  - Suite completa de PHPUnit: 313 pruebas / 1793 aserciones en verde.
  - `DocumentacionNoMienteTest` y `TablerosNoDivergenTest`: 100% aprobadas.
- **Prueba real con datos locales:**
  - Ejecutado `php artisan avisos:resumen-diario` -> salida "Resumen diario de avisos enviado correctamente." y contenido verificado en `storage/logs/laravel.log`.
- **Documentación y tableros actualizados:**
  - Sincronizados `ESTADO-ACTUAL.md`, `CHECKLIST-CARLOS.md`, `PLAN-PRODUCCION.md`, `PLAN-TRABAJO-IA-v2026-09-08.md`, `PLAN-TRABAJO-CARLOS-v2026-09-08.html` y la bitácora `LOG-GEMINI.md` (firma `LOG GEM CYE`).
- **Sin cambios de diseño ni deploy:**
  - Sin modificar vistas Blade ni CSS.
  - Commit `e8b2824` subido a `origin/main` listo para cuando Claude actualice el servidor.

## 2026-09-22 — LOG GEM CYE — FIN-08: Retoques de pantalla de Revisión autorizados por Carlos

- **Objetivo:** Aplicar los tres retoques de diseño y UX detectados durante la verificación de FIN-08 en `resources/views/revision-cobranza/index.blade.php`, autorizados expresamente por Carlos el 22/09.
- **Cambios reales:**
  1. Renderizado visible de `$errors` en banner superior `.ds-flash.ds-flash--error` sin JavaScript ni reabrir formularios, mostrando mensajes de validación cuando la nota es menor a 5 caracteres o está vacía.
  2. Filtros responsivos: reemplazada grilla inline rígida por `.filtros-row` con `flex-wrap:wrap; align-items:end;` y campos con `flex:1; min-width:140px;` para que se apilen adecuadamente en celular sin apretarse.
  3. Eliminado el bloque `<script>` duplicado (`abrirForm` y `cerrarForm`), activando la delegación nativa `[data-abrir-revision]` y `[data-cerrar-revision]` ya implementada en `resources/js/ds-app.js`.
  4. Reducida la constante `BLOQUES_SCRIPT_PERMITIDOS` de 22 a 21 en `tests/Feature/CspSinCodigoIncrustadoTest.php`.
- **Verificación:** Vistas compilan (`view:cache` y `view:clear`), CSP test pasa en verde, suite completa pasa con 290 pruebas / 1675 aserciones.
- **Prueba real en pantalla (Chrome headless/CDP):**
  1. *Abrir:* Clic en `Continúa` oculta botones (`display: none`), despliega formulario (`display: block`), enfoca textarea y setea `res-tipo = CONTINUA`.
  2. *Cancelar:* Clic en `Cancelar` oculta formulario y restaura botones (`display: flex`).
  3. *Confirmar con 2 letras:* Envío de "ok" dispara validación del servidor (min:5) y renderiza el banner `.ds-flash.ds-flash--error` superior con "The nota resolucion field must be at least 5 characters".
  4. *Mobile (375x700):* `.filtros-row` con `flex-wrap: wrap` apila los filtros y el botón Limpiar sin desbordes ni compresión. Cuatro capturas guardadas como evidencia. Sin deploy.

## 2026-09-21 — LOG GEM CYE — FIN-08: Verificación exhaustiva y auditoría de permisos

- **Objetivo:** Auditar y verificar minuciosamente la tarea FIN-08 (commit `5dff231`), que habilita la resolución de revisiones de cobranza al rol OPERATIVO ("Continúa" / "Inactivo"), evaluando los 9 puntos del pedido de Carlos y buscando fallas potenciales sin alterar código.
- **Verificaciones y Resultados:**
  1. OPERATIVO entra a Revisión por menú "Día a día" (HTTP 200) y filtros por estado y período funcionan correctamente.
  2. "Continúa" genera la cuota en `deuda_cuotas` con precio del plan activo, conserva al alumno activo y aparece en Cobranza y ficha de alumno.
  3. "Inactivo" da de baja al alumno (`activo = 0`) y deja la deuda anterior y pagos parciales 100% INTACTOS (no se condona nada).
  4. Doble resolución simultánea (dos pestañas) es interceptada por `RevisionCobranzaService` y rechazada con error claro ("Esta revisión ya fue resuelta.").
  5. PROFESOR queda completamente excluido: sin enlace en menú, GET `/revision-cobranza` da 403 y POST resolver da 403.
  6. ADMIN conserva condonación desde la ficha del alumno; OPERATIVO no tiene el botón y todo intento POST a condonar es bloqueado por `EnsureAdminWeb` (302 a `/caja`).
  7. Menú responsive verificado: único `<aside class="ds-sidebar">` off-canvas en mobile, sin duplicación de enlaces en el DOM para ningún rol.
  8. Regresión: Alumnos, Cobranza, Caja y Clases intactos. Suite completa en verde con **288 pruebas pasando**.
- **Hallazgos documentados:** Creado informe completo en `docs/06-pruebas/FIN-08-VERIFICACION-2026-09-21.md`. Se reportaron 3 observaciones visuales/UX para corregir cuando Carlos autorice diseño (Hallazgo 1: falta renderizar `$errors` en `revision-cobranza/index.blade.php`; Hallazgo 2: grilla inline fija de filtros en mobile; Hallazgo 3: script inline redundante reemplazable por la delegación nativa de `ds-app.js`).

## 2026-09-21 — LOG GEM CYE — FIN-12: Cancelar liquidación cerrada no pagada

- **Objetivo:** Implementar la enmienda del contrato §2.4 (`docs/05-pendientes/FIN-12-CANCELAR-LIQUIDACION-CERRADA.md` y `docs/02-contratos/LIQUIDACIONES_CONTRATO_V2.md`): permitir que ADMIN cancele una liquidación en estado `CERRADA` con `estado_pago = PENDIENTE` con motivo obligatorio para revisar asistencias y regenerar, preservando detalle y auditoría. Liquidaciones `PAGADA` son estrictamente intocables para todos los perfiles.
- **Cambios reales:**
  1. `database/migrations/2026_09_21_120000_permitir_cancelar_liquidacion_cerrada_no_pagada.php`: Agregó `'CANCELADA'` al enum de `estado`, columnas de auditoría (`usuario_cancelacion_id`, `cancelada_at`, `motivo_cancelacion`, `reemplazada_por_id`). Se creó índice no único `['profesor_id', 'mes', 'anio']` antes de soltar la restricción única (evitando error 1553 MariaDB con FK). Probado en migrate y rollback.
  2. `app/Models/Liquidacion.php`: Constante `ESTADO_CANCELADA = 'CANCELADA'`, casts, fillables, relaciones (`usuarioCancelacion`, `reemplazadaPor`, `liquidacionesCanceladas`). En `boot()`: permite transición a `CANCELADA` solo con motivo y usuario si está `PENDIENTE`, bloquea mutaciones posteriores excepto `reemplazada_por_id`, y prohíbe eliminar canceladas.
  3. `app/Services/LiquidacionService.php`: `cancelarLiquidacion()` implementado con `lockForUpdate()` y validaciones de CERRADA y PENDIENTE. `validarNoExisteLiquidacion()` excluye canceladas permitiendo regeneración. `generarLiquidacionMensual()` asocia canceladas previas mediante `reemplazada_por_id`. `obtenerResumenPeriodo()` excluye canceladas del total monetario.
  4. `app/Models/Clase.php` y `app/Http/Controllers/ClaseWebController.php`: `Clase::integraLiquidacionCerrada()` bloquea alteración de asistencias (422) tanto en HORA (por detalle) como en COMISIÓN (por profesor/mes/año de liquidación cerrada). La cancelación desbloquea las asistencias.
  5. `routes/web.php` y `app/Http/Controllers/LiquidacionWebController.php`: Ruta `POST /liquidaciones/{id}/cancelar` protegida con `ensure.admin.web`. Filtro `cancelada` en index y show con relaciones de reemplazo y usuario cancelación.
  6. `resources/views/liquidaciones/show.blade.php` e `index.blade.php`: Tarjeta de cancelación con confirmación nativa `data-confirmar` (sin inline handlers, conteo CSP intacto en 10 manejadores) y motivo obligatorio. Badge `Cancelada` en listado y ficha.
- **Pruebas:** Creados `tests/Feature/CancelarLiquidacionCerradaTest.php` (9 pruebas funcionales) y `tests/Feature/CancelarLiquidacionConcurrenteTest.php` (2 pruebas concurrentes con dos conexiones MariaDB reales probando pago vs cancelación y cancelación vs pago con locks transaccionales). Suite completa verde: 274 pruebas / 1567 aserciones. Sin deploy.

## 2026-09-19 — LOG GEM CYE — FIN-09: Límites de fechas y confirmación al cargar movimientos y cobros

- **Objetivo:** Implementar la decisión de Carlos sobre límites de fechas al registrar o editar movimientos de caja, cashflow y cobro de cuotas: rechazar fechas futuras, permitir fechas del mes actual sin aviso, y exigir confirmación en pantalla antes de guardar si la fecha es de un mes anterior o más vieja.
- **Cambios reales:**
  1. `app/Http/Controllers/CajaWebController.php`: Validación estricta `fecha => required|date|before_or_equal:today` en `editarStore` y `updateMovimiento`. Si la fecha es anterior al inicio del mes en curso y no viene `confirmar_fecha_vieja=1`, retorna `withInput()` y aviso explicativo en sesión. En `pagar`, si `fecha_pago` es de mes anterior sin `confirmar_fecha_vieja=1`, responde HTTP 409 con `requiere_confirmacion_fecha_vieja: true`. Marcados puntos con TODO para futuros avisos por Mail/Telegram al ADMIN.
  2. `app/Http/Controllers/CashflowWebController.php`: Validación idéntica `before_or_equal:today` y confirmación de fecha vieja en `store`.
  3. `resources/views/caja/editar.blade.php` y `resources/views/cashflow/movimiento.blade.php`: Banner informativo con tokens DS (`var(--color-warning)`) cuando hay aviso de fecha vieja, campo oculto `confirmar_fecha_vieja=1` y botón de submit actualizado a `Confirmar` (un solo verbo corto). Sin scripts nuevos (conteo CSP intacto en 22 bloques y 10 manejadores).
  4. `resources/views/caja/cobrar.blade.php`: Interceptor en `enviarCobro()` ante respuesta 409 para confirmar la fecha anterior e invocar segundo envío con `confirmar_fecha_vieja=1`.
- **Pruebas:** Creada suite `tests/Feature/LimiteFechasMovimientosTest.php` (8 pruebas, 53 aserciones) cubriendo rechazo de fecha futura, mes actual sin aviso, bordes de fin/inicio de mes, tres meses atrás preservando cajas cerradas/validadas, edición de movimiento, cashflow y cobro de cuotas. Suite completa en 251 pruebas / 1491 aserciones. Sin deploy.

## 2026-09-17 — LOG GEM CYE — FIN-13: Pantalla y recibo de liquidación por hora según duración

- **Objetivo:** Aplicar la autorización de diseño de Carlos para mostrar la duración en formato "1 h 20 min" y subtotales/totales con 2 decimales en la pantalla y en el recibo PDF de liquidaciones por hora.
- **Cambios reales:**
  1. `app/Services/ReciboService.php`: Agregado método público estático `formatearDuracion(?int $minutos)`. En anexo HORA se mapea `'minutos' => $minutos` en detalles y se formatea `conteo_texto` como "X clases dictadas · 3 h 20 min".
  2. `resources/views/pdfs/recibo-liquidacion.blade.php`: Encabezado de columna cambiado a "Duración"; celda muestra `formatearDuracion($det['minutos'])` ("1 h", "1 h 20 min", "30 min"). Verificado PDF real: 2 páginas exactas, sin desbordes ni hojas en blanco.
  3. `resources/views/liquidaciones/show.blade.php`: "Valor por clase" actualizado a "Valor por hora". Duración toma `$detalle->minutos` vía `formatearDuracion` (fallback a "—" si es null). Subtotales de fila y total HORA formateados con 2 decimales.
- **Pruebas:** Creado `tests/Feature/LiquidacionHoraVistaYReciboTest.php` (3 pruebas comprobadas fallando contra el código previo; ahora pasan verde con 27 aserciones). Suite completa en 238 pruebas / 1429 aserciones. Sin deploy.

## 2026-09-16 — PENDIENTES comunes (registrado por Claude CAB a pedido de Carlos)

Misma entrada en los tres logs, para que cada agente arranque con la lista.
Corte: `main` con todo subido; suite 229 pruebas / 1374 aserciones, verde el 16/09.

**Cerrado desde el 13/09:** FIN-06 (Gemini), verificacion cruzada de FIN-10 y FIN-03
(Gemini), ENT-02 recibos de cuota y liquidacion (Gemini, commiteado el 16/09),
SEG-06, SEG-07 y `report-uri` de CSP (Claude), `test.gestionar-te` montado (Claude).

**Pendiente, por orden:**

1. **Actualizar `test.gestionar-te`** — Claude. Esta en `2fccacb`, **sin los recibos
   nuevos**: ENT-02 no estaba commiteado cuando se actualizo. Correr
   `montar-test.sh` y `montar-test-https.sh`.
2. **FIN-12** cancelar liquidacion cerrada no pagada — sin asignar (propuesta:
   Gemini). Contrato enmendado en Liquidaciones §2.4; instrucciones en
   `docs/05-pendientes/FIN-12-CANCELAR-LIQUIDACION-CERRADA.md`.
3. **Prueba grande PRU-02** en `test.gestionar-te` — Gemini, despues de 1 y 2.
   Entrar con `admin@wings.test` / `PruebaWings2026`. No resetear la base por su
   cuenta: pedirlo a Claude.
4. **Ensayo de restauracion contra un respaldo real del servidor** — Claude, no
   toca nada. Hasta hacerlo, que el respaldo sirva no esta demostrado.
5. **Desplegar en wings** — despues de que pase 3. Respaldo manual antes y con
   Carlos presente. Migraciones pendientes: `detalle_anulacion`,
   `motivo_cambio_horario`, `porcentaje_comision_aplicado` (ya probadas en test).

**Clases particulares** — Codex. Contrato en `Wings-Contrato-Clases-Particulares-V1.md`.
Va en rama aparte; **falta que Carlos decida si entra antes o despues de la prueba grande**.

**Decisiones de Carlos que no bloquean la prueba:** FIN-04 (balance), FIN-08
(revision con parciales), FIN-09 (limites de fechas), PRU-03 (DEUDOR sin pagos),
ENT-01 (inscripcion), liquidacion por hora: por clase o por duracion, `monto_base`.

**Sin asignar, no bloquean:** SEG-10 integracion continua; SEG-11 resto (22 bloques
`<script>`, uno por archivo segun DESIGN-RULES §8); ENT-06/07/08 despues de la prueba.

## 2026-09-13 — LOG GEM CAB — ENT-02 Implementación y verificación visual final de recibos PDF

- **Objetivo:** Aplicación del diseño de comprobantes PDF aprobado por Carlos y Vanina el 11/09 (`INSTRUCTIVO-IMPLEMENTACION-RECIBOS.md §4.2`) a `recibo-liquidacion.blade.php`, soporte de anexo detallado en `ReciboService.php`, y saneamiento del texto de observaciones en `recibo-cuota.blade.php`.
- **Diseño autorizado por Carlos:** `Diseno-autorizado: aplicar el diseño de recibos que Carlos y Vanina aprobaron el 11/09 (INSTRUCTIVO-IMPLEMENTACION-RECIBOS.md §4.2), nunca aplicado a recibo-liquidacion.blade.php`
- **Cambios reales implementados:**
  1. `resources/views/pdfs/recibo-liquidacion.blade.php`: Reemplazada la plantilla histórica verde por el diseño institucional en pizarra `#0F172A` de 2 páginas exactas A5 vertical (`148mm × 210mm`, márgenes `10mm 12mm 10mm 12mm`). Hoja 1: Resumen ejecutivo con logo institucional en marco oscuro `.logo-frame`, período, total liquidado, medio e imputación contable, y firma de conformidad. Hoja 2: Anexo cronológico de clases dictadas (modalidad HORA) o nómina de alumnos comisionados con cuota base, porcentaje y comisión (modalidad COMISIÓN).
  2. `app/Services/ReciboService.php`: En `generarReciboLiquidacion()`, agregada carga de la relación `detalles`, mapeo batch de referencias (`Clase` con grupo o `Alumno`) sin N+1, cálculo de horas/conteo y estructuración de la colección `$detalles` requerida por la Página 2. En `generarReciboCuota()`, se mantiene compatibilidad de `$observaciones` y se evita la duplicación visual en `recibo-cuota.blade.php` limpiando el motivo repetido al renderizar.
  3. `resources/views/pdfs/recibo-cuota.blade.php`: Limpiado el motivo de anulación del bloque de observaciones al renderizar pagos cancelados, mostrando observaciones originales y motivo de cancelación en campos independientes.
- **Verificaciones:**
  - `php -l`: sintaxis limpia en `ReciboService.php`, `recibo-cuota.blade.php` y `recibo-liquidacion.blade.php`.
  - `php artisan view:cache; php artisan view:clear`: plantillas Blade compiladas sin errores.
  - `php artisan test`: suite completa con **229 pruebas pasadas / 1374 aserciones** (100% verde).
  - Inspección visual en pantalla vía screenshots Edge headless: los 4 comprobantes (cuota 1 mes, cuota 3 meses, cuota estrés con nombre largo, cuota anulado, liquidación hora 2 páginas y liquidación comisión 2 páginas) confirmados prolijos y listos para su entrega en el mostrador.
- **Siguiente paso:** ENT-02 completado y validado en pantalla; continuar según prioridades de Carlos.

## 2026-09-13 — LOG GEM CAB — FIN-03 Verificación cruzada de detalle de anulación en recibos

- **Objetivo:** Verificación cruzada de la implementación de FIN-03 (commit `c1bef8a` de Codex) contra el contrato `Wings-Contrato-Recibos-PDF-V2.md §7.c`.
- **Entorno:** Base aislada `wings_testing_fin10_gemini_20260913` con cobro de 2 meses (`2026-08` y `2026-09`, $60.000) por caja operativa y anulación posterior desde la UI web como `admin@wings.test`.
- **Resultados (100% verificado):**
  1. Cobro de dos meses y posterior anulación: el recibo anulado (`GET /recibos/cuota/{id}`) conserva y muestra ambos períodos con sus importes ($30.000 cada uno, total $60.000 revertido), sello visible y bloque de auditoría con fecha, usuario y motivo.
  2. Base de datos: `pagos.detalle_anulacion` almacena la copia documental JSON con motivo y períodos/montos; `pago_deuda_cuota` queda en 0 filas (imputaciones activas eliminadas atómicamente).
  3. Deuda revertida: las cuotas vuelven a estado `PENDIENTE` con `monto_pagado = 0.00`; saldo total del alumno vuelve de $0 a $60.000 exactos.
  4. Caso legacy: pago anulado previo a FIN-03 sin `detalle_anulacion` no inventa períodos (muestra leyenda explicativa "sin detalle de períodos disponible").
- **Evidencia:** PDFs reales generados en `scratch/recibo_anulado_cuota_3.pdf` y `scratch/recibo_anulado_legacy_4.pdf`. Suite `ReciboMedioDePagoTest` (6 tests, 66 assertions) en verde.
- **Siguiente paso:** Tarea FIN-03 cerrada formalmente; continuar según prioridades de Carlos.

## 2026-09-13 — LOG GEM CAB — FIN-10 Verificación cruzada de edición de clases en pantalla y base

- **Objetivo:** Verificación cruzada contra el contrato `Wings-Contrato-Clases-Asistencias-V1.md §4.c` de la implementación de FIN-10 (commit `c17b3d0` de Codex).
- **Entorno:** Base aislada `wings_testing_fin10_gemini_20260913` con `PrimeraCargaCompletaSeeder` y servidor web autenticado como `admin@wings.test`. Clases pasadas con asistencia creadas vía formulario y ajustada su fecha en base para cumplir restricción de creación.
- **Resultados (10 de 10 casos cumplidos):**
  1. Fechas pasadas inmutables y no movibles al pasado (validación rechaza; fecha en BD intacta).
  2. Horario en clase pasada exige motivo obligatorio; al enviarlo se persiste en `clases.motivo_cambio_horario`.
  3. Edición de horario bloqueada si integra liquidación CERRADA; permitida si está ABIERTA.
  4. Lista de profesores inmutable en clases pasadas (parámetro ignorado en backend; relación en `clase_profesor` intacta).
  5. Clase de hoy permite cambio de fecha, horario y profesores sin pedir motivo.
  6. Superposición de alumno presente en otra clase rechaza con error amigable y ejecuta rollback atómico de la transacción (horario y motivo en BD intactos).
  7. Guardar clase pasada sin cambios no exige motivo (redirección a show con éxito; datos en BD intactos).
- **Evidencia:** Informe detallado comparativo en chat y persistido en `scratch/resultado_verificacion_fin10.json` y `scratch/resultado_sin_cambios.json`.
- **Siguiente paso:** Tarea FIN-10 cerrada formalmente; continuar según prioridades de Carlos.

## 2026-09-13 — LOG GEM CAB — FIN-06 Comisión histórica en liquidaciones

- **Objetivo:** Garantizar que las liquidaciones por comisión se calculen y recalculen con hechos del pasado y no con datos del presente, desacoplando el cálculo del estado actual del alumno y congelando el porcentaje de comisión del profesor al momento de crear la liquidación.
- **Cambios reales:**
  1. `database/migrations/2026_09_13_190000_add_porcentaje_comision_aplicado_to_liquidaciones_table.php`: Agregada columna `porcentaje_comision_aplicado` (`decimal(5,2)`, nullable) a la tabla `liquidaciones` con backfill automático para liquidaciones preexistentes de tipo `COMISION` tomando el porcentaje actual de su profesor.
  2. `app/Models/Liquidacion.php`: Agregado `porcentaje_comision_aplicado` a `$fillable` y a `$casts` (`'decimal:2'`).
  3. `app/Services/LiquidacionService.php`:
     - En `generarLiquidacionMensual()`: se congela `porcentaje_comision_aplicado` al crear la liquidación si es de tipo `COMISION` (`$profesor->porcentaje_comision`).
     - En `calcularLiquidacionComision()`: se usa `$liquidacion->porcentaje_comision_aplicado ?? $profesor->porcentaje_comision ?? 0`, garantizando inmutabilidad histórica ante cambios posteriores en el porcentaje del profesor.
     - Removidos los filtros de estado actual `->where('activo', true)` y `->where('deporte_id', $deporteId)` de la consulta de alumnos con pago. Solo hechos históricos deciden la comisión: pago del período completado y asistencia confirmada (`presente = true`) a clase no cancelada del profesor en ese mes.
  4. `app/Http/Controllers/LiquidacionWebController.php`: En `show()`, si la liquidación es `COMISION` y tiene `porcentaje_comision_aplicado`, se asigna en memoria a `$liquidacion->profesor->porcentaje_comision` y se llama inmediatamente a `$liquidacion->profesor->syncOriginal()`. Esto mantiene el porcentaje visible para la vista Blade (diseño intacto, Regla 1) pero limpia el estado "dirty" del modelo en memoria, previniendo que una llamada posterior o accidental a `save()` persista el porcentaje congelado en la tabla de profesores.
  5. `tests/Feature/LiquidacionComisionHistoricaTest.php`: Creada suite de pruebas con 7 tests cubriendo: alumno dado de baja con pago/asistencia pasada, alumno que cambia de deporte, cambio posterior de porcentaje del profesor sin alterar liquidación recalculada, liquidación legacy con porcentaje null usando porcentaje actual, anulación de pago que retira la comisión en recálculo, alumno con pago sin asistencia que no genera comisión, y vista show mostrando el porcentaje congelado con verificación de que `profesor->isDirty()` es falso y `save()` no altera la base de datos.
  6. Documentación actualizada: `ESTADO-ACTUAL.md`, `CHECKLIST-CARLOS.md`, `PLAN-PRODUCCION.md` y `RESUMEN-ARRANQUE.md` sincronizados en 229 pruebas / 1372 aserciones. Registrada decisión pendiente sobre `valor_hora` en liquidaciones por hora.
- **Verificaciones:**
  - `php -l`: sintaxis limpia en modelos, servicios, controladores, migraciones y tests.
  - `php artisan migrate`: migración ejecutada sin errores.
  - `php artisan test --filter LiquidacionComisionHistoricaTest`: 7 pasaron (26 assertions).
  - `php artisan test --filter DocumentacionNoMienteTest`: 1 passed (7 assertions).
  - `git diff --stat -- resources/views resources/css`: vacío (diseño intacto).
  - `php artisan test`: suite completa con **229 pruebas pasadas**, 100% verde.
- **Siguiente paso:** Próxima tarea del bloque FIN / SEG según prioridades de Carlos.

## 2026-09-13 — LOG GEM CAB — SEG-11 Scripts de grupos y usuarios a sus propios archivos JS

- **Objetivo:** Migrar los bloques `<script>` de `grupos/_form.blade.php` y `usuarios/_form.blade.php` a sus propios archivos dedicados (`resources/js/grupos.js` y `resources/js/usuarios.js`), compilados por Vite y cargados por sus vistas mediante `@vite` vía `@push('scripts')`, conforme a la regla de modularidad de `DESIGN-RULES.md §8`.
- **Cambios reales:**
  1. `resources/js/grupos.js`: Creado para la lógica de grupos (verificación de deporte+nivel vía AJAX `/grupos/check-disponible`, carga y borrado de precios por frecuencia dinámicos vía `#btn-add-plan` y `.btn-remove-plan`, formateo de montos vía `window.initMoneyInput` e índice inicial leído de `data-initial-idx`).
  2. `resources/js/usuarios.js`: Creado para la lógica de usuarios (verificación de email único vía AJAX `/usuarios/check-email`, validación cruzada de contraseña/confirmación, resalte visual de radio `.rol-label` y visibilidad condicional de `#panel-profesor`).
  3. `vite.config.js`: Declarados `resources/js/grupos.js` y `resources/js/usuarios.js` en los inputs del plugin Laravel.
  4. `resources/views/grupos/_form.blade.php`: Reemplazado bloque `<script>` por `@push('scripts') @vite('resources/js/grupos.js') @endpush` y agregado `data-initial-idx` al contenedor de planes.
  5. `resources/views/usuarios/_form.blade.php`: Reemplazado bloque `<script>` por `@push('scripts') @vite('resources/js/usuarios.js') @endpush`.
  6. Compilados assets de producción con Vite (`npm run build`).
  7. `tests/Feature/CspSinCodigoIncrustadoTest.php`: Reducida la constante `BLOQUES_SCRIPT_PERMITIDOS` de 24 a 22.
- **Verificaciones:**
  - `node -c`: sintaxis JS limpia en `grupos.js` y `usuarios.js`.
  - `php -l`: sintaxis limpia en vistas Blade modificadas y en el test de CSP.
  - `php artisan view:cache; php artisan view:clear`: compilación de plantillas Blade OK.
  - `php artisan test --filter CspSinCodigoIncrustadoTest`: 2 passed (2 assertions).
  - Pruebas funcionales de roles (`ADMIN`, `OPERATIVO`, `PROFESOR` con vínculo obligatorio) y precios por frecuencia con separador de miles y borrado dinámico (`test_funcional_seg11.php`) aprobadas con éxito.
  - `git diff --stat -- resources/css`: vacío (diseño 100% intacto).
  - `php artisan test`: suite completa verde en el nuevo corte con **222 pruebas pasadas** (1347 assertions).
- **Siguiente paso:** Tareas restantes de SEG-11 o siguientes pasos según defina Carlos.

## 2026-09-13 — LOG GEM CAB — SEG-11 Validador de nombre repetido unificado en ds-app.js

- **Objetivo:** Unificar en `resources/js/ds-app.js` el validador en vivo de disponibilidad / nombre repetido compartido por `niveles/_form.blade.php` y `tipos-caja/_form.blade.php`, eliminando sus scripts en línea sin tocar el diseño y preparando el soporte para campos combinados (futuro `grupos`).
- **Cambios reales:**
  1. `resources/js/ds-app.js`: Agregado validador delegado sobre `[data-verificar-disponible]` que consulta el endpoint vía fetch AJAX con `X-Requested-With: XMLHttpRequest`. Maneja eventos `blur` e `input` dinámicos, muestra/oculta el error en vivo, limpia opcionalmente el error previo del servidor (`data-verificar-error-sv`) y deshabilita/habilita el botón de envío del formulario. Preparado para múltiples campos combinados (`data-verificar-combinado`).
  2. `resources/views/niveles/_form.blade.php`: Configurado input `#nombre` con atributos `data-verificar-*` y removido su bloque `<script>` de 40 líneas.
  3. `resources/views/tipos-caja/_form.blade.php`: Configurado input `#nombre` con atributos `data-verificar-*` y removido su bloque `<script>` de 43 líneas.
  4. `grupos` y `usuarios` quedaron afuera por evaluación de alcance acordada: `grupos` valida combinación deporte+nivel y aloja la tabla dinámica de planes; `usuarios` valida contraseñas cruzadas, gestiona selección de roles y el panel de profesor.
  5. Compilados assets con Vite (`npm run build`).
  6. `tests/Feature/CspSinCodigoIncrustadoTest.php`: Reducida la constante `BLOQUES_SCRIPT_PERMITIDOS` de 26 a 24.
- **Verificaciones:**
  - `php -l`: sintaxis limpia en vistas, JS y tests.
  - `php artisan view:cache; php artisan view:clear`: compilación Blade OK.
  - `php artisan test --filter CspSinCodigoIncrustadoTest`: 2 passed (2 assertions).
  - `php artisan test --filter TablerosNoDivergenTest`: 2 passed (9 assertions).
  - Renderizado HTML probado (`scratch/test_verificar_html.php`) con atributos correctos en create y edit.
  - `git diff --stat -- resources/css`: vacío (diseño intacto).
  - `php artisan test`: 214 tests pasaron (1315 assertions).
- **Siguiente paso:** Próximos pasos de SEG-11 o tareas del bloque de entregables según indique Carlos.

## 2026-09-13 — LOG GEM CAB — SEG-11 Bajar los 14 onclick de las vistas

- **Objetivo:** Migrar los 14 manejadores `onclick` en línea de las vistas Blade hacia JavaScript externo (`resources/js/ds-app.js`) mediante atributos `data-*` y delegación global de eventos, allanando el camino para el endurecimiento de la CSP (`script-src 'self'`).
- **Cambios reales:**
  1. `resources/js/ds-app.js`: Agregado módulo de delegación global para `[data-confirmar]`, `[data-abrir-condonar]`, `[data-cerrar-condonar]`, `[data-abrir-rechazar]`, `[data-cerrar-rechazar]`, `[data-abrir-cancelar]`, `[data-cerrar-cancelar]`, `[data-incluir-hoy]`, `[data-abrir-revision]` y `[data-cerrar-revision]`.
  2. Vistas Blade actualizadas sin alterar ningún estilo, clase ni layout:
     - `resources/views/alumnos/show.blade.php`: 3 reemplazos (`data-confirmar`, `data-abrir-condonar`, `data-cerrar-condonar`).
     - `resources/views/caja/detalle.blade.php`: 4 reemplazos (`data-abrir-rechazar`, `data-abrir-cancelar`, `data-cerrar-cancelar`, `data-cerrar-rechazar`).
     - `resources/views/caja/resumen.blade.php`: 2 reemplazos (`data-abrir-rechazar`, `data-cerrar-rechazar`).
     - `resources/views/liquidaciones/create.blade.php`: 2 reemplazos (`data-incluir-hoy="true"`, `data-incluir-hoy="false"`).
     - `resources/views/revision-cobranza/index.blade.php`: 3 reemplazos (`data-cerrar-revision`, `data-abrir-revision` con `data-tipo="CONTINUA"` e `INACTIVO`).
  3. Preservados intactos los 10 `onsubmit="return confirm(...)"` de eliminación y los 26 bloques `<script>`.
  4. Compilados los assets de producción con Vite (`npm run build`).
  5. `tests/Feature/CspSinCodigoIncrustadoTest.php`: Reducida la constante `MANEJADORES_PERMITIDOS` de 24 a 10 con documentación explicativa.
- **Verificaciones:**
  - `php -l`: sintaxis limpia en las 5 vistas, el test y los archivos tocados.
  - `php artisan view:cache; php artisan view:clear`: compilación Blade sin errores.
  - `php artisan test --filter CspSinCodigoIncrustadoTest`: 2 passed (2 assertions).
  - `git diff --stat -- resources/css`: vacío (diseño intacto).
  - `php artisan test`: 214 tests passed (1315 assertions).
- **Siguiente paso:** Tareas de administración y alertas (ENT-06) o siguiente etapa de CSP.

## 2026-09-13 — LOG GEM CAB — ENT-05 Acceso directo al recibo tras cobrar y en la ficha

- **Objetivo:** Dar acceso directo al recibo de cuota a un clic en dos momentos clave: inmediatamente después de cobrar (para entregar al padre en mostrador) y en la ficha del alumno (para reimpresión o consulta posterior).
- **Cambios reales:**
  1. `app/Http/Controllers/CajaWebController.php`: en `pagar()`, agregada clave `recibo_pago_id` en el flash de sesión del redirect; en `index()`, agregado `session()->reflash()` si la petición es AJAX para conservar el flash ante follows de fetch.
  2. `resources/views/layouts/ds-app.blade.php`: en el banner `.ds-flash--success`, incorporado botón `Recibo` (`class="ds-btn-row ds-btn-row--sec"`, `target="_blank"`, `inline=1`) cuando existe `recibo_pago_id` o `recibo_url`.
  3. `resources/views/alumnos/show.blade.php`: en cada fila del widget "Historial de pagos", agregado botón `Recibo` (`class="ds-btn-row ds-btn-row--sec"`, `target="_blank"`, `inline=1`), distinguiendo además pagos anulados (`· Anulado`).
  4. Ambos botones protegidos con `!auth()->user()?->isProfesor()`.
  5. `resources/js/ds-app.js`: condicionado el auto-dismiss de 3s en `.ds-flash` para que banners con acciones (`<a>` o `<button>`) no desaparezcan automáticamente. Compilado con Vite (`npm run build`).
  6. Agregada prueba de regresión `tests/Feature/CobroReciboAccesoTest.php` (3 pruebas, 18 aserciones).
  7. Sincronizados ambos tableros (`PLAN-TRABAJO-IA-v2026-09-08.md` y `PLAN-TRABAJO-CARLOS-v2026-09-08.html`), `RESUMEN-ARRANQUE.md` y `ESTADO-ACTUAL.md`.
- **Verificaciones:**
  - `php -l`: sintaxis limpia en controladores, vistas y tests.
  - `php artisan view:cache && php artisan view:clear`: compilación Blade OK.
  - `php artisan test --filter CobroReciboAccesoTest`: 3 passed (18 assertions).
  - `php artisan test --filter TablerosNoDivergenTest`: 2 passed (9 assertions).
  - `php artisan test --filter CspSinCodigoIncrustadoTest`: 2 passed (2 assertions).
  - `git diff --stat -- resources/views resources/css`: solo las dos vistas autorizadas tocadas.
- **Siguiente paso:** Tareas de administración y alertas (ENT-06) o reportes.

## 2026-09-12 — LOG GEM CAB — ENT-02 Nuevo recibo de cuota y versión anulada

- **Objetivo:** Implementar el diseño visual aprobado por Carlos y Vanina (`docs/03-diseno-ui/INSTRUCTIVO-IMPLEMENTACION-RECIBOS.md`) para el comprobante de cuota y su versión anulada, bajo el estándar estricto DomPDF CSS 2.1 (A5 vertical).
- **Cambios reales:**
  1. `resources/views/pdfs/recibo-cuota.blade.php`: Rediseñado por completo en layout de tablas porcentuales (`width: 58%` / `42%`, etc.) y `border-collapse: collapse;`, sin flexbox ni grid.
  2. Formato de página A5 vertical (`148mm × 210mm`) con márgenes `10mm 12mm 10mm 12mm` y tipografías DejaVu Sans / DejaVu Sans Mono.
  3. Logo institucional dentro de contenedor `.logo-frame` con fondo `#0F172A` para asegurar contraste y legibilidad.
  4. Importes y totales formateados en `#0F172A` (prohibido rojo en montos). Rojo reservado exclusivamente a la cabecera `.recibo-sello-anulado` y borde de auditoría de cancelación.
  5. Grilla de auditoría en recibo anulado con fecha de emisión, fecha de cobro, fecha de cancelación, cancelado por, motivo de anulación y observaciones.
  6. En `app/Services/ReciboService.php`: incorporados al array `$data` los campos de trazabilidad (`fecha_cancelacion`, `cancelado_por`, `motivo_cancelacion`) preservando `obtenerPeriodosImputados()` y `obtenerTipoCajaPago()`.
  7. Sincronizados ambos tableros (`PLAN-TRABAJO-IA-v2026-09-08.md` y `PLAN-TRABAJO-CARLOS-v2026-09-08.html` con casilla tildada).
- **Verificaciones:**
  - `php -l resources/views/pdfs/recibo-cuota.blade.php`: sintaxis limpia.
  - `php -l app/Services/ReciboService.php`: sintaxis limpia.
  - `php artisan view:cache; php artisan view:clear`: compilación Blade OK.
  - `php artisan test --filter ReciboMedioDePagoTest`: 6 passed (66 assertions), incluyendo generación y regeneración real de PDF anulado con DomPDF.
  - `php artisan test --filter CspSinCodigoIncrustadoTest`: 2 passed (2 assertions).
  - `php artisan test --filter TablerosNoDivergenTest`: 2 passed (9 assertions).
- **Siguiente paso:** Tareas de cobros y de integridad pendientes en el plan.

## 2026-09-12 — LOG GEM CAB — SEG-05 Sanitización de errores en ReciboController

- **Objetivo:** Ocultar excepciones crudas de recibos al usuario final en `ReciboController`, evitando filtrar rutas internas del servidor, trazas o errores de base de datos y registrando el detalle en el log de Laravel.
- **Cambios reales:**
  1. En `app/Http/Controllers/ReciboController.php`: Atrapadas `\Throwable $e` y validada la existencia del archivo generado tanto en `cuota()` como en `liquidacion()`.
  2. Registrado el error técnico con `Log::error()` conteniendo ID del registro, excepción y contexto.
  3. Respuesta JSON amigable en castellano: `"No se pudo generar el comprobante. Por favor, intentá nuevamente o comunicate con administración."` con HTTP 500.
  4. Agregada prueba de regresión `tests/Feature/ReciboErrorSanitizadoTest.php` comprobando que no se filtre información interna y que se registre en el log.
  5. Sincronizados ambos tableros (`PLAN-TRABAJO-IA-v2026-09-08.md` y `PLAN-TRABAJO-CARLOS-v2026-09-08.html` con casilla tildada).
- **Verificaciones:**
  - `php -l app/Http/Controllers/ReciboController.php`: sintaxis limpia.
  - `php artisan test --filter ReciboErrorSanitizadoTest`: 2 passed (14 assertions).
  - `php artisan test --filter TablerosNoDivergenTest`: 2 passed (9 assertions).
- **Siguiente paso:** Proceder con ENT-02 (nuevo diseño de recibo de cuota y versión anulada).

## 2026-09-12 — LOG GEM CAB — ENT-04 Ojo para ver contraseña en usuarios

- **Objetivo:** Implementar botón para ver/ocultar contraseña en alta y edición de usuarios (`resources/views/usuarios/_form.blade.php`), pedido por Carlos el 07/09.
- **Cambios reales:**
  1. Dos botones de ojo independientes (`.btn-toggle-password` con `data-target="password"` y `data-target="password_confirmation"`), manteniendo simetría de columnas en el grid y localidad de control.
  2. Íconos `.icon-eye` y `.icon-eye-off` (SVG) idénticos al modelo de `login.blade.php`.
  3. `padding-right: 2.75rem` en ambos inputs para evitar que el texto pase por debajo del botón.
  4. Compatible con alta y edición: en edición no afecta el placeholder ("Dejar en blanco para no cambiar") ni la validación.
  5. JavaScript desacoplado en `resources/js/ds-app.js` (sin incrustar código en Blade), manteniendo `CspSinCodigoIncrustadoTest` exactamente en 26 bloques `<script>` y 24 manejadores inline. Compilado con Vite (`npm run build`).
- **Verificaciones:**
  - `php -l resources/views/usuarios/_form.blade.php`: sintaxis limpia.
  - `php artisan view:cache; php artisan view:clear`: compilación Blade OK.
  - `php artisan test --filter CspSinCodigoIncrustadoTest`: 2 passed (2 assertions).
  - `php artisan test`: 166 passed (1026 assertions) 100% verde.
- **Siguiente paso:** Continuar con tareas financieras o de entregas según prioridades.

## 2026-09-12 — LOG GEM CAB — ENT-03 Favicon definitivo de Wings

- **Objetivo:** Resolver ENT-03 (Favicon) con identidad propia de escuela de patín artístico, abandonando la paleta negro/rojo/blanco por pedido de Carlos.
- **Cambios reales:**
  1. Generación de activo gráfico: bota blanca de patín artístico con taco, alas fucsia y cian, ruedas oscuras y fondo claro hielo/lavanda con máximo contraste.
  2. Encuadre maximizado al 95% de superficie útil (aprobado por Carlos) para legibilidad nítida en 16px y 32px.
  3. Producción de assets en `public/`: `favicon.ico` (multi-res 16/32/48), `favicon-32x32.png`, `favicon-16x16.png`, `apple-touch-icon.png` (180x180), `android-chrome-192x192.png`, `android-chrome-512x512.png` y `site.webmanifest`.
  4. Enlace canónico en `resources/views/layouts/ds-app.blade.php` bajo autorización explícita de diseño de Carlos.
- **Verificaciones:**
  - `php -l resources/views/layouts/ds-app.blade.php`: sintaxis limpia.
  - `php artisan view:cache; php artisan view:clear`: compilación Blade OK.
  - `git diff --stat -- resources/views resources/css`: solo 6 líneas autorizadas en `ds-app.blade.php`.
  - `php artisan test`: 166 tests pasados (1026 assertions) verde al 100%.
- **Siguiente paso:** Continuar con las tareas de cobros / financiera del plan vigente.

## 2026-09-11 — extracto documental de LOG GEM CYE — ENT-02

Diseño de recibos de cuota, anulado y liquidación por hora/comisión preparado.
Aprobación visual registrada; no equivale a decisión de cálculo por hora/clase.
Entregables: ../03-diseno-ui/PREVIEW-RECIBOS.html y muestras PDF de esa carpeta.
Instructivo: ../03-diseno-ui/INSTRUCTIVO-IMPLEMENTACION-RECIBOS.md.
No copiar a producción sin resolver bloqueos y autorización concreta de implementación.
Verificar disponibilidad/versionado al cambiar de computadora.
