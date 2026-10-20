# T64 — Las reglas de primer pago no pueden superponerse ni dejar días sin cubrir

Gemini CAB, 20/10/2026. Implementación local.
Decisión de Carlos: **DU-45 («Impedir reglas superpuestas y días sin cubrir»)**. Caso: **CFG-004**.
Revisión independiente: Claude / Codex.

---

## 1. Regla de negocio

1. **Cobertura continua del mes (días 1 al 31):**
   - El conjunto de reglas activas de primer pago debe cubrir **la totalidad de los días del 1 al 31**.
   - Comienza obligatoriamente en el **día 1** (`dia_desde == 1`).
   - Termina obligatoriamente en el **día 31** (`dia_hasta == 31`).
   - No pueden existir huecos entre tramos consecutivos (`siguiente.dia_desde == actual.dia_hasta + 1`).
   - No pueden existir superposiciones entre tramos (`siguiente.dia_desde > actual.dia_hasta`).
   - Debe existir al menos una regla activa.

2. **Mensajes claros en castellano:**
   - Ante superposiciones:
     *«Los días X a Y se pisan con la regla "Nombre", que va del A al B. Un mismo día no puede tener dos porcentajes.»*
     (o *«El día X se pisa con la regla...»* si es un solo día).
   - Ante días sin cubrir (huecos):
     *«Los días X a Y quedan sin cubrir. Todos los días del 1 al 31 deben estar cubiertos por una regla.»*
     (o *«El día X queda sin cubrir...»* si es un solo día).
   - Si no hay reglas:
     *«Debe existir al menos una regla que cubra del día 1 al 31.»*

3. **Invarianza de deudas y pagos históricos (CFG-004 e):**
   - Las cuotas y deudas ya emitidas no sufren alteraciones.
   - Las claves foráneas en `pagos.regla_primer_pago_id` se preservan: el guardado en lote actualiza in-place las reglas existentes manteniendo sus IDs numéricos.

---

## 2. El problema de diseño resuelto

### El dilema de estados intermedios
Si cada regla se guardara de forma aislada, pasar de «1–15 y 16–31» a «1–10 y 11–31» obligaba a un estado intermedio inválido:
- Si se editaba primero la regla 1 a 1–10: quedaba un hueco del 11 al 15 (la regla 2 seguía en 16–31) y se bloqueaba.
- Si se editaba primero la regla 2 a 11–31: se superponía del 11 al 15 con la regla 1 (que seguía en 1–15) y se bloqueaba.

### La solución: Guardado atómico de lote
1. **Endpoint `PUT /configuraciones/primer-pago`:** Recibe `{ "reglas": [ ... ] }`.
2. **Validación de integridad completa (`App\Support\ReglasPrimerPagoValidator`):**
   - Ordena los tramos por `dia_desde`.
   - Verifica inicio en día 1, continuidad exacta tramo a tramo, y cierre en día 31.
3. **Transacción atómica (`DB::transaction`):**
   - Actualiza las reglas existentes que conservan su ID.
   - Crea nuevas reglas si hay altas en el conjunto.
   - Elimina las que fueron retiradas del conjunto.
   - Si cualquier validación falla, se retorna 422 y **no se persiste ningún cambio** (rollback total).
4. **Operaciones individuales (`store`, `update`, `destroy`):**
   - Simulan el conjunto resultante contra la base de datos y validan con `ReglasPrimerPagoValidator`.
   - Cualquier intento de dejar huecos o superposiciones individuales es rechazado.

---

## 3. Cambios reales

1. **Validador de Conjunto (`app/Support/ReglasPrimerPagoValidator.php`):**
   - Método estático `validar(array $reglas): ?string`:
     Retorna `null` si el conjunto cubre exactamente del 1 al 31 sin fisuras ni solapamientos, o el mensaje exacto en castellano indicando qué días se pisan o quedan sin cubrir.

2. **Controlador Web (`app/Http/Controllers/ReglaPrimerPagoWebController.php`):**
   - `guardarTodas(Request $request)`: implementado con validación de array y transacción de base de datos.
   - `store`: si viene un lote delega a `guardarTodas`; si viene una regla individual simula el conjunto y exige cobertura completa.
   - `update`: simula el conjunto reemplazando la regla `$id` y valida cobertura completa.
   - `destroy`: simula el conjunto retirando la regla `$id` y rechaza si deja hueco o si era la última.

3. **Rutas (`routes/web.php`):**
   - Registrada ruta `PUT /configuraciones/primer-pago` (`web.config.primer-pago.guardar-todas`) antes de la ruta con `{id}`.

4. **Cliente (`resources/js/configuraciones.js`):**
   - Función `recopilarReglas`: obtiene todos los tramos de las tarjetas activas y/o el panel de agregar regla.
   - Modificados los eventos de guardado de tarjetas, adición y eliminación para enviar el conjunto a `PUT /configuraciones/primer-pago`.
   - Función `renderizarReglas`: redibuja las tarjetas y actualiza el contador tras guardado exitoso.
   - Exhibe errores de validación en el panel abierto y en el resumen superior de errores.
   - Cumple **AGENTS.md §5-bis**: sin bloques `<script>` en Blade; compilado con `npm run build`.

5. **Integridad del Diseño Visual (AGENTS.md §1):**
   - **No se modificó ninguna vista Blade bajo `resources/views/**` ni hojas de estilo bajo `resources/css/**`.**
   - `git diff --stat -- resources/views resources/css` está completamente vacío.

---

## 4. Pruebas automatizadas

### A. Nueva suite: `tests/Feature/ReglaPrimerPagoSinHuecosT64Test.php`
8 pruebas automatizadas cubriendo el 100% de los requerimientos:
1. `test_hueco_al_crear_es_rechazado`: rechaza crear regla de 18 a 31 dejando hueco 16–17.
2. `test_hueco_al_editar_es_rechazado`: rechaza achicar tramo de 1–15 a 1–10 dejando hueco 11–15.
3. `test_hueco_al_borrar_es_rechazado`: rechaza borrar tramo 16–31 dejando hueco al final.
4. `test_borrar_la_unica_regla_es_rechazado`: rechaza borrar la única regla del mes.
5. `test_superposicion_es_rechazada`: rechaza tramos que solapan días tanto individual como en lote.
6. `test_cambio_de_tramos_1_15_y_16_31_a_1_10_y_11_31_en_lote_pasa`: comprueba la transición atómica exitosa conservando IDs.
7. `test_lote_invalido_hace_rollback_y_no_guarda_nada`: comprueba que un lote con hueco no persista nada.
8. `test_primera_cuota_del_alta_toma_la_regla_del_dia`: comprueba que `PagoCuotaService::calcularReglaPrimerPago` aplique la regla correspondiente al día del alta (100%, 70%, 40%).

### B. Suite de superposición adaptada: `tests/Feature/ReglaPrimerPagoSinSuperposicionTest.php`
5 pruebas adaptadas para respetar la nueva exigencia de no dejar días sin cubrir.

**Resultado en `wings_testing_gemini`:**
- `ReglaPrimerPagoSinHuecosT64Test`: 8 passed (29 assertions).
- `ReglaPrimerPagoSinSuperposicionTest`: 5 passed (40 assertions).
- `ConfiguracionA11Test`: 8 passed (98 assertions).

---

## 5. Evidencias visuales

Guardadas en `docs/06-pruebas/PRU-04/evidencia-t64/`:
- `01-configuracion-reglas-1366.png`: Listado de reglas (1366px escritorio).
- `02-configuracion-reglas-marco-375.png`: Listado de reglas (375px teléfono en marco).
- `03-configuracion-reglas-edicion-1366.png`: Panel de edición inline (1366px escritorio).
- `04-configuracion-reglas-edicion-marco-375.png`: Panel de edición inline (375px teléfono en marco).
- `05-configuracion-reglas-error-1366.png`: Error de días sin cubrir (1366px escritorio).
- `06-configuracion-reglas-error-marco-375.png`: Error de días sin cubrir (375px teléfono en marco).

Documento aprobador navegable:
`docs/06-pruebas/PRU-04/APROBAR-T64.html`.

---

## 6. Catálogo actualizado

- `docs/06-pruebas/PRU-04/tabla-de-pruebas/catalogo/CFG.json`: caso `CFG-004` actualizado; punto f anterior («se cobra al 100% sin avisar») marcado como obsoleto por DU-45, reemplazado por la verificación de impedimento de superposición y días sin cubrir.
