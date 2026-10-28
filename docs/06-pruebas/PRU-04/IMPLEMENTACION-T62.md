# T62 · Cancelar y reactivar una clase: aviso por lista tomada, choque y motivo conservado

> Implementación Gemini CyE · 28/10/2026.  
> Referencias: DU-30 y DU-31 (decisiones de Carlos del 13/10) · Casos CLA-012 y CLA-015.  
> Galería de aprobación visual: [`APROBAR-T62.html`](APROBAR-T62.html).

---

## 1. Decisiones de Carlos y alcance

### DU-30 (CLA-012 / CLA-013)
- **Cancelar una clase con lista tomada:** avisa que la lista ya está tomada y pide confirmación antes de cancelar.
- **Cancelación de serie:** informa cuántas clases de la serie tienen lista tomada antes de confirmar.
- **Liquidación cerrada o pagada:** impedido previamente por Claude en T90 (línea 605 de `ClaseWebController.php`); se preservó intacto.

### DU-31 (CLA-015)
- **Reactivar una clase cancelada:** controla choque de horario si alguna de las profesoras de la clase ya tiene otra clase activa que se superpone, explicando con cuál choca.
- **Conservar rastro:** se preserva que la clase estuvo cancelada y su motivo, visible en la ficha de la clase.

---

## 2. Detalle de lo implementado

### A. CLA-012.a — Aviso por lista tomada y confirmación
- En `ClaseWebController::toggleCancelada`:
  - Si la clase tiene asistencia registrada (presente o ausente: `$clase->asistencias()->exists()`) y no se envía `confirmar: true`, responde **HTTP 409 Conflict** con:
    ```json
    {
      "success": false,
      "requiere_confirmacion": true,
      "con_lista": 1,
      "message": "Esta clase ya tiene la lista tomada. Las marcas quedarán guardadas pero la clase dejará de contar para liquidaciones y comisiones. ¿Confirmás la cancelación?"
    }
    ```
  - En cancelación de serie (`cancelar_serie: true`), calcula cuántas clases activas futuras de la serie tienen lista tomada (`$conLista`). Si `$conLista > 0` y no se envía `confirmar: true`, responde 409 informando la cantidad exacta (`"X clase(s) de la serie ya tienen la lista tomada..."`).
  - Con `confirmar: true`, ejecuta la cancelación de la clase o de la serie guardando el motivo ingresado.
- En `resources/views/clases/show.blade.php` y `resources/js/clases-show.js`:
  - Panel `#panel-cancelar` incluye aviso si la clase ya tiene asistencia.
  - Al pulsar *Cancelar esta* o *Cancelar serie*, si el servidor responde 409, se despliega el subpanel `#subpanel-confirmar-lista` con el mensaje y botones canónicos del design system:
    - **Confirmar:** `.ds-btn .ds-btn--danger` (96×32 px).
    - **Cerrar:** `.ds-btn .ds-btn--secondary` (96×32 px).

### B. CLA-012.b y c — Marcas guardadas y liquidación / Hallazgo en Generación
- **Marcas de asistencia:** al cancelar, los registros en la tabla `asistencias` se conservan intactos para auditoría y visualización.
- **Liquidación y comisión:** `LiquidacionService` filtra estrictamente `where('cancelada', false)` tanto en `clasesLiquidablesHora` como en `clasesLiquidablesComision`. Una clase cancelada no suma clases dictadas ni comisiones.
- **Hallazgo reportado en generación del mes siguiente (`GenerarDeudasMensualesCommand.php`):**
  - La instrucción de Carlos indicó: *«Es lo que hace hoy: verificarlo, no cambiarlo. Si no se cumple, informarlo antes de corregir.»*
  - **Relevamiento:** En `app/Console/Commands/GenerarDeudasMensualesCommand.php` (líneas 131-136), la consulta `whereHas('clase', ...)` no filtra `where('cancelada', false)`. Por lo tanto, si el único presente de un alumno fue en una clase cancelada, la consulta actual lo considera presente y le genera deuda en vez de enviarlo a Revisión de cobranza.
  - Conforme al protocolo (AGENTS.md §6b) y la orden de no alterar el comando sin autorización previa, **no se modificó `GenerarDeudasMensualesCommand.php`** y queda reportado para decisión de Carlos.

### C. CLA-015.b — Control de choques al reactivar
- En `app/Services/ClaseService.php` (`validarSolapamientoProfesor`):
  - Se añadió la condición `where('clases.cancelada', false)`. Una clase que estuvo cancelada no genera falso choque al evaluar solapamientos.
  - La excepción lanzada incluye el nombre del grupo de la clase existente.
- En `ClaseWebController::toggleCancelada`:
  - Al reactivar (`$clase->cancelada == true`), para cada profesora asignada se ejecuta `$this->claseService->verificarDisponibilidadProfesor($clase->id, $profesor->id)`.
  - Si choca con otra clase activa en ese horario, se rechaza la reactivación con **HTTP 422** (o `back()->with('error', ...)` en envío tradicional) detallando:
    `"No se puede reactivar: la profesora [Nombre] ya tiene asignada la clase '[Grupo]' que se solapa en fecha [Fecha] entre [Hora inicio] y [Hora fin]."`
  - Si la otra clase en ese horario está cancelada, no se considera choque y la reactivación se permite.

### D. CLA-015.c — Conservación del motivo al reactivar
- En `ClaseWebController::toggleCancelada`:
  - Al reactivar, la clase se actualiza con `['cancelada' => false]`, **sin blanquear** `motivo_cancelacion`.
- En `resources/views/clases/show.blade.php`:
  - Si la clase está activa pero tiene motivo de cancelación histórico, la cabecera muestra junto al badge `Activa` el texto:
    `<span id="rastro-cancelacion">(Estuvo cancelada: Motivo)</span>`.

### E. CLA-015.a y d — Estado programado, asistencia y listados
- Al reactivar, la clase vuelve a `cancelada = false`.
- El banner de bloqueo de asistencias desaparece y el controlador `storeAsistencias` permite volver a registrar presentes/ausentes.
- La clase reactivada vuelve a sumar en los conteos de `ClaseWebController::index` y, si su fecha es pasada y no tiene asistencias ni validación, vuelve a figurar en el filtro «Sin resolver / Sin asistencia».

---

## 3. Pruebas y verificación

Se creó la suite de pruebas permanentes `tests/Feature/CancelarReactivarClaseT62Test.php` con 10 métodos y 53 aserciones, todas en verde en `wings_testing_gemini`:

1. `test_cancelar_clase_sin_asistencia_no_pide_confirmacion`: cancela directamente sin confirmación.
2. `test_cancelar_clase_con_asistencia_avisa_y_exige_confirmacion`: avisa con 409 y confirmación requerida.
3. `test_cancelar_serie_con_asistencias_informa_cuantas_tienen_lista_y_pide_confirmacion`: conteo exacto de clases con lista en la serie.
4. `test_marcas_de_asistencia_quedan_guardadas_y_clase_no_cuenta_en_liquidacion`: marcas persisten y LiquidacionService no las cuenta.
5. `test_reactivar_falla_si_la_profesora_ya_tiene_otra_clase_activa_que_se_superpone`: 422 con detalle de profesora, grupo y horario.
6. `test_reactivar_permite_si_la_otra_clase_superpuesta_esta_cancelada`: no choca contra clases canceladas.
7. `test_al_reactivar_se_conserva_el_motivo_en_la_base_y_se_muestra_en_la_ficha`: rastro `(Estuvo cancelada: ...)` visible.
8. `test_reactivada_permite_guardar_asistencias`: desbloqueo efectivo de asistencia.
9. `test_reactivada_pasada_vuelve_a_contar_en_sin_asistencia`: reincorporación al filtro `estado=finalizada`.
10. `test_operativo_no_puede_reactivar_y_profesor_no_puede_cancelar_ni_reactivar`: control estricto de permisos y roles.

### Pruebas de regresión y guardianes
- `CancelarClaseLiquidadaT90Test`: 4 pruebas / 21 aserciones en verde.
- `CspSinCodigoIncrustadoTest`: 2 pruebas en verde (0 bloques y 0 manejadores inline añadidos).
- `TableroAlDiaTest`: 6 pruebas en verde.

---

## 4. Evidencia visual generada

Generadas en `docs/06-pruebas/PRU-04/evidencia-t62/` mediante Chrome headless con marco de 375 px:
- `01-aviso-lista-tomada-1366.png` y `01-aviso-lista-tomada-375.png`: aviso y botones `Confirmar` y `Cerrar`.
- `02-rechazo-por-choque-1366.png` y `02-rechazo-por-choque-375.png`: flash de error con detalle del choque.
- `03-motivo-conservado-1366.png` y `03-motivo-conservado-375.png`: clase activa con leyenda `(Estuvo cancelada: Feriado imprevisto)`.

Consolidadas para revisión en [`APROBAR-T62.html`](APROBAR-T62.html).
