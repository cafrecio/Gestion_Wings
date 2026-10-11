# IMPLEMENTACIÓN T21 — Clases Canceladas de Hoy Visibles en Clases

**Resultado: IMPLEMENTADO Y EN ESPERA DE VERIFICACIÓN VISUAL DE CARLOS**

Estado en el tablero: `a_verificar` (hizo Gemini, tiene Carlos para aprobar capturas; luego verifica otro agente la regla de negocio).

---

## 1. Qué cambió

Carlos decidió el 10/10/2026:
> *«Las clases canceladas de hoy siguen en el bloque «Hoy», al final, apagadas, con la etiqueta Cancelada y el motivo de la cancelación a la vista.*
> *El filtro Cancelada también encuentra las de hoy.*
> *Solo cambia qué se muestra. No cambia quién puede cancelar, activar ni tomar asistencia.»*

### Archivos modificados y motivo:

1. **`app/Http/Controllers/ClaseWebController.php` (método `index`):**
   - **Bloque «Hoy»:** Se eliminó la condición restrictiva `->where('cancelada', false)`. Se agregó ordenamiento doble: `->orderBy('cancelada')->orderBy('hora_inicio')` para que las clases en pie aparezcan primero por horario y las canceladas se ubiquen al final por horario.
   - **Separación de roles para profesor:** La lógica de `$misClasesHoy` y `$otrasClasesHoy` hereda el mismo orden natural (activas primero, canceladas al final).
   - **Listado inferior con filtros:** Se flexibilizó `whereDate('fecha', '!=', $hoy)`: ahora solo se aplica si **no** se está filtrando por `estado = cancelada` ni se filtró la fecha de hoy. Con esto:
     * Si no hay filtros, no se duplican las clases de hoy en el listado inferior (se muestran únicamente en el bloque «Hoy»).
     * Si se usa el filtro `Cancelada` (solo o con fecha de hoy), las canceladas de hoy aparecen en los resultados filtrados.

2. **`resources/views/clases/index.blade.php`:**
   - **Contador del encabezado de «Hoy»:** Se actualizó `· <strong>{{ $misClasesHoy->count() }}</strong> clase(s)` por `· <strong>{{ $misClasesHoy->where('cancelada', false)->count() }}</strong> clase(s)`, para que el encabezado no cuente las clases canceladas como clases a dar.
   - **Muestra de canceladas cuando todo el día está cancelado:** Al no estar vacía la colección `$misClasesHoy`, no se muestra el mensaje de día vacío («No hay clases programadas para hoy»), sino las tarjetas canceladas con sus datos.

3. **`resources/views/clases/_card.blade.php`:**
   - **Etiqueta Cancelada destacada:** Cuando `$clase->cancelada` es verdadero, se muestra la etiqueta tipo pill con fondo suave y texto en `var(--color-danger)`: `padding:2px 8px; border-radius:999px; background:color-mix(in srgb, var(--color-danger) 15%, transparent); color:var(--color-danger);`.
   - **Motivo de cancelación a la vista:** Si la clase cancelada posee `$clase->motivo_cancelacion`, se exhibe debajo del encabezado con indentación alineada al título:
     `<p style="font-size:0.75rem; color:var(--color-text-muted); margin:0 0 0.75rem 1.5rem;"><strong>Motivo:</strong> {{ $clase->motivo_cancelacion }}</p>`.
   - **Tarjeta apagada:** Se mantiene `opacity: 0.6;` inline sin tocar archivos compartidos ni `resources/css/app.css`.

---

## 2. Pruebas permanentes en `tests/Feature/ClasesCanceladasHoyTest.php`

Se agregaron 9 pruebas permanentes cubriendo todos los criterios solicitados (todas en verde):
1. `test_admin_y_operativo_ven_clase_cancelada_de_hoy_en_bloque_hoy`: Admin y Operativo ven la clase cancelada hoy, con badge Cancelada y motivo.
2. `test_profesora_ve_su_clase_cancelada_en_tus_clases_y_otra_en_otras_clases`: Lucía ve su cancelada en «Tus clases» y la cancelada de Mariela en «Otras clases de hoy».
3. `test_clase_cancelada_de_hoy_aparece_al_final_despues_de_las_clases_en_pie`: Ordenamiento garantizado (activas primero, canceladas al final independientemente de la hora de inicio).
4. `test_encabezado_de_hoy_no_cuenta_las_canceladas_como_clases_a_dar`: Con 1 activa y 2 canceladas, el encabezado muestra `1 clase(s)`.
5. `test_si_todas_las_de_hoy_estan_canceladas_se_ven_y_no_muestra_cartel_vacio`: Muestra `0 clase(s)` y renderiza las tarjetas canceladas sin el cartel de día vacío.
6. `test_filtro_cancelada_encuentra_la_clase_cancelada_de_hoy`: Funciona con filtro solo, filtro + fecha hoy, y para profesora con filtro + profesor_id.
7. `test_sin_filtros_no_repite_clases_de_hoy_en_listado_inferior`: El listado inferior sin filtros muestra solo clases futuras (`> hoy`).
8. `test_asistencia_sigue_bloqueada_en_clase_cancelada_de_hoy`: Intento de guardar asistencia responde HTTP 422 JSON y la ficha muestra aviso de bloqueo con botón deshabilitado.
9. `test_contador_sin_asistencia_no_cuenta_clases_canceladas_de_hoy`: El badge de clases pendientes no suma clases canceladas de hoy.

---

## 3. Capturas del sistema andando (Escritorio y Celular 360px con marco)

Rutas exactas de las capturas guardadas en `docs/06-pruebas/PRU-04/evidencia-t21/`:

### Control de método (Login)
- **Escritorio (1366px):** [`docs/06-pruebas/PRU-04/evidencia-t21/login-control-desktop.png`](evidencia-t21/login-control-desktop.png)
- **Celular (360px con marco):** [`docs/06-pruebas/PRU-04/evidencia-t21/login-control-mobile-360.png`](evidencia-t21/login-control-mobile-360.png)
*(El formulario entra completo y centrado, validando que el marco de 360px no recorta).*

### Escenario 1: Una cancelada y otra en pie el mismo día
- **Admin:**
  - Escritorio (1366px): [`docs/06-pruebas/PRU-04/evidencia-t21/admin-parcial-desktop.png`](evidencia-t21/admin-parcial-desktop.png)
  - Celular (360px con marco): [`docs/06-pruebas/PRU-04/evidencia-t21/admin-parcial-mobile-360.png`](evidencia-t21/admin-parcial-mobile-360.png)
- **Operativo (Sandra):**
  - Escritorio (1366px): [`docs/06-pruebas/PRU-04/evidencia-t21/operativo-parcial-desktop.png`](evidencia-t21/operativo-parcial-desktop.png)
  - Celular (360px con marco): [`docs/06-pruebas/PRU-04/evidencia-t21/operativo-parcial-mobile-360.png`](evidencia-t21/operativo-parcial-mobile-360.png)
- **Profesora (Lucía):**
  - Escritorio (1366px): [`docs/06-pruebas/PRU-04/evidencia-t21/profesora-parcial-desktop.png`](evidencia-t21/profesora-parcial-desktop.png)
  - Celular (360px con marco): [`docs/06-pruebas/PRU-04/evidencia-t21/profesora-parcial-mobile-360.png`](evidencia-t21/profesora-parcial-mobile-360.png)

### Escenario 2: Todas las clases de hoy canceladas
- **Admin:**
  - Escritorio (1366px): [`docs/06-pruebas/PRU-04/evidencia-t21/admin-todas-canceladas-desktop.png`](evidencia-t21/admin-todas-canceladas-desktop.png)
  - Celular (360px con marco): [`docs/06-pruebas/PRU-04/evidencia-t21/admin-todas-canceladas-mobile-360.png`](evidencia-t21/admin-todas-canceladas-mobile-360.png)
- **Operativo (Sandra):**
  - Escritorio (1366px): [`docs/06-pruebas/PRU-04/evidencia-t21/operativo-todas-canceladas-desktop.png`](evidencia-t21/operativo-todas-canceladas-desktop.png)
  - Celular (360px con marco): [`docs/06-pruebas/PRU-04/evidencia-t21/operativo-todas-canceladas-mobile-360.png`](evidencia-t21/operativo-todas-canceladas-mobile-360.png)
- **Profesora (Lucía):**
  - Escritorio (1366px): [`docs/06-pruebas/PRU-04/evidencia-t21/profesora-todas-canceladas-desktop.png`](evidencia-t21/profesora-todas-canceladas-desktop.png)
  - Celular (360px con marco): [`docs/06-pruebas/PRU-04/evidencia-t21/profesora-todas-canceladas-mobile-360.png`](evidencia-t21/profesora-todas-canceladas-mobile-360.png)

---

## 4. Qué expresamente NO se probó

- **Cancelación desde la API REST:** Se encuentra apagada por diseño en `bootstrap/app.php`. No se probó.
- **Navegadores móviles físicos reales en dispositivos iOS/Android:** Se utilizó Chrome Headless sobre Windows en marco estricto de 360px (`iframe` con resolución real de teléfono). No se probó en hardware de celular físico en mano.
- **Acceso o cambios en el sitio de prueba ni su reloj simulado:** No se tocó el sitio de prueba ni su base de datos.
