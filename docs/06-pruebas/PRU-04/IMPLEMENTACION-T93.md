# Implementación T93: Filtro de subrubros en Nuevo movimiento de Cashflow

- **Tarea:** T93
- **Fecha:** 2026-10-27
- **Autor:** Gemini (CAB)
- **Verificador asignado:** Claude

---

## 1. Problema identificado

En la pantalla de **Cashflow → Nuevo movimiento** (`/cashflow/movimiento`), cuando no había ningún rubro seleccionado, el selector de subrubros mostraba en el desplegable todos los subrubros de todos los rubros del sistema. Aunque el texto inicial decía «Seleccionar rubro primero...», al desplegar la lista aparecían opciones pertenecientes a múltiples rubros incompatibles.

### Causa técnica
En [resources/js/cashflow-movimiento.js](file:///c:/xampp/htdocs/gestion-wings/resources/js/cashflow-movimiento.js), la función `filtrarSubrubros(rubroId)` contenía:
```javascript
opt.style.display = (!rubroId || opt.dataset.rubro === String(rubroId)) ? '' : 'none';
```
Cuando `rubroId` estaba vacío (`""`), `!rubroId` evaluaba como verdadero, asignando `display = ''` a todas las opciones de subrubros.

---

## 2. Solución implementada

Se modificó exclusivamente [resources/js/cashflow-movimiento.js](file:///c:/xampp/htdocs/gestion-wings/resources/js/cashflow-movimiento.js):

1. **Sin rubro elegido no se muestra ningún subrubro**:
   Se corrigió la condición a:
   ```javascript
   opt.style.display = (rubroId && opt.dataset.rubro === String(rubroId)) ? '' : 'none';
   ```
   Si no hay `rubroId`, todas las opciones con `data-rubro` quedan ocultas (`display: 'none'`). Además, se asegura que el valor del selector sea vacío (`selectSub.value = ''`) y el texto del placeholder sea «Seleccionar rubro primero...».

2. **Al elegir un rubro**:
   Aparecen únicamente los subrubros cuyo `data-rubro` coincide exactamente con el `rubroId` seleccionado. El placeholder cambia a «Seleccionar...».

3. **Al cambiar Ingreso / Egreso**:
   En `filtrarRubros(tipo)`, si el rubro seleccionado queda oculto porque pertenece al tipo contrario, o si no hay rubro seleccionado, se llama a `filtrarSubrubros('')`, lo cual vacía el subrubro (`selectSub.value = ''`) y oculta todas sus opciones.

4. **Al editar un movimiento existente**:
   La vista utiliza el mismo formulario tanto para alta como para corrección/edición. En la inicialización (`// Init`):
   ```javascript
   var tipoInicial = tipoIngreso.checked ? 'INGRESO' : 'EGRESO';
   actualizarBotonesTipo(tipoInicial);
   filtrarSubrubros(rubroActual);
   if (subrubroActual && rubroActual) selectSub.value = subrubroActual;
   ```
   Si el movimiento tiene un rubro y subrubro precargados (`rubroActual` y `subrubroActual`), los subrubros del rubro se muestran y el subrubro guardado se mantiene seleccionado.

5. **Restricciones respetadas**:
   - No se modificaron vistas Blade (`AGENTS.md` §1 y §5-bis).
   - No se modificó `app.css`.
   - No se modificaron controladores ni modelos.
   - No se tocaron `caja-movimiento.js` ni `caja-editar.js` (fuera del alcance de Carlos para esta tarea).
   - Se compiló con `npm run build` (`public/build` al día).

---

## 3. Pruebas en Chrome real (Automatizadas vía CDP)

Se ejecutó el script [probar-t93-browser.mjs](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-t93/probar-t93-browser.mjs) contra un servidor local en Chrome real sobre la base de datos `wings_testing_gemini`:

| Caso | Comprobación | Resultado |
|---|---|---|
| **1. Alta sin rubro** | `rubro_id.value === ''`, placeholder «Seleccionar rubro primero...», 0 subrubros visibles de 16 totales | **PASS** |
| **2. Elegir rubro** | Se elige «Indumentaria» (`id=3103`). Aparecen 3 subrubros, todos pertenecientes al rubro. Placeholder «Seleccionar...». | **PASS** |
| **3. Cambiar tipo I/E** | Al pasar a EGRESO, el rubro anterior queda oculto. Se vacía `rubro_id` y `subrubro_id`. Vuelven a 0 subrubros visibles y placeholder «Seleccionar rubro primero...». | **PASS** |
| **4. Editar movimiento** | Se carga un movimiento existente con rubro y subrubro guardados (`/cashflow/movimientos/229/editar`). Rubro y subrubro permanecen precargados y seleccionados, mostrando solo las opciones del rubro. | **PASS** |

---

## 4. Evidencias visuales (Capturas a 1366 px y 375 px)

- **Caso 1: Alta sin rubro elegido**
  - Escritorio (1366 px): [01-alta-sin-rubro-1366.png](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-t93/01-alta-sin-rubro-1366.png)
  - Móvil (375 px): [02-alta-sin-rubro-375.png](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-t93/02-alta-sin-rubro-375.png)

- **Caso 2: Elegir un rubro**
  - Escritorio (1366 px): [03-elegir-rubro-1366.png](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-t93/03-elegir-rubro-1366.png)
  - Móvil (375 px): [04-elegir-rubro-375.png](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-t93/04-elegir-rubro-375.png)

- **Caso 3: Cambiar Ingreso / Egreso**
  - Escritorio (1366 px): [05-cambiar-tipo-vacio-1366.png](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-t93/05-cambiar-tipo-vacio-1366.png)
  - Móvil (375 px): [06-cambiar-tipo-vacio-375.png](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-t93/06-cambiar-tipo-vacio-375.png)

- **Caso 4: Editar un movimiento existente**
  - Escritorio (1366 px): [07-editar-movimiento-1366.png](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-t93/07-editar-movimiento-1366.png)
  - Móvil (375 px): [08-editar-movimiento-375.png](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-t93/08-editar-movimiento-375.png)

---

## 5. Verificación de Suites de Pruebas

En base `wings_testing_gemini`:
- `CspSinCodigoIncrustadoTest`: **PASS** (2 passed, 2 assertions)
- Suite rápida (`--exclude-group=lento`): **PASS** (952 passed, 6 skipped, 227.98s)
