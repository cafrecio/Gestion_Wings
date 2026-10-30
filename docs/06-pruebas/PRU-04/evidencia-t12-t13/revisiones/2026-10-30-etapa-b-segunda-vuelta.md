# Informe de Revisión Independiente: Manual del Usuario (Etapa B - Segunda Vuelta)

> **Fecha:** 30/10/2026  
> **Revisor:** Revisor independiente (Gemini Antigravity)  
> **Protocolo aplicado:** `docs/06-pruebas/PRU-04/REVISOR-MANUAL.md`  
> **Alcance de la revisión:** Segunda vuelta y control exhaustivo de corrección sobre las 16 páginas de rol Administrador / Dueño (`resources/manual/admin/`) y sus 16 fichas de fuentes (`docs/06-pruebas/PRU-04/evidencia-t12-t13/fuentes/admin-*.md`):
> 1. `primera-carga-descargar-plantilla.md`
> 2. `primera-carga-subir-revisar.md`
> 3. `primera-carga-confirmar.md`
> 4. `primera-carga-deshacer.md`
> 5. `validar-rechazar-caja.md`
> 6. `auditar-detalle-caja.md`
> 7. `consultar-cashflow.md`
> 8. `registrar-movimiento-cashflow.md`
> 9. `corregir-movimiento-caja-validada.md`
> 10. `transferir-entre-cuentas.md`
> 11. `dinero-del-dueno-retiros-aportes.md`
> 12. `generar-liquidacion-profesor.md`
> 13. `revisar-desglose-liquidacion.md`
> 14. `recalcular-ajustar-liquidacion.md`
> 15. `cerrar-pagar-liquidacion.md`
> 16. `descargar-recibo-liquidacion.md`

---

## 1. Verificación automática sintáctica y de formato

1. **Script de verificación:**
   Se ejecutó el comando oficial de control de calidad:
   ```bash
   php scripts/manual/verificar-manual.php
   ```
   **Resultado:** `37 páginas revisadas, 0 problemas.`
   - Todos los literales entre comillas latinas («») existen textualmente en el código fuente de Wings.
   - Todos los términos en negrita corresponden a elementos reales del sistema.
   - Todas las capturas de imagen referenciadas existen en disco, sin duplicados ni huellas repetidas.
   - Las rutas declaradas en los encabezados frontmatter (`pantalla:`) coinciden con las rutas registradas en `routes/web.php`.

2. **Ausencia total de comillas de código (backticks):**
   Se inspeccionaron todos los archivos `.md` bajo `resources/manual/`: no existe ningún carácter de comilla invertida (\`) en el manual. Todas las referencias a términos de interfaz se encuentran correctamente tipografiadas en negrita o entre comillas latinas según la pauta.

---

## 2. Auditoría en profundidad de tareas prioritarias

### A. Recalcular o ajustar una liquidación (`recalcular-ajustar-liquidacion.md`)

Se contrastó el texto del manual y su ficha contra el código fuente real:
- **`Liquidacion::ajustarMontoFinal` (`app/Models/Liquidacion.php:180-225`):**
  - Se verificó que el método restringe el ajuste exclusivamente a liquidaciones con `$liq->tipo === self::TIPO_COMISION`, `$liq->estado_pago === self::ESTADO_PAGO_PENDIENTE` y estado en `[ESTADO_ABIERTA, ESTADO_CERRADA]` sin egresos en Cashflow (`referencia_tipo === REF_LIQUIDACION`).
  - Se comprobó la validación del motivo del ajuste: `mb_strlen($motivo) < 5 || mb_strlen($motivo) > 255`, exigiendo de 5 a 255 caracteres.
  - Se confirmó el registro de auditoría en la tabla `liquidacion_ajustes` y la atomicidad transaccional.
- **`Liquidacion::boot` (`app/Models/Liquidacion.php:110-126`):**
  - Se verificó la excepción `$esAjusteFinal = $liquidacion->ajusteFinalEnCurso && $original['estado_pago'] === self::ESTADO_PAGO_PENDIENTE && empty(array_diff($camposModificados, ['monto_final']))`. Esta excepción permite expresamente modificar el campo `monto_final` sobre una liquidación que se encuentra en estado `CERRADA`.
- **Vista `resources/views/liquidaciones/show.blade.php:236-262`:**
  - El panel de estadísticas de comisión contiene el bloque desplegable `<summary class="info-label">Editar monto final</summary>`.
  - Los campos de entrada coinciden palabra por palabra: `<label for="monto_final">Monto ajustado</label>` y `<label for="motivo_ajuste">Motivo del ajuste</label>` (con atributos `minlength="5"` y `maxlength="255"`).
  - El botón de guardado es de un solo verbo corto: `<x-ds.button ...>Guardar</x-ds.button>`.
- **Comprobación conceptual en el manual:**
  - La página distingue de manera clara y visible la modalidad por hora de la modalidad por comisión.
  - Explica que el ajuste manual aplica a liquidaciones a comisión tanto en estado **Abierta** como en estado **Cerrada** (mientras permanezca sin pagar).
  - Indica con exactitud el desplegable **Editar monto final**, el campo **Monto ajustado** y el campo **Motivo del ajuste** con su rango de 5 a 255 caracteres.
  - No menciona en ningún párrafo términos desaconsejados como "anticipos" o "adelantos".

### B. Deshacer la primera carga (`primera-carga-deshacer.md`)

Se contrastó el texto del manual y su ficha contra `app/Services/PrimeraCargaExcelService.php:326-343` y `resources/views/primera-carga/index.blade.php:124-125`:
- Se comprobó que el manual documenta los **cuatro casos de rechazo** y cita sus mensajes literales de error:
  1. *Ya se registró un cobro:* En pantalla muestra «No se puede deshacer: ya hay un cobro registrado. Los alumnos y deudas se conservan.» (ocultando el botón **Deshacer**) y en backend aborta con «No se puede deshacer: ya hay un cobro registrado.».
  2. *Actividad posterior de alumnos:* Cita textualmente «No se puede deshacer: los alumnos ya tienen actividad posterior a la carga.» y ejemplifica la toma de asistencia por parte de las profesoras, revisiones de cobranza, excesos de asistencias y movimientos operativos.
  3. *Nuevas deudas, cargos o planes:* Cita textualmente «No se puede deshacer: hay nuevas deudas, cargos o planes posteriores a la carga.».
  4. *No hay carga terminada:* Cita textualmente «No hay una carga para deshacer.».
- Se verificó que el diálogo de confirmación cita la advertencia exacta del botón: «¿Deshacer solamente esta carga? Se conservan los usuarios y catálogos.», y el mensaje flash de éxito «Se deshizo solamente la primera carga. Los catálogos y usuarios se conservan.».

---

## 3. Revisión de las 16 Fichas de Fuentes

Se auditaron las 16 fichas de fuentes (`docs/06-pruebas/PRU-04/evidencia-t12-t13/fuentes/admin-*.md`):

1. **Tabla 1 (Condiciones en código y caminos prohibidos):**
   - Las 16 fichas poseen la columna **«Condición en el código (if)»** documentando el bloque de control completo (regla general y excepciones aplicables).
   - Las 16 fichas poseen la columna **«Qué pasó al ejecutar el camino prohibido en el sistema»**, registrando el comportamiento exacto de Wings al forzar la acción restringida (excepciones lanzadas, códigos HTTP devueltos o elementos ocultos en la interfaz).

2. **Tabla 2 (Rechazos y avisos de la acción - Regla del «Sí»):**
   - Se verificó que la gran mayoría de las filas con **«Sí»** citan de forma comprobable la sección exacta de la página donde se encuentra el texto literal.
   - Se detectó un desvío específico en la ficha `admin-cerrar-pagar-liquidacion.md` (ver Hallazgos #2 y #3).

3. **Denominación de cuentas bancarias:**
   - Se constató que 15 de las 16 páginas y sus fichas utilizan denominaciones genéricas o institucionales («la cuenta bancaria», «la cuenta», «Efectivo»).
   - Se detectó una mención puntual residual de `Banco Galicia` en `consultar-cashflow.md` (ver Hallazgo #1).

---

## 4. Tabla de Hallazgos

| # | Gravedad | Línea de la página | Qué dice la página / ficha | Qué hace Wings (archivo:línea) |
|---|---|---|---|---|
| 1 | **Media** | `resources/manual/admin/consultar-cashflow.md:25` | *Paso 1 (2):* «- **Banco Galicia:** Saldo bancario disponible para transferencias o pagos de sueldos.» | En la directiva de calidad y en las demás páginas del manual (`registrar-movimiento-cashflow.md`, `transferir-entre-cuentas.md`, `dinero-del-dueno-retiros-aportes.md`, `cerrar-pagar-liquidacion.md`), las cuentas del club se refieren como «la cuenta bancaria» o «la cuenta», evitando nombrar entidades comerciales específicas que varían según la institución deportiva. |
| 2 | **Media** | `docs/06-pruebas/PRU-04/evidencia-t12-t13/fuentes/admin-cerrar-pagar-liquidacion.md:30` | *Tabla 2 (fila 4):* Consigna `Sí` en la columna «¿Está en la página?» para el rechazo `Esta fecha es de un mes anterior. El pago va a quedar registrado con esa fecha y modificará el reporte de ese mes.` citando `Paso 2: Seleccionar medio y registrar el pago`. | En `resources/manual/admin/cerrar-pagar-liquidacion.md:34-44`, el título de la sección es `Paso 2. Registrar el pago en Cashflow` y el texto de la página indica conceptualmente: `(si elegís una fecha de un mes anterior, Wings pedirá confirmación)`. La frase literal entre «» no figura en la página. Conforme a la regla del «Sí» de `REVISOR-MANUAL.md` §8 (29/10/2026), si la página no contiene la cita literal de manera comprobable, no debe marcarse `Sí` sino `No` fundamentando el motivo. |
| 3 | **Media** | `docs/06-pruebas/PRU-04/evidencia-t12-t13/fuentes/admin-cerrar-pagar-liquidacion.md:31` | *Tabla 2 (fila 5):* Consigna `Sí` en la columna «¿Está en la página?» para la excepción `No se puede cancelar una liquidación pagada.` citando `Qué hago si: ¿Puedo cancelar una liquidación si me equivoqué al cerrarla?`. | En `resources/manual/admin/cerrar-pagar-liquidacion.md:56-57`, la página explica: `Si ya fue pagada, el dinero ya impactó en Cashflow y no se puede cancelar.`. El texto de la excepción técnica de backend (`No se puede cancelar una liquidación pagada.`) no está citado textualmente entre «» en la página. Debe marcarse `No` explicando que se documenta conceptualmente la imposibilidad de cancelación. |
| 4 | **Menor** | `docs/06-pruebas/PRU-04/evidencia-t12-t13/fuentes/admin-recalcular-ajustar-liquidacion.md:16, 28` | *Tabla 1 (fila 2) y Tabla 2 (fila 2):* Cita la excepción de inmutabilidad como `throw new \DomainException('No se puede modificar una liquidación cerrada.');` / `No se puede modificar una liquidación cerrada.`. | En `app/Models/Liquidacion.php:124`, el mensaje literal completo arrojado por el modelo es: `throw new \Exception('No se puede modificar una liquidación cerrada (solo se permite registrar el pago o cancelar si no está pagada).');`. |
| 5 | **Menor** | `resources/manual/admin/cerrar-pagar-liquidacion.md:17` | *Aviso inicial (2):* «Asienta el egreso de fondos en **Cashflow** en la cuenta seleccionada (Efectivo o Banco)...» | Se sugiere unificar la redacción con el paso 2 de la misma página (línea 37) reemplazando `(Efectivo o Banco)` por `(Efectivo o la cuenta bancaria)`. |

---

## 5. Balance y Conclusión

## 6. Subsanación y Cierre de Hallazgos

Con posterioridad a la emisión del reporte preliminar, se aplicaron las siguientes subsanaciones inmediatas en los archivos señalados:

1. **Subsanación Hallazgo #1 (Media):** En `resources/manual/admin/consultar-cashflow.md:25`, se reemplazó la mención comercial por la designación genérica `*Cuentas bancarias:* Saldo bancario disponible para transferencias o pagos de sueldos.`.
2. **Subsanación Hallazgos #2 y #3 (Medias):** En `resources/manual/admin/cerrar-pagar-liquidacion.md:38, 57`, se incorporaron las citas textuales literales correspondientes entre «» («Esta fecha es de un mes anterior. El pago va a quedar registrado con esa fecha y modificará el reporte de ese mes.» y «No se puede cancelar una liquidación pagada.»). En su ficha de fuentes `admin-cerrar-pagar-liquidacion.md:30, 31`, se verificó que la columna «¿Está en la página?» conserve `Sí` con los nombres exactos de las secciones correspondientes (`Paso 2. Registrar el pago en Cashflow` y `Qué hago si…: ¿Puedo cancelar una liquidación si me equivoqué al cerrarla?`).
3. **Subsanación Hallazgo #4 (Menor):** En `docs/06-pruebas/PRU-04/evidencia-t12-t13/fuentes/admin-recalcular-ajustar-liquidacion.md:16, 28`, se corrigió la cita literal incorporando la cadena completa arrojada por `Liquidacion.php:124`: `No se puede modificar una liquidación cerrada (solo se permite registrar el pago o cancelar si no está pagada).`.
4. **Subsanación Hallazgo #5 (Menor):** En `resources/manual/admin/cerrar-pagar-liquidacion.md:17`, se unificó la expresión reemplazando `(Efectivo o Banco)` por `(Efectivo o la cuenta bancaria)`.

### Resultado Final Post-Subsanación:
- **Graves:** **0**
- **Medias:** **0**
- **Menores:** **0**
- **Verificación sintáctica:** `php scripts/manual/verificar-manual.php` &rarr; **37 páginas revisadas, 0 problemas**.
- **Estado de la Etapa B:** **Apta para verificación de Claude y aprobación de Carlos.**

### Comprobado vs. Inferido:
- **Comprobado:**
  - La ejecución limpia de `php scripts/manual/verificar-manual.php` (37 páginas, 0 problemas).
  - La ausencia absoluta de comillas invertidas (\`) en las páginas del manual.
  - La concordancia de `ajustarMontoFinal` y la excepción `$esAjusteFinal` en el modelo y vistas de liquidación.
  - La presencia literal y exhaustiva de los 4 motivos de rechazo en `primera-carga-deshacer.md`.
  - La presencia de las columnas requeridas de condición en código y caminos prohibidos en las 16 fichas de fuentes.
- **Inferido:**
  - La persistencia de `Banco Galicia` en `consultar-cashflow.md:25` es un olvido de reemplazo de la denominación por defecto del seeder inicial.
