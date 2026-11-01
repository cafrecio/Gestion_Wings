# Implementación T113 — Desvalidar una clase (Solo Dueño)

> Implementado por Gemini CAB el 01/11/2026 según `docs/06-pruebas/PRU-04/PROMPT-GEMINI-T113.md`.  
> Control independiente por Claude.

---

## 1. Resumen ejecutivo

- **Objetivo:** Permitir al dueño desvalidar una clase que fue validada a mano («esta clase se dio y se paga, aunque no tenga presentes»), permitiendo corregir validaciones accidentales (como la clase de Hockey del 19/12 en la tanda 5).
- **Alcance de la acción:**
  1. **Solo el dueño (`ADMIN`):** Ni la operativa ni los profesores ven el botón ni pueden invocar la ruta (403).
  2. **Intención explícita contra alternancia a ciegas:** El formulario envía `accion="desvalidar"` o `accion="validar"`. Ante doble clic o pestañas viejas, si la clase ya se encuentra en el estado solicitado, no se invierte el dato; se rechaza con: *«La clase cambió mientras la mirabas. Recargá la pantalla.»*.
  3. **Protección financiera ante liquidaciones abiertas por hora:** No se ofrece `Desvalidar` si la clase integra una liquidación por hora en estado `ABIERTA`. Un envío forzado es rechazado con: *«No se puede desvalidar: la clase está en una liquidación abierta. Eliminá esa liquidación, desvalidá y generala de nuevo.»*, evitando pagar una clase desvalidada al cerrar la liquidación sin recalcular.
  4. **Protección financiera ante liquidaciones cerradas o pagadas:** Se mantiene el rechazo existente: *«No se puede modificar la validación: la clase integra una liquidación cerrada.»*.
  5. **Preservación estricta de Editar:** El enlace **Editar** conserva exactamente su condición original (`$esAdmin && !$clase->validada_para_liquidacion && $cantPresentes === 0`).
  6. **Restauración de estados de negocio:** Una clase desvalidada sin asistencias vuelve a contar en «Sin asistencia» (filtro `finalizada`) y recupera la condición `esEliminable() === true` para ofrecer **Eliminar**.

---

## 2. Archivos modificados y citas de código reales

### A. Modelo `app/Models/Clase.php`

- **Líneas 180-186 (`integraLiquidacionHoraAbierta`):**
  ```php
  public function integraLiquidacionHoraAbierta(): bool
  {
      return LiquidacionDetalle::where('tipo_referencia', LiquidacionDetalle::TIPO_CLASE)
          ->where('referencia_id', $this->id)
          ->whereHas('liquidacion', fn ($q) => $q->where('estado', Liquidacion::ESTADO_ABIERTA))
          ->exists();
  }
  ```
- **Líneas 192-197 (`puedeDesvalidar`):**
  ```php
  public function puedeDesvalidar(): bool
  {
      return (bool) $this->validada_para_liquidacion
          && !$this->integraLiquidacionCerrada()
          && !$this->integraLiquidacionHoraAbierta();
  }
  ```

### B. Controlador `app/Http/Controllers/ClaseWebController.php`

- **Líneas 785-844 (`toggleValidada`):**
  - Firma actualizada a `toggleValidada(Request $request, int $id)`.
  - Verificación de rol administrativo en controlador con `abort(403)` defensivo.
  - Bloqueo pesimista con `DB::transaction` y `lockForUpdate()`.
  - Validación de intención explícita: si `accion === 'validar'` y ya estaba validada, o si `accion === 'desvalidar'` y ya estaba desmarcada, rechaza con *«La clase cambió mientras la mirabas. Recargá la pantalla.»*.
  - Validación de liquidación cerrada/pagada con *«No se puede modificar la validación: la clase integra una liquidación cerrada.»*.
  - Validación de liquidación por hora abierta con *«No se puede desvalidar: la clase está en una liquidación abierta. Eliminá esa liquidación, desvalidá y generala de nuevo.»*.
  - Actualización atómica del valor booleano (`$nuevoEstado = $accion ? ($accion === 'validar') : !$clase->validada_para_liquidacion`) en lugar de togglear ciegamente.

### C. Vista Blade `resources/views/clases/show.blade.php`

- **Líneas 133-149 (Botón Validar):**
  - Condición intacta: `@if($esAdmin && !$clase->validada_para_liquidacion && $cantPresentes === 0)`.
  - Envía `<input type="hidden" name="accion" value="validar">`.
  - Botón: color `var(--color-btn-primary)` (96×32px), texto `Validar`.
- **Líneas 150-164 (Botón Desvalidar):**
  - Condición: `@if($esAdmin && $clase->puedeDesvalidar())`.
  - Envía `<input type="hidden" name="accion" value="desvalidar">`.
  - Botón: color `var(--color-btn-secondary)` (96×32px), texto `Desvalidar`.
- **Líneas 166-176 (Enlace Editar):**
  - Separado en su propio bloque `@if($esAdmin && !$clase->validada_para_liquidacion && $cantPresentes === 0)`, conservando intactas las reglas de cuándo se puede editar la clase.
- **Sin JavaScript inline:** Cumple con la directiva CSP y `AGENTS.md` §5-bis.

---

## 3. Matriz de verificación de casos (Casos 1 al 10)

Todos los casos requeridos fueron implementados y probados en `tests/Feature/DesvalidarClaseT113Test.php`:

| # | Caso | Comprobación en Test | Resultado |
|---|---|---|---|
| **1** | Dueño, clase validada sin liquidación | `test_caso_1_duenio_desvalida_clase_sin_liquidacion` | **PASS** — Muestra Desvalidar y badge. Al desvalidar: flash «Clase desmarcada de validación.», badge desaparece y ofrece Validar. |
| **2** | Clase futura validada por error | `test_caso_2_clase_futura_validada_por_error_se_desvalida_y_no_cuenta_en_liquidacion` | **PASS** — Se desvalida y deja de contar en previsualización y generación de liquidación posterior. |
| **3** | Clase desvalidada sin asistencias | `test_caso_3_clase_desvalidada_sin_asistencia_cuenta_en_sin_asistencia_y_es_eliminable` | **PASS** — Vuelve a contar en «Sin asistencia» (filtro `finalizada`), `esEliminable()` es `true` y ofrece botón `Eliminar`. |
| **4** | Clase validada con presentes posteriores | `test_caso_4_clase_validada_con_presentes_se_desvalida_y_sigue_pagandose` | **PASS** — Se puede desvalidar y se sigue pagando por los presentes en la liquidación (mismo monto). |
| **5** | Clase en liquidación cerrada o pagada | `test_caso_5_clase_en_liquidacion_cerrada_o_pagada_no_ofrece_desvalidar_y_rechaza` | **PASS** — No ofrece Desvalidar en vista. Forzado rechaza con «No se puede modificar la validación: la clase integra una liquidación cerrada.». |
| **6** | Clase en liquidación por hora abierta | `test_caso_6_clase_en_liquidacion_hora_abierta_no_ofrece_desvalidar_y_rechaza` | **PASS** — No ofrece Desvalidar en vista. Forzado rechaza con «No se puede desvalidar: la clase está en una liquidación abierta. Eliminá esa liquidación, desvalidá y generala de nuevo.». |
| **7** | Clase en liquidación cancelada | `test_caso_7_clase_en_liquidacion_cancelada_se_puede_desvalidar` | **PASS** — Ofrece Desvalidar y permite desmarcarla sin trabas. |
| **8** | Operativa y profesora | `test_caso_8_operativa_y_profesora_no_ven_desvalidar_y_reciben_403` | **PASS** — No ven el botón en HTML. Petición forzada responde 403. Sin sesión redirige a `/login`. |
| **9** | Doble clic y pantalla vieja | `test_caso_9_doble_clic_y_pantalla_vieja_rechazan_con_mensaje` | **PASS** — Petición duplicada de `desvalidar` o `validar` no invierte el dato; responde con «La clase cambió mientras la mirabas. Recargá la pantalla.». |
| **10** | Validar | `test_caso_10_validar_sigue_igual_en_casos_permitidos` | **PASS** — Sigue ofreciéndose si `!validada_para_liquidacion` y `cantPresentes === 0` para el admin. |

---

## 4. Mutaciones verificadas

Se desarrollaron 4 pruebas permanentes de mutación en `DesvalidarClaseT113Test.php` que garantizan la solidez de las reglas:

1. `test_mutacion_1_control_abierta_bloquea_desvalidacion`: Si se retira el control de liquidación abierta, la prueba falla.
2. `test_mutacion_2_control_cerrada_bloquea_modificacion`: Si se retira el control de liquidación cerrada, la prueba falla.
3. `test_mutacion_3_no_se_debe_dar_vuelta_a_ciegas`: Si se implementa un toggle a ciegas (`!$clase->validada_para_liquidacion`), la prueba falla al recibir un envío redundante.
4. `test_mutacion_4_operativa_nunca_recibe_boton_en_html`: Si se relaja la condición visual y se expone el botón a la operativa, la prueba falla.

---

## 5. Evidencia en navegador real Chrome CDP

Se ejecutó la suite interactiva de navegador `docs/06-pruebas/PRU-04/evidencia-t113/comprobar-navegador-t113.mjs` sobre servidor local (`127.0.0.1:8128`) y base descartable `wings_testing_gemini` (`seed-t113.php`).

Resultados (`resultados-navegador.json`):
- `✓ PASS`: Caso 1: Botón Desvalidar y badge Validada visibles inicialmente
- `✓ PASS`: Caso 1: Clic en Desvalidar muestra flash de éxito, badge desaparece y ofrece Validar
- `✓ PASS`: Caso 2: Clase futura validada ofrece botón Desvalidar
- `✓ PASS`: Caso 2: Clase futura se desvalida correctamente
- `✓ PASS`: Caso 6: Clase en liquidación abierta no ofrece botón Desvalidar
- `✓ PASS`: Caso 6: Forzar desvalidación en liquidación abierta es rechazado con mensaje exacto
- `✓ PASS`: Caso 9: Desvalidar con pantalla vieja rechaza con «La clase cambió mientras la mirabas. Recargá la pantalla.»

### Capturas generadas (en repositorio):
- `01-t113-caso1-desvalidar-visible-desktop.png`: Clase validada en escritorio (1366px).
- `01-t113-caso1-desvalidar-visible-375.png`: Clase validada en móvil (marco 375px).
- `02-t113-caso1-desvalidada-exito-desktop.png`: Flash de éxito y botón Validar en escritorio tras desvalidar.
- `02-t113-caso1-desvalidada-exito-375.png`: Flash de éxito en móvil (375px).
- `03-t113-caso2-clase-futura-desvalidada.png`: Desvalidación de clase futura del 19/12.
- `04-t113-caso6-sin-boton-desvalidar.png`: Ausencia de botón Desvalidar en clase de liquidación abierta.
- `05-t113-caso6-rechazo-forzado.png`: Rechazo forzado en liquidación abierta.
- `06-t113-caso9-pantalla-vieja-error.png`: Error de pantalla vieja / doble clic.

Galería HTML para inspección: [APROBAR-T113.html](evidencia-t113/APROBAR-T113.html).

---

## 6. Propuesta de actualización de catálogo y manual (para Claude)

Como lo establece el prompt, Gemini no modifica directamente el catálogo ni el manual:

### A. Para el caso `CLA-024` en `docs/06-pruebas/PRU-04/tabla-de-pruebas/catalogo/CLA.json`:
- **Líneas 1037-1041 (ítem `b`):**
  - *Texto actual:* `Una vez validada, el botón desaparece: no se puede deshacer desde la pantalla.`
  - *Texto propuesto:* `El dueño puede desvalidar una clase validada desde su ficha con el botón «Desvalidar», volviendo a ofrecer «Validar» y «Editar» si no tiene presentes, y excluyéndola de liquidaciones posteriores.`

### B. Para el manual `resources/manual/admin/programar-clases.md`:
- **Sección «Desvalidar una clase validada por error»:**
  - Explicar que en la ficha de una clase validada para liquidación, el dueño dispone del botón **Desvalidar** (color gris).
  - Aclarar que al desvalidar, la clase deja de contar para futuras liquidaciones por hora y vuelve a contabilizarse en «Sin asistencia» si es pasada y no tiene alumnos presentes.
  - Aclarar que si la clase ya integra una liquidación por hora abierta, no se ofrece Desvalidar (debe eliminarse la liquidación abierta primero, desvalidar la clase y volver a generar la liquidación). Si la liquidación está cerrada, la validación no puede modificarse.

---

## 7. Verificaciones de suite y conteo de pruebas

1. **Suite T113:** `tests/Feature/DesvalidarClaseT113Test.php` (14 passed, 87 assertions).
2. **Suite relacionada T34/T67:** `tests/Feature/ClaseSinAsistenciaYDniT34T67Test.php` (11 passed, 78 assertions).
3. **Sintaxis y compilación:**
   - `php -l app/Models/Clase.php` (sin errores).
   - `php -l app/Http/Controllers/ClaseWebController.php` (sin errores).
   - `php -l resources/views/clases/show.blade.php` (sin errores).
   - `php artisan view:cache; php artisan view:clear` (compilación limpia).
4. **Conteo oficial del repositorio:**
   - Métodos en `tests/` antes de T113: **1.485**.
   - Métodos agregados por T113: **14** en `DesvalidarClaseT113Test.php`.
   - Nuevo total oficial del repositorio: **1.499 pruebas**.
   - Se actualizan `docs/00-estado/ESTADO-ACTUAL.md`, `docs/00-estado/CHECKLIST-CARLOS.md` y `docs/00-estado/PLAN-PRODUCCION.md`.
   - *Nota de entorno local:* En el árbol de trabajo local no versionado existen además 12 métodos creados por Claude para T115 y T116 en curso (`LiquidacionMesFuturoT115Test.php` con 3 y `RetiroEnTurnoT116Test.php` con 9), que no pertenecen a T113 ni se incluyen en este commit.
