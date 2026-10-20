# Verificación T73 — Cero JavaScript dentro de las vistas

**Fecha:** 20/10/2026  
**Verificador:** Gemini (Antigravity)  
**Implementó:** Claude CyE (commit `b05bda9`)  
**Entorno de prueba:** Base `wings_testing_gemini` exclusivamente, servidor PHP embebido en `http://127.0.0.1:8740` ejecutando contra assets recién compilados (`npm run build`). Pruebas mediante navegación y clics reales con Chrome CDP (`scripts/pruebas/cdp-helper.mjs`).  
**Estado:** **APROBADO** — Cero JavaScript incrustado, funcionalidad idéntica a la original comprobada en navegador con clics e interacciones reales.

---

## 1. Alcance y antecedentes

Por orden directa de Carlos del 13/10/2026 (incorporada en `AGENTS.md` §5-bis), ningún agente puede escribir JavaScript adentro de una vista Blade: ni bloques `<script>` sin `src`, ni atributos de eventos inline (`onclick`, `onsubmit`, `onchange`).

En el commit `b05bda9` (T73), Claude CyE extrajo:
- **8 bloques `<script>`** de 8 vistas hacia 5 módulos dedicados en `resources/js/` y funciones comunes en `resources/js/ds-app.js`.
- **8 manejadores `onsubmit="return confirm(...)"`** hacia el mecanismo declarativo `form[data-confirmar-envio="..."]` administrado centralmente por `ds-app.js`.

---

## 2. Metodología de verificación independiente

1. **Compilación de producción:** Se ejecutó `npm run build` para asegurar que Vite compilara los módulos `caja-editar.js`, `clases-show.js`, `clases-index.js`, `liquidaciones-create.js`, `alumnos-index.js` y `ds-app.js`.
2. **Ambiente aislado:** Se utilizó la base de pruebas `wings_testing_gemini` con migraciones frescas (`migrate:fresh --seed`) y un seeder de prueba sintético (`docs/06-pruebas/PRU-04/evidencia-verificacion-t73-gemini/seed-t73.php`).
3. **Navegador real (Chrome CDP):** Se ejecutó un arnés de pruebas automatizado sobre Chrome real (`docs/06-pruebas/PRU-04/evidencia-verificacion-t73-gemini/verificar-t73.mjs`), interactuando mediante clics de mouse, teclas de navegación, despacho de eventos DOM nativos, y escuchando diálogos `window.confirm` así como capturando logs de consola y avisos de CSP.

---

## 3. Matriz de resultados por punto

| Punto | Pantalla / Módulo | Qué se probó | Resultado | Evidencia |
|---|---|---|---|---|
| **1** | `auth/login` (`ds-app.js`) | Clic en el botón con ojo (`.btn-toggle-password`): cambia `type="password"` a `text` y viceversa; cambia ícono SVG. | **PASÓ** | Verificado en DOM con clics reales. |
| **2** | `grupos/index` & `profesores/index` (`ds-app.js`) | Selects de deporte con `data-enviar-al-cambiar`: cambio de valor dispara submit automático del formulario y recarga con query param (`deporte_id=X`). | **PASÓ** | URL final incluye `deporte_id=3` y `deporte_id=4`. |
| **3** | `caja/editar` (`caja-editar.js`) | Clics en tabs de tipo (Ingreso/Egreso): cambian estilos de tabs (`color`, `borderLeft`), filtran opciones del select `#rubro_id` según `data-tipo`, y al cambiar rubro filtra select `#subrubro_id`. Valores iniciales preservados. | **PASÓ** | Captura `03-caja-editar-1366.png`. |
| **4** | `clases/show` (`clases-show.js`) | Checkbox presente incrementa contador (`0 -> 1`), muestra aviso de exceso si el alumno superó su plan semanal, botón guardar envía vía AJAX y muestra toast. Modal cancelar exige motivo antes de enviar. Panel modificar profesoras se abre/cierra y actualiza lista vía AJAX. | **PASÓ** | Captura `04-clase-show-1366.png`. |
| **5** | `clases/index` (`clases-index.js`) | Scroll automático a la primera clase futura en la ventana de hoy (`#clases-hoy-container`). Selects de filtro envían formulario al cambiar (`deporte_id`, `orden`). | **PASÓ** | Navegación confirmada con parámetros en URL. |
| **6** | `liquidaciones/crear` (`liquidaciones-create.js`) | Radio de profesor marca fila como `.selected` y muestra banner de advertencia si el docente tiene clases sin asistencia cargada. Botones de "Incluir hoy" alternan valor del hidden y clases/estilos visuales. | **PASÓ** | Captura `06-liquidacion-create-1366.png`. |
| **7** | `alumnos/index` (`alumnos-index.js`) | Búsqueda con debounce (250ms), dropdown con resultados, navegación con flechas de teclado (`ArrowDown`/`ArrowUp`) marcando `.active`. **Seguridad XSS:** Alumno con HTML inyectado en su nombre (`<b>Martin</b>`) es renderizado con texto plano escapado vía `document.createElement('span').textContent`, impidiendo inyección DOM. | **PASÓ** | Captura `07-alumnos-autocomplete-1366.png`. |
| **8** | Confirmaciones (8 formularios con `data-confirmar-envio`) | Se probaron las 8 confirmaciones del sistema: cancelar el diálogo (`window.confirm = false`) aborta el submit y mantiene botones/formularios habilitados (sin bloqueo por doble clic). Aceptar el diálogo (`window.confirm = true`) envía el formulario correctamente, incluso en entidades con comillas en el nombre (`Rubro "Especial T73"`). | **PASÓ** | Captura `08-confirmacion-abierta-1366.png` y verificación de los 8 formularios. |
| **9** | Consola & Seguridad CSP | Monitoreo de eventos `Console.messageAdded` y `Security.securityStateChanged` durante todo el recorrido de pruebas: 0 errores de JavaScript y 0 violaciones/avisos de Content Security Policy. | **PASÓ** | Cero errores ni avisos en consola. |

---

## 4. Detalle de las 8 confirmaciones verificadas (Punto 8)

1. `grupos/show`: Eliminar plan de grupo (`¿Eliminar este plan del grupo? Los alumnos que lo tengan asignado pasarán a no tener plan.`).
2. `liquidaciones/index`: Eliminar borrador de liquidación (`¿Eliminar esta liquidación? Esta acción no se puede deshacer.`).
3. `liquidaciones/show`: Cerrar liquidación (`¿Cerrar esta liquidación? No podrá ser modificada después.`).
4. `liquidaciones/show`: Recalcular liquidación (`¿Recalcular? Los detalles actuales serán reemplazados.`).
5. `liquidaciones/show`: Eliminar liquidación permanentemente (`¿Eliminar esta liquidación permanentemente?`).
6. `niveles/index`: Eliminar nivel (`¿Eliminar el nivel «Nivel Borrable T73»?`).
7. `rubros/index`: Eliminar rubro ingreso (`¿Eliminar Rubro Ingreso Borrable T73?`).
8. `rubros/index`: Eliminar rubro egreso con comillas (`¿Eliminar Rubro "Especial T73"?`).

En todas se constató que la cancelación no dispara el listener de protección de doble submit (`form.dataset.enviando`), dejando el formulario y el botón de submit completamente operativos.

---

## 5. Pruebas automáticas de soporte

Se ejecutaron las pruebas automatizadas del suite:
```bash
DB_DATABASE=wings_testing_gemini vendor/bin/phpunit tests/Feature/CspSinCodigoIncrustadoTest.php tests/Feature/AlumnoAutocompleteViewSecurityTest.php
```
- `CspSinCodigoIncrustadoTest`: 2 pruebas, 4 afirmaciones — **VERDE**. Confirma 0 bloques `<script>` sin src y 0 atributos de eventos inline en las vistas.
- `AlumnoAutocompleteViewSecurityTest`: 2 pruebas, 2 afirmaciones — **VERDE**. Confirma que la vista de alumnos no contiene código incrustado y carga el bundle compilado.

---

## 6. Dictamen y recomendación

La implementación de **T73** realizada por Claude en commit `b05bda9` cumple en su totalidad los requisitos de diseño, seguridad y funcionalidad interactiva:
- No existen regresiones de interfaz ni de comportamiento.
- Todos los componentes JavaScript responden a los clics e interacciones de usuario en un navegador Chrome real.
- Se respetó la prohibición estricta de JavaScript dentro de las vistas Blade (AGENTS.md §5-bis).

**Dictamen:** Tarea **T73 APROBADA y CERRADA**.
