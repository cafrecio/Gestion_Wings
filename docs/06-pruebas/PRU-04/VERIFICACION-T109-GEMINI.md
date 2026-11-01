# Verificación T109 — Filtros inválidos en la dirección y recibos inexistentes

> **Agente:** Gemini (Antigravity) · **Máquina:** CAB (`LOG GEM CAB`) · **Fecha:** 01/11/2026  
> **Tarea:** T109 (Etapa 3 de `docs/06-pruebas/PRU-04/PROMPT-GEMINI-T101-T105-CONTROL.md`)  
> **Commit bajo control:** `88db2b24` (implementado por Claude)  
> **Base de pruebas:** `wings_testing_gemini`  

---

## 1. Resumen ejecutivo

- **Estado:** **CERRADA SIN HALLAZGOS (Aprobada)**.
- **Resultado:** Se verificaron por ejecución real los 8 casos del prompt más la búsqueda exhaustiva de filtros compartidos en la interfaz y la prueba técnica masiva de seguridad (3.876 peticiones HTTP sobre 19 pantallas).
- **Comportamiento comprobado:**
  1. Filtros con basura en la URL (`desde`, `hasta`, `search`, `q`, `estado`, `fecha`, `mes`) son sanitizados o ignorados limpiamente por el middleware `IgnorarFiltrosInvalidos` y los controladores web, sin generar errores 500 ni ensuciar los campos del formulario.
  2. Recibos inexistentes o de liquidaciones no pagadas abortan con HTTP 404 y renderizan la vista web corporativa `errors.404` («Página no disponible»), sin exponer errores técnicos ni estructuras JSON.
  3. El rol PROFESOR es rechazado con HTTP 403 de forma estricta tanto para recibos existentes como inexistentes, garantizando que no se filtre la existencia o ausencia de cobros.
  4. Los parámetros válidos conservan el 100% de su comportamiento y funcionalidad de filtrado preexistente.
  5. Ningún formulario ni enlace de la aplicación utiliza estos nombres de filtros como lista/array ni con formatos alternativos incompatibles.

---

## 2. Matriz de Casos Verificados

| # | Caso | Resultado Esperado | Resultado Obtenido | Estado | Tipo |
|---|---|---|---|---|---|
| **1** | Historial: `desde` y `hasta` con texto (`abc`), fecha imposible (`2026-02-31`, `9999-99-99`), inyecciones (`1' OR '1'='1`) y caracteres raros | La pantalla abre (HTTP 200), sin el filtro y con el campo vacío en el formulario | Comprobado tanto para Dueño como para Operativa. La pantalla carga con HTTP 200, los registros no se restringen indebidamente y el HTML no contiene `value="<basura>"` | **OK** | Comprobado |
| **2** | Listados: `?search[]=x`, `?estado[]=x`, `?fecha[]=x`, `?q[]=x`, `?desde[]=x` | La pantalla abre sin error del servidor (HTTP < 500) | Comprobado en 14 pantallas y endpoints (`caja.historial`, `movimientos.index`, `cobranza.index`, `clases.index`, `alumnos.index`, `alumnos.autocomplete`, `caja.cobrar-cuota`, `profesores.index`, `revision-cobranza.index`, `liquidaciones.index`). Todos responden HTTP 200 (o 403 según rol) sin fallas 500 | **OK** | Comprobado |
| **3** | Caja del dueño: `?mes=abc`, `?mes=2026-13`, `?mes[]=x`, etc. | Abre en el mes actual | Comprobado. `CajaWebController::index` valida con regex `/^\d{4}-(0[1-9]|1[0-2])$/D` y reasigna `now()->format('Y-m')`. La vista recibe `mes` con el período actual | **OK** | Comprobado |
| **4** | Fechas, mes y búsquedas válidos, usando los filtros de las pantallas | Filtran igual que antes | Comprobado: Historial filtra y refleja `2026-03-01` y `2026-03-31`, Alumnos filtra y muestra coincidencia con `Valentina`, y Caja carga el mes solicitado `2026-03` | **OK** | Comprobado |
| **5** | Recibo inexistente (ID 999999 o texto), como operativa y como dueño | «Página no disponible» (HTTP 404 web) | Comprobado. Devuelve HTTP 404, `Content-Type: text/html`, título «Página no disponible – Wings» y mensaje amigable. No expone JSON técnico | **OK** | Comprobado |
| **6** | Recibo que existe e inexistente, como profesora | HTTP 403 en los dos casos | Comprobado. La verificación de rol en `ReciboController:44` (`$request->user()->isProfesor()`) precede a la consulta de existencia del pago (`abort_unless(Pago::whereKey...)`), impidiendo enumeración | **OK** | Comprobado |
| **7** | Liquidación inexistente o sin pagar, como dueño | «Página no disponible» (HTTP 404 web) | Comprobado. Inexistente devuelve HTTP 404 con «Página no disponible». Sin pagar (`estado_pago = PENDIENTE`) devuelve HTTP 404 con «Página no disponible» (antes devolvía JSON técnico HTTP 400) | **OK** | Comprobado |
| **8** | Recibo que existe, con `?inline=1` y descargándolo | Funciona igual | Comprobado para cuotas y liquidaciones. Con `inline=1` envía `Content-Disposition: inline`. Sin parámetro envía `Content-Disposition: attachment`. Ambos con binario `%PDF-` íntegro | **OK** | Comprobado |

---

## 3. Lo comprobado y lo inferido

### Comprobado por ejecución
- Ejecución de la prueba de seguridad masiva:
  - Archivo: [`docs/06-pruebas/PRU-04/prueba-tecnica/SeguridadDiasPruebaTest.php`](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/prueba-tecnica/SeguridadDiasPruebaTest.php) (`test_dias_41_47_filtros_con_basura_no_dan_error_del_servidor`).
  - Total: **3.876 peticiones HTTP** (19 pantallas × 17 parámetros × 6 valores inválidos × 2 roles).
  - Resultado: **0 errores del servidor** (0 peticiones con código >= 500). Tiempo de corrida: 107,47 s.
- Ejecución de la suite unitaria/funcional de T109:
  - Archivo: [`tests/Feature/FiltrosInvalidosT109Test.php`](file:///c:/xampp/htdocs/gestion-wings/tests/Feature/FiltrosInvalidosT109Test.php): 12 pruebas pasadas (83 aserciones) en 14,40 s.
- Ejecución de la suite de verificación exhaustiva propia de Gemini:
  - Archivo: [`docs/06-pruebas/PRU-04/evidencia-t109/VerificacionT109GeminiTest.php`](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-t109/VerificacionT109GeminiTest.php): 8 pruebas pasadas (162 aserciones) en 16,05 s.
- Revisión de código y vistas:
  - Relevamiento de todos los formularios `<form>` y campos `<input>` / `<select>` de `resources/views/**`.
  - Relevamiento de todos los scripts de `resources/js/**`.
  - Se confirmó que ningún componente o enlace del sistema genera ni espera arrays (`[]`) en `search`, `q`, `estado`, `fecha`, `desde` o `hasta`. Todos son cadenas escalares.

### Inferido
- No se infirió ningún aspecto de seguridad: todos los casos fueron ejecutados contra el motor HTTP y la base de datos `wings_testing_gemini`.

---

## 4. Hallazgos

- **Ninguno (0 graves, 0 medios, 0 leves).**
- La implementación es limpia, modular y no introduce regresiones ni alteraciones en el diseño.

---

## 5. Pruebas y Suite Técnica

- **Pruebas de la tarea:**
  - `tests/Feature/FiltrosInvalidosT109Test.php`: 12 pasadas.
  - `docs/06-pruebas/PRU-04/evidencia-t109/VerificacionT109GeminiTest.php`: 8 pasadas (162 aserciones).
  - `docs/06-pruebas/PRU-04/prueba-tecnica/SeguridadDiasPruebaTest.php` (`test_dias_41_47_filtros_con_basura_no_dan_error_del_servidor`): 1 pasada (3.876 requests evaluadas, 0 errores 500).
- **Suite rápida de referencia:**
  - `DB_DATABASE=wings_testing_gemini php artisan test --exclude-group=lento`: 1335 pasadas, 6 omitidas, 9782 aserciones en 451 s.
