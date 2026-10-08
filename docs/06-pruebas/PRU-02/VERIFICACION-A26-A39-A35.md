# Informe de Verificación: A26, A39 y A35

- **Fecha:** 08/10/2026
- **Verificador:** Gemini (Antigravity)
- **Firma en bitácora:** `LOG GEM CYE`
- **Commit analizado:** `7122210` (que incorpora el trabajo de Claude en `975fae3` y la corrección `d664c35`)
- **Base de datos empleada:** `wings_testing_gemini` (aislada y descartable)
- **Suite de pruebas:**
  - `tests/Feature/DefectosMenoresA26A44Test.php`: 5 passed (16 assertions)
  - `tests/Feature/MenuYMovimientosA38A39Test.php`: 4 passed (24 assertions)
  - `docs/06-pruebas/PRU-02/evidencia/verificacion-a26-a39-a35/VerificacionA26A39A35Test.php`: 3 passed (73 assertions)

---

## 1. Defecto A26 — Celular de un Menor

### 1.1 Regla y alcance
- **Regla:** Para un menor de 18 años, el campo `celular` es opcional en alta y edición; si queda vacío, se completa automáticamente con el valor de `telefono_tutor`. Para un mayor (18 años o más), el campo `celular` es estrictamente obligatorio.
- **Implementación verificada:**
  - `app/Http/Controllers/AlumnoWebController.php`: métodos `validationRules()`, `validationMessages()`, `completarCelular()`, `store()` y `update()`.
  - `resources/js/alumnos-form.js`: función `esMenor()`, control de visibilidad de `#celular-obligatorio`, sincronización del checkbox `#celular-mismo-tutor`.

### 1.2 Casos de prueba ejecutados y comprobación en Base de Datos

| # | Caso / Escenario | Datos cargados (Payload / Navegador) | Respuesta del Servidor / UI | Estado en Base de Datos (`alumnos`) | Resultado |
|---|---|---|---|---|---|
| 1 | Alta menor sin celular | Mateo González, DNI 50111222, F.Nac: 10 años atrás (`2016-10-08`), `celular: ''`, `nombre_tutor: 'María Pérez'`, `telefono_tutor: '11-4444-5555'` | HTTP 302 (Redirect `web.alumnos.index`), sin errores de validación | Fila creada. `celular = '11-4444-5555'`, `telefono_tutor = '11-4444-5555'` | **APROBADO** |
| 2 | Alta menor con celular propio | Joaquín López, DNI 50222333, F.Nac: 12 años atrás (`2014-10-08`), `celular: '11-9999-8888'`, `nombre_tutor: 'Carlos López'`, `telefono_tutor: '11-3333-2222'` | HTTP 302 (Redirect `web.alumnos.index`), sin errores | Fila creada. Conserva `celular = '11-9999-8888'`, `telefono_tutor = '11-3333-2222'` | **APROBADO** |
| 3 | Alta mayor sin celular | Esteban Suárez, DNI 38111222, F.Nac: 25 años atrás (`2001-10-08`), `celular: ''`, sin datos de tutor | HTTP 302 (Redirect back con errores de sesión). Error: *"El celular es obligatorio."* | No se inserta ningún registro en la base de datos | **APROBADO** |
| 4 | Edición menor vaciando celular | Alumno creado con `celular: '11-1111-2222'` y `telefono_tutor: '11-3333-2222'`. PUT con `celular: ''` | HTTP 302 (Redirect `web.alumnos.index`), sin errores | Registro actualizado. `celular = '11-3333-2222'` (heredó tutor) | **APROBADO** |
| 5a | Borde: Cumple 18 **hoy** (`2008-10-08`) | DNI 50888001, F.Nac: `2008-10-08`, `celular: ''`, `telefono_tutor: '11-7777-6666'` | HTTP 302 back con error: *"El celular es obligatorio."*. Servidor calcula `diffInYears = 18` (mayor) | No se crea alumno. Servidor y UI coinciden (exigen celular) | **APROBADO** |
| 5b | Borde: Cumple 18 **mañana** (`2008-10-09`) | DNI 50888002, F.Nac: `2008-10-09`, `celular: ''`, `telefono_tutor: '11-7777-6666'` | HTTP 302 (Redirect `web.alumnos.index`), sin error. Servidor calcula `diffInYears = 17` (menor) | Fila creada. `celular = '11-7777-6666'` (heredó tutor) | **APROBADO** |
| 6 | Menor sin celular Y sin teléfono de tutor | Tomás Blanco, DNI 51000001, F.Nac: 10 años atrás, `celular: ''`, `telefono_tutor: ''` | HTTP 302 back con error: *"El teléfono del tutor es obligatorio para menores de edad."*. **Nunca error 500.** | No se crea alumno | **APROBADO** |
| 7 | Asterisco en vivo (Chrome Headless CDP) | Carga inicial sin fecha / con mayor (`2001-05-10`) vs ingreso de fecha menor (`2016-05-10`) | Al poner menor: `#celular-obligatorio.hidden = true` (`display: none`). Al volver a mayor: `#celular-obligatorio.hidden = false` (`display: block`) | Verificado en DOM real con eventos `input`/`change` disparados en vivo | **APROBADO** |
| 8 | Tilde "Mismo que el teléfono del tutor" | Escribir `'11-8888-9999'` en `#telefono_tutor` y activar checkbox `#celular-mismo-tutor` | Input `#celular` se actualiza de inmediato con `'11-8888-9999'` | Verificado en navegador | **APROBADO** |

### 1.3 Observación de importación Excel (Fuera de alcance)
- La importación masiva por Excel (`app/Services/ImportacionAlumnosService.php`) es de carga inicial y procesa alumnos existentes. El servicio no fuerza completar celular de menores si viene en blanco, ni valida la obligatoriedad del tutor del mismo modo que el alta interactiva web. Esto es consistente con una importación masiva de legajos históricos.

### 1.4 No verificado
- No se verificaron dispositivos móviles físicos iOS/Android (se verificó con emulación en Chrome Headless oficial vía CDP sobre servidor local y base de pruebas).
- Rutas de API REST de alumnos (`/api/v1/alumnos`), dado que la API está deshabilitada intencionalmente en `bootstrap/app.php`.

### 1.5 Dictamen A26
**CERRADO.** Cumple rigurosamente la regla de negocio y frontend dinámico.

---

## 2. Defecto A39 — Operativo y Movimientos

### 2.1 Regla y alcance
- **Regla de Carlos:** *"Que esté Movimientos en el menú. Solo puede ver los que corresponden a rubros del operativo. Nunca sueldos, alquileres, ni nada que el admin ponga solo para él."*
- **Implementación verificada:**
  - `resources/views/layouts/ds-app.blade.php`: ítem de menú *Movimientos* bajo grupo *Plata*, visible tanto para ADMIN como para OPERATIVO.
  - `app/Http/Controllers/MovimientoWebController.php`:
    - En `index()`: si el usuario es `OPERATIVO`, filtra la consulta principal mediante `whereHas('subrubro', fn($q) => $q->where('permitido_para', 'OPERATIVO'))`.
    - Dropdowns de filtro: `Rubro` filtra sólo aquellos que tienen al menos un subrubro con `permitido_para = 'OPERATIVO'`. `Subrubro` filtra estrictamente por `permitido_para = 'OPERATIVO'`.
    - Totales: `$totalIngresos` y `$totalEgresos` calculan sobre el query filtrado, excluyendo absolutamente cualquier movimiento de admin.
    - Bloqueo en consulta por GET directo: si el usuario operativo manipula la URL con `?rubro_id=X` o `?subrubro_id=Y` correspondientes a rubros/subrubros de ADMIN, la consulta no arroja resultados ni suma sus importes.
    - Rol PROFESOR: middleware y autorización rechazan el acceso con HTTP 403.

### 2.2 Casos de prueba y comprobación en el sistema andando (Usuario Sandra - OPERATIVO)

| # | Caso / Escenario | Acción / URL | Respuesta / Pantalla | Verificación de Datos | Resultado |
|---|---|---|---|---|---|
| 1 | Menú y acceso | Clic / navegación a `/movimientos` como Sandra | HTTP 200. Pantalla abre correctamente. Ítem "Movimientos" presente en el menú de navegación. | Menú contiene enlace funcional a `/movimientos`. | **APROBADO** |
| 2 | Movimiento propio y del compañero | Carga de Sandra (Cuotas Gimnasia $15.000) y de Marcos (Librería $7.500) | Ambos movimientos se listan en la tabla con sus fechas, medios, importes y observaciones. | Sandra ve su propio movimiento y el de su compañero Marcos. | **APROBADO** |
| 3 | Bloqueo de movimientos de Admin | Movimientos cargados por Admin: Sueldos Profesores ($250.000) y Alquiler Predio ($50.000) | **No aparecen en la tabla.** Cero filas correspondientes a sueldos o alquileres. | Se corroboró que ninguna fila ni concepto de admin es transmitido al HTML. | **APROBADO** |
| 4 | Totales de ingresos, egresos y neto | Indicador `.stats-info` en la cabecera de la lista | Texto mostrado: `"3 movimientos · Ingresos $15.000 · Egresos $10.500 · Neto $4.500"` | Cálculo exacto: Ingresos: $15.000. Egresos: $7.500 (Marcos) + $3.000 (Sandra en rubro mixto) = $10.500. Neto: $4.500. Cero pesos de los $312.000 de egresos del Admin. | **APROBADO** |
| 5 | Desplegables de filtros | Inspección del elemento `select[name="rubro_id"]` y `select[name="subrubro_id"]` | **Rubros visibles para Sandra:** *Cobranzas, Cuotas, Gastos Mostrador, Gastos Operativos, Indumentaria, Inscripciones, Mantenimiento General, Torneos*. **Ausentes:** *Sueldos Profesores, Alquiler Cancha, Intereses*. | Los catálogos reservados para ADMIN no se exponen al OPERATIVO en ningún selector. | **APROBADO** |
| 6 | Ataque por GET directo | Petición con query string: `/movimientos?subrubro_id=[ID_SUELDO_ADMIN]` y `/movimientos?rubro_id=[ID_ALQUILER_ADMIN]` | Devuelve tabla vacía (0 movimientos) y totales en $0. | La cláusula de seguridad en backend anula cualquier filtrado fraudulento por ID directo. | **APROBADO** |
| 7 | Rubro mixto con subrubros de ambos | Rubro *Mantenimiento General* con subrubro Admin (*Reparación Estructural*) y subrubro Operativo (*Pintura Menor*) | Sandra ve el rubro en el selector y ve únicamente el movimiento del subrubro operativo ($3.000). El movimiento del subrubro de admin ($12.000) queda completamente oculto. | Comportamiento exacto según la regla de negocio. | **APROBADO** |
| 8 | Rol ADMIN | Acceso como Carlos Admin | Admin ve los 6 movimientos en la tabla y los egresos totales acumulan $307.500. | Admin tiene visión total. | **APROBADO** |
| 9 | Rol PROFESOR | Carga de `/movimientos` como Laura Gómez | HTTP 403 Forbidden. No existe ítem en menú. | Acceso bloqueado según matriz de permisos. | **APROBADO** |

### 2.3 No verificado
- Paginaciones de más de 25 páginas con cientos de miles de registros (se probó paginación funcional del framework hasta 2 páginas).

### 2.4 Dictamen A39
**CERRADO.** La visibilidad del módulo de Movimientos para el rol operativo respeta de forma estricta los límites de rubros/subrubros y resguarda la privacidad financiera reservada al administrador.

---

## 3. Defecto A35 — Confirmación de No-Defecto (Botón Historial en Caja)

### 3.1 Planteo original y hallazgo
- El relevamiento PRU-02 indicaba que en el historial de cajas existía un botón "Historial" redundante que apuntaba a la misma pantalla.
- Claude identificó en el commit `975fae3` que la captura original correspondía a la pantalla principal de `/caja` (donde el botón "Historial" lleva a `/caja/historial`), y no a `/caja/historial`.

### 3.2 Auditoría exhaustiva de botones y enlaces en `/caja` y `/caja/historial`
Se auditaron todas las rutas y destinos mediante la suite automatizada (`resultados-a35.json`):

#### A. En pantalla `/caja` (Caja principal)
- **Rol ADMIN:**
  - `Wings` -> `/`
  - Enlaces de barra de navegación: Dashboard, Alumnos, Clases, Cobranza, Caja, Movimientos, Cashflow, Liquidaciones, Revisión, Deportes, Niveles, Grupos, Profesores, Rubros, Tipos de Caja, Configuración, Usuarios.
  - Botones de acción en página:
    - *Limpiar* -> `/caja` (resetea formulario de filtros de caja)
    - *Configurar* -> `/caja/configuracion`
    - **Historial** -> `/caja/historial` (navega a la pantalla de Historial)
    - *Cobrar* -> `/caja/cobrar`
    - *Agregar* -> `/caja/{id}/editar`
    - *Resumen* -> `/caja/{id}/resumen`
    - *Detalle* -> `/caja/{id}/detalle`
- **Rol OPERATIVO:**
  - Enlaces de barra: Inicio, Alumnos, Clases, Cobranza, Caja, Movimientos, Revisión, Grupos.
  - Botones de acción en página:
    - **Historial** -> `/caja/historial` (navega al Historial)
    - *Cobrar* -> `/caja/cobrar`
    - *Agregar* -> `/caja/{id}/editar`
    - *Resumen* -> `/caja/{id}/resumen`
    - *Detalle* -> `/caja/{id}/detalle`

#### B. En pantalla `/caja/historial` (Historial de cajas)
- **Rol ADMIN y OPERATIVO:**
  - Botones de acción en página:
    - *Limpiar* -> `/caja/historial` (resetea los filtros de búsqueda en historial)
    - **Volver** -> `/caja` (retorna a la pantalla principal de caja)
    - *Filtrar* -> submit de formulario GET

### 3.3 Dictamen A35
**NO ES DEFECTO (CERRADO).**
No existe ningún botón rotulado "Historial" en `/caja/historial` que recargue o lleve a la misma pantalla. El botón "Historial" vive en `/caja` y conduce correctamente a `/caja/historial`, mientras que en `/caja/historial` el botón de navegación se llama "Volver" y conduce a `/caja`. Queda confirmado que el reporte se originó en una captura mal rotulada.

---

## 4. Evidencias Generadas

Toda la evidencia y scripts de prueba interactiva se encuentran archivados en:
`docs/06-pruebas/PRU-02/evidencia/verificacion-a26-a39-a35/`

- `VerificacionA26A39A35Test.php`: suite completa de 3 tests de integración con base de datos real.
- `sembrar_escenario.php`: script PHP para reproducir de forma determinística usuarios, cajas y movimientos.
- `verificar_navegador.mjs`: script interactivo que maneja Chrome Headless vía CDP para validar DOM en vivo.
- `a26-asterisco-menor.png`: captura de pantalla de `/alumnos/create` tras ingresar fecha de menor (asterisco ausente).
- `a26-asterisco-mayor.png`: captura de pantalla tras cambiar a fecha de mayor (asterisco presente).
- `a39-sandra-movimientos.png`: captura de la pantalla `/movimientos` vista por Sandra, mostrando la tabla limpia de rubros de admin y los totales correctos.
- `resultados-a26.json`: volcado detallado de los 6 casos de prueba de A26.
- `resultados-a39.json`: volcado de datos y verificación de catálogos y ataques GET de A39.
- `resultados-a35.json`: inventario completo de todos los botones y sus destinos en `/caja` y `/caja/historial`.
- `resultados-navegador.json`: mediciones extraídas en vivo durante la sesión interactiva con Chrome Headless.
- `salida-tests-claude.txt`: salida de la corrida de los tests de Claude (`MenuYMovimientosA38A39Test` y `DefectosMenoresA26A44Test`).
