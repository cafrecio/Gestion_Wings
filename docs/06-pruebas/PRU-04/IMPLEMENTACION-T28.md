# Informe de Implementación T28: Cashflow muestra a quién se le cobró, quién cobró y quién validó

**Fecha:** 12/10/2026 (Segunda vuelta)  
**Autor:** Gemini (CAB) — Firma: **LOG GEM CAB**  
**Tarea:** T28 — «Cashflow: vincular sin adivinar y mostrar quién validó»  
**Origen:** Día 1 de la prueba por días y revisión de Claude (12/10/2026). Decisiones de Carlos del 12/10/2026.  
**Estado:** Implementada y con diseño visual aprobado por Carlos (12/10/2026: Opción 1 escritorio «Val.», Opción 2 celular badge «Val: CB», desempate CB/CZ). Listo para verificación de regla de negocio por otro agente.

---

## 1. Problema que resuelve y evolución

### Primera vuelta:
- Al validar una caja, Cashflow mostraba la observación técnica en vez del nombre del alumno y a quien validó en vez de a quien cobró.
- Se implementó la visualización del nombre del alumno y de la operativa que cobró en el mostrador.
- Claude identificó en la verificación que el vínculo dependía de heurística de textos sobre la observación.

### Segunda vuelta (decisiones de Carlos del 12/10/2026):
1. **Vincular sin adivinar:** Cada renglón nuevo de Cashflow generado desde una caja validada sabe de qué movimiento viene mediante un dato guardado (`movimiento_operativo_id`), con su migración de base de datos autorizada explícitamente por Carlos.
2. **Tratamiento de movimientos preexistentes:** Renglones anteriores al cambio sin ID guardado exigen coincidencia exacta de importe + subrubro + observación con un único movimiento de la caja. Se eliminó completamente el segundo intento por solo importe y subrubro: si no hay certeza exacta, se muestra la observación original y nunca se adivina un alumno.
3. **Columna de quién validó la caja:**
   - En escritorio: columna angosta «Val.» (50px) al final de la tabla con las 2 iniciales del administrador que validó (ej. `CB`, `VA`), y en el tooltip (`title`) el nombre completo. Si el movimiento no vino de caja (cobro directo o gasto manual del dueño), la celda queda completamente vacía (ni iniciales, ni guión, ni texto).
   - En celular: la tarjeta muestra `Registró: <nombre>` a la izquierda y un badge compacto `Val: <iniciales>` a la derecha. Si es directo del dueño, solo muestra `Registró: <nombre>`.
   - Cuidado de importes: el ancho de la columna Monto se reservó en 125px holgados para soportar montos grandes con decimales sin desbordar (`FormatoImporte::pesos(...)`), tal como Carlos indicó.
   - Desempate de iniciales: si dos administradores activos comparten iniciales (ej. Carlos Bonifacio y Carlos Benítez), desempata la segunda letra con la última del apellido (ej. `CB` y `CZ` o `CO`), según decisión de Carlos.

---

## 2. Cambios realizados en el código

1. **Migración de base de datos:**  
   `database/migrations/2026_10_12_120000_add_movimiento_operativo_id_to_cashflow_movimientos.php`: agrega la columna `movimiento_operativo_id` (foreignId nullable hacia `movimientos_operativos` con `nullOnDelete`).
2. **Modelo `App\Models\CashflowMovimiento`:**  
   - Agregado `movimiento_operativo_id` a `$fillable`.  
   - Relación `movimientoOperativo(): BelongsTo` hacia `MovimientoOperativo`.
3. **Modelo `App\Models\User`:**  
   - Método `iniciales(): string` con desempate automático de 2 letras en caso de colisión entre administradores activos (`CB` vs `CZ`).
4. **Servicio `App\Services\CashflowIntegracionCajaService`:**  
   - En `reflejarCajaEnCashflow()`, al crear cada `CashflowMovimiento` se persiste `'movimiento_operativo_id' => $movimiento->id`. Idempotencia intacta.
5. **Controlador `App\Http\Controllers\CashflowWebController`:**  
   - En `index()`:
     - Eager loading de `movimientoOperativo.alumno`, `movimientoOperativo.usuario` y `CajaOperativa::with('usuarioAdminValidacion')`.
     - Si `movimiento_operativo_id` está presente: resuelve alumno y quien registró directamente sin mirar observaciones ni heurística.
     - Si es un renglón viejo sin ID: coincidencia exacta o nada (conserva la observación original).
     - Si vino de caja: asigna `$mov->validadorNombre` y `$mov->validadorIniciales`. Si no vino de caja, quedan en `null`.
6. **Vista `resources/views/cashflow/index.blade.php`:**  
   - **Escritorio:** Encabezado con columna «Val.» (50px) y columna Monto (125px holgado para soportar decimales). Renglón directo muestra celda `<td></td>` vacía; renglón de caja muestra iniciales con `title` del nombre completo.
   - **Celular (375px):** En la tarjeta, línea inferior con `Registró: <nombre>` a la izquierda y badge `Val: <iniciales>` a la derecha cuando vino de caja. Renglones sin scroll horizontal.

---

## 3. Pruebas automatizadas permanentes

En `tests/Feature/CashflowVinculoDirectoYValidadorTest.php` (5 pruebas, 30 aserciones):
1. `test_validacion_de_caja_guarda_el_id_del_movimiento_operativo_en_cada_renglon_de_cashflow`: Verifica que cada fila creada en Cashflow tenga su `movimiento_operativo_id` apuntando a su movimiento operativo.
2. `test_renglon_con_id_muestra_alumno_del_movimiento_y_quien_lo_cargo_sin_mirar_observacion`: Modificando la observación a un texto arbitrario sin números ni nombres, demuestra que el alumno y quien registró se resuelven de forma 100% certera por el ID.
3. `test_renglon_viejo_sin_id_coincidencia_exacta_muestra_alumno_y_sin_coincidencia_exacta_muestra_observacion`: Coincidencia exacta muestra el alumno; si la observación no coincide exacto, muestra la observación original y nunca adivina.
4. `test_columna_valido_muestra_iniciales_correctas_cuando_viene_de_caja_y_vacia_cuando_es_directo`: Caja validada por Vanina Attorresi muestra `VA` en escritorio y en celular, con tooltip completo; movimiento directo de Carlos Bonifacio deja la celda vacía y sin badge.
5. `test_desempate_de_iniciales_cuando_dos_administradores_comparten_iniciales`: Carlos Bonifacio (`CB`) y Carlos Benítez (`CZ`) desempatan en 2 letras exactas.

Resultado en `wings_testing_gemini`: **100% pasando (5/5 tests, 30 aserciones)**.  
Adicionalmente, `CashflowDetalleAlumnoYUsuarioTest` (5/5) y `VerificacionT28ClaudeTest` (2/2 con 40 cobros idénticos paginados) pasan al 100%.

---

## 4. Capturas reglamentarias finales (v2)

Guardadas en `docs/06-pruebas/PRU-04/evidencia-t28/` generadas desde la aplicación real (`CapturaT28Test.php`) y capturadas con Google Chrome headless (`tomar-capturas.mjs`):

| Vista | Dispositivo | Archivo |
|---|---|---|
| Cashflow escritorio con columna Val. y montos con decimales (Opción 1) | Escritorio (1366 × 950) | `docs/06-pruebas/PRU-04/evidencia-t28/cashflow-v2-escritorio.png` |
| Cashflow celular con badge Val: CB y montos con decimales (Opción 2) | Celular marco 375 px | `docs/06-pruebas/PRU-04/evidencia-t28/cashflow-v2-celular-375.png` |
| Control de método (Login) | Escritorio (1366 × 950) | `docs/06-pruebas/PRU-04/evidencia-t28/login-control-v2-escritorio.png` |
| Control de método (Login) | Celular marco 375 px | `docs/06-pruebas/PRU-04/evidencia-t28/login-control-v2-celular-375.png` |

---

## 5. Próximo paso

El diseño visual (Opción 1 escritorio, Opción 2 celular, y desempate CB/CZ) fue definido y aprobado por Carlos el 12/10/2026. La tarea pasa a `a_verificar` en el tablero a cargo de Claude para verificar en código y base de datos.
