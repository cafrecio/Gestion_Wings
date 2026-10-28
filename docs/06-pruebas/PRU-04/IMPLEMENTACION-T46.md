# T46 — Desactivar profesora sin dejar clases futuras ni acceso

28/10/2026 · Codex CyE · implementación local, pendiente de Claude y aspecto de Carlos. Sin despliegue.

DU-44 / PRO-004. La tarjeta informa cuántas clases activas futuras o en curso hay y enlaza a Clases para reasignar. El servidor impide la baja mientras quede alguna. Cuenta fecha posterior a hoy o, si es hoy, fin posterior a la hora actual; canceladas y terminadas no bloquean. Se usa `ClaseWebController::actualizarProfesores`, con su control de choque vigente: una cancelada no choca. No se altera ni cancela automáticamente ninguna clase.

`ProfesorEstadoService` bloquea la profesora y comprueba las clases con lectura actual. Alta/reasignación vuelven a comprobar y bloquear profesoras activas antes de guardar; reactivar una clase futura con profesora inactiva se rechaza. No se ofrece una inactiva en los selectores de alta/reasignación y las rutas forzadas también rechazan. Se conservan la protección de cuentas y el impedimento de desactivar una cuenta con caja abierta; la transacción revierte todo si no puede completar la baja.

Una inactiva aparece en Nueva liquidación solo si conserva clases terminadas liquidables, sin liquidación vigente de ese período. No se ofrecen las que no tienen nada pendiente. Las clases con presencia o validación manual permiten liquidar HORA y COMISION con la regla del período; no se repone una tarifa ni un porcentaje. Una liquidación abierta existente se revisa desde su ficha y no se vuelve a generar.

Acceso: se desactivan las cuentas vinculadas, se cambia su remember_token y se borran sus sesiones de la tabla configurada. Nuevo ingreso y cada petición protegida comprueban cuenta/profesora activas. Incluso activar solo el usuario no habilita acceso mientras la profesora esté inactiva. **Decisión de reactivación:** activar a la profesora conserva el estado previo del usuario; ADMIN lo habilita por separado en Usuarios. Esto evita restaurar un acceso sin revisión. No se permite afectar una cuenta protegida ni una cuenta con caja abierta.

La tarjeta actualiza interruptor, rótulo, punto y aviso en el lugar; rechazos revierten el interruptor y muestran el motivo en la misma tarjeta. Reenvío explícito del estado no lo invierte. JavaScript exclusivo de la pantalla; `ds-app.js` queda intacto. No se cambia CSS.

Pruebas y evidencia:

- 15 nuevas / 113 aserciones verdes: cantidad futura/en curso, reasignación operativa, choque real/cancelado, liquidación inactiva HORA y COMISION, desaparición sin pendientes, sesión abierta/nuevo ingreso, eliminación de sesión/recuerdo, reactivación separada, rutas forzadas por rol, alta/reasignación inactiva, clase cancelada futura y atomicidad ante cuenta protegida.
- Área: 50 aprobadas / 343 aserciones / 23,29 s, incluyendo T66, T62, UsuarioActivoWeb y Nueva liquidación T87. Después, propias finales con Vite y exportación HTML: 15/113, 11,23 s.
- PHP de doce archivos correcto, Blade compila y limpia; build Vite correcto. Rápida: 1128 aprobadas, 6 omitidas, 8635 aserciones, 373,71 s, salida 0. Completa: 1264 aprobadas, 6 omitidas, 10426 aserciones, 1636,60 s, salida 0 en wings_testing_codex.
- 8 capturas reales Chrome 1366/marco375: Profesores antes/después, Nueva liquidación y login de calibración. HTML de respuestas reales HTTP de Laravel en `wings_testing_codex`, con datos sintéticos. [Galería](APROBAR-T46.html). Sin desborde de documento ni errores JavaScript según `evidencia-t46/medidas-chrome.json`.
- Cliente probado con respuestas simuladas **solo después de las capturas**: rechazo revierte, éxito actualiza rótulo/aria, doble clic produce un pedido por decisión, sin navegación. Los permisos y mutaciones reales se prueban por HTTP/PHPUnit. `evidencia-t46/interaccion-cliente.json`.

Sin migraciones nuevas de T46, sin cambios de datos del club ni reactivación de la API. No se reconstruyen ni reasignan clases antiguas por conjetura. El índice mostró cobertura incompleta y no se usó para certificar hechos; se confirmaron las fuentes reales.

Propuesta para Claude, sin editar catálogo ni DU-44: PRO-004 (`catalogo/PRO.json:159`), estado actual `no_implementado`: pasar a `vigente` después de verificar; fuente de la línea160 actual «Decidido por Carlos (DU-44, 13/10). Falta programar: T46. Antes: ProfesorWebController::toggleActivo no controla nada; LiquidacionService exige profesor activo (código leído); SI-08.»: reemplazar por «DU-44 implementada por ProfesorEstadoService y selección de pendientes inactivos en LiquidacionWebController; ver IMPLEMENTACION-T46.md». En c), línea149, actual «Su usuario queda desactivado: no puede volver a entrar.»; propuesto «Su usuario queda desactivado y su sesión se corta. Reactivar a la profesora requiere habilitar el usuario por separado». Conservar a), b) y d).

Corte: 17 fuentes propias intactas; 631 de 632 fuentes globales sin cambios. Solo tests/Feature/AyudaManualT12T13Test.php cambió durante la corrida, por el trabajo paralelo de T12/T13, y queda fuera del commit y de la certificación de T46. No se modificó ni se incorporó ese trabajo. Al iniciar no había otra suite; más tarde se observó una rápida ajena, conservándose una sola completa en la computadora.

[Resultados](evidencia-t46/pruebas.txt) · [Fuentes propias](evidencia-t46/fuentes-sha256.json) · [Corte global](evidencia-t46/corte-global-sha256.json). T46 a_verificar, tiene/verifica=Claude. Se completan las tres entregas autorizadas T51, T45 y T46; quedan su verificación independiente y la revisión visual de Carlos. Sin despliegue.
