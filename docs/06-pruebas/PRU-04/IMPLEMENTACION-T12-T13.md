# Implementación T12 y T13 — Etapa A (Operativa, páginas 2 a 20) y Buscador

**Gemini CAB, 29/10/2026**
**Firma:** LOG GEM CAB

Continuación del trabajo conforme al plan de 3 etapas (`PROMPT-GEMINI-T12-T13-ETAPAS.md`):
- **T12:** Manual del usuario: contenido por tarea y por rol.
- **T13:** Ayuda dentro de Wings: entrada en el menú, buscador de ayuda y signo de pregunta en cada pantalla.

---

## 0. Paso 0: Depuración de la nómina de páginas contra el código fuente

Siguiendo la instrucción de Carlos y Claude de no escribir guías sobre funciones inexistentes, se auditó exhaustivamente `T12-LISTA-DE-PAGINAS.md` contra las rutas, controladores y modelos reales de Wings:

| Página | Título anterior | Ajuste realizado | Evidencia en código (`archivo:línea`) |
|---|---|---|---|
| **Pág. 7** | Dar de baja a un alumno | Reemplazado por **Pasar a un alumno a Inactivo**. Separado estrictamente de T65 (eliminación permanente). | `app/Http/Controllers/AlumnoWebController.php:428-460` (método `toggleActivo`); vista `resources/views/alumnos/show.blade.php:59-63`. T65 en `AlumnoWebController.php:465-515`. |
| **Pág. 9** | Condonar una cuota o intereses | Reemplazado por **Consultar el registro de movimientos**. Condonación es exclusiva de ADMIN. | En rol operativo no existe condonar (`routes/web.php:119-120, 148-153` exige `ensure.admin.web`). Se describe `/movimientos` (`app/Http/Controllers/MovimientoWebController.php:14`). |
| **Pág. 11** | Registrar un cobro manual en caja | Reemplazado por **Registrar un ingreso directo en el turno de caja**. | `app/Http/Controllers/CajaWebController.php:446-511` (`guardarMovimiento`), `resources/views/caja/movimiento.blade.php:43-52`. |
| **Pág. 13** | Anular un cobro o gasto mal cargado | Título formal: **Cancelar un movimiento en una caja abierta o rechazada**. | Botón **Cancelar** en `resources/views/caja/detalle.blade.php:180-184`; acción `cancelarMovimiento` en `app/Http/Controllers/CajaWebController.php:601-638`. |
| **Pág. 20 y 42** | Consultar grupos y cupos / Administrar grupos | Se eliminaron referencias a "cupos" y "rango de edades". | `app/Models/Grupo.php:11-15` `$fillable`: solo `deporte_id`, `nivel_id`, `activo`. No existen columnas ni lógica de cupo o edad. |
| **Pág. 34** | Liquidaciones: adelantos | Se eliminaron menciones a "adelantos". | En `routes/web.php:168-175` y `app/Http/Controllers/LiquidacionWebController.php` solo existen acciones de `recalcular` y `ajustar`. |
| **Pág. 39** | Exportación de reporte de sueldos a Excel/CSV | Se eliminó exportación a archivos. | En `routes/web.php:180-186` y `app/Http/Controllers/ReporteWebController.php:97-150` no existe ruta ni método de exportación a Excel/CSV. |
| **Pág. 40** | Administrar deportes (colores) | Se eliminó mención al campo de color. | `app/Models/Deporte.php:11-15` `$fillable`: solo `nombre`, `tipo_liquidacion`, `activo`. Los colores son rieles fijos en el CSS. |
| **Pág. 50** | Consultar clases (sedes) | Se eliminaron menciones a "sedes". | `app/Models/Clase.php:11-18`: campos `fecha`, `hora_inicio`, `deporte_id`, `grupo_id`, `profesor_id`. No existe entidad ni columna sede. |

### Ajustes en las páginas de muestra (Devolución de Claude)
1. `resources/manual/operativo/cobrar-cuota.md`: Se clarificó que al navegar desde **Cobranza**, el botón **Cobrar** de la tarjeta del alumno dirige directamente a la pantalla de cobro de dicho alumno (`/caja/cobrar/{alumnoId}`), sin pasar por el buscador general (`resources/views/cobranza/index.blade.php:191-197`).
2. `resources/manual/admin/corregir-movimiento-caja-validada.md`: Se detalló que al seleccionar una fecha de un mes anterior en Cashflow se despliega la advertencia `Atención: fecha de un mes anterior` y se requiere pulsar el botón **Confirmar** (`resources/views/cashflow/movimiento.blade.php:80-92`, `app/Http/Controllers/CashflowWebController.php:68-75`).

---

## 1. Arquitectura y almacenamiento del contenido

### ¿Dónde vive el contenido?
Los textos del manual se almacenan en archivos **Markdown independientes** fuera del código Blade de las vistas, ubicados en:
```text
resources/manual/{rol}/{slug}.md
```
Roles de carpetas:
- `resources/manual/operativo/`: Tareas propias del mostrador y atención diaria.
- `resources/manual/admin/`: Tareas de administración, cashflow, validación, catálogos y primera carga.
- `resources/manual/profesor/`: Tareas de profesores (clases y asistencia).

Esta separación permite:
1. Agregar o corregir páginas del manual simplemente creando o editando archivos Markdown, sin necesidad de programar vistas ni tocar controladores.
2. Mantener los textos en un formato legible, versionable y seguro.

### Metadatos y Frontmatter
Cada archivo `.md` incluye un bloque de cabecera delimitado por `---` con los siguientes datos:
- `titulo`: Nombre formal de la tarea (ej. *Cobrar una cuota*).
- `rol`: `operativo`, `admin` o `profesor`.
- `categoria`: Nombre del grupo temático (ej. *Caja y cobranza*, *Cashflow y finanzas*, *Clases y asistencia*).
- `orden`: Número de orden relativo para presentación en el índice.
- `resumen`: Descripción concisa en una o dos oraciones de qué resuelve la tarea.
- `du`: Código de duda o caso de prueba asociado (ej. *DU-27*, *DU-29*, *DU-20*).

### Servicio `ManualService`
El servicio `App\Services\ManualService` se encarga de:
- Escanear y listar las guías disponibles según el rol del usuario conectado:
  - **ADMIN:** Ve todas las páginas (Admin, Operativo y Profesor). El dueño nunca tiene menos acceso que la operativa.
  - **OPERATIVO:** Ve únicamente las páginas del rol operativo.
  - **PROFESOR:** Ve únicamente las páginas del rol profesor.
- `buscarPagina(string $slug, User $user)`: Localiza el archivo por su slug. Si el archivo existe pero pertenece a un rol que el usuario no tiene permitido (por ejemplo, una operativa intentando acceder a una guía de admin), interrumpe con `HTTP 403 Forbidden`. Si el slug no existe, devuelve `HTTP 404 Not Found`.
- Convierte el cuerpo Markdown a HTML mediante el componente estándar de Laravel (`Illuminate\Support\Str::markdown`).

---

## 2. Navegación, Rutas y Vistas

### Rutas registradas (dentro del middleware `auth`):
- `GET /ayuda`: `web.ayuda.index` &rarr; `AyudaWebController@index`.
- `GET /ayuda/img/{rol}/{archivo}`: `web.ayuda.imagen` &rarr; `AyudaWebController@imagen`.
- `GET /ayuda/{slug}`: `web.ayuda.show` &rarr; `AyudaWebController@show`.

### Servicio autenticado de imágenes del manual
Para que las imágenes se visualicen correctamente dentro de Wings tanto en entorno local como en servidor y no se puedan acceder sin sesión:
1. Las imágenes físicas se almacenan en `resources/manual/img/{rol}/{archivo}`, fuera del directorio público.
2. La ruta `/ayuda/img/{rol}/{archivo}` está protegida por el middleware `auth` y valida los roles mediante `ManualService::rolesPermitidos`:
   - Admin puede ver imágenes de cualquier rol.
   - Operativo solo puede ver imágenes de `operativo` (intento sobre `admin` o `profesor` da HTTP 403).
   - Profesor solo puede ver imágenes de `profesor` (intento sobre `admin` u `operativo` da HTTP 403).
   - Usuarios sin sesión son redirigidos a `/login`.
   - Archivos inexistentes devuelven HTTP 404.
   - Nombres con caracteres sospechosos o intentos de *directory traversal* son rechazados con 404.
3. En los archivos Markdown del manual, las imágenes se referencian directamente como `/ayuda/img/{rol}/{archivo}`, cargándose de forma nativa e integrada en las vistas de Wings.

### Entrada en el Menú lateral (`layouts/ds-app.blade.php`)
- En el grupo **Sistema** para el rol ADMIN (debajo de Usuarios).
- En el grupo **Sistema** para el rol OPERATIVO (debajo de Grupos).
- En la barra de navegación lateral para el rol PROFESOR (debajo de Clases).

### Botón de ayuda contextual (?)
- Se implementó en la barra superior (`ds-topbar`), visible en todas las resoluciones (escritorio 1366 px y celular 375 px) junto al botón de Salir.
- En la Etapa 1, se habilitó exclusivamente en las dos pantallas de muestra mediante `@section('ayuda-slug', '...')`:
  - En `/caja/cobrar/{alumnoId}` apunta a `route('web.ayuda.show', 'cobrar-cuota')`.
  - En `/cashflow` apunta a `route('web.ayuda.show', 'corregir-movimiento-caja-validada')`.
- Las demás pantallas no declaran `@section('ayuda-slug')` y por lo tanto no muestran el botón en esta etapa.

### Función de impresión (`window.print()`)
- La vista `resources/views/ayuda/show.blade.php` cuenta con un botón **Imprimir**.
- **Sin JavaScript incrustado:** Cumpliendo estrictamente con AGENTS.md §5-bis, el botón utiliza el atributo `data-accion="imprimir"`, el cual es interceptado por un manejador global en `resources/js/ds-app.js`.
- Estilos `@media print`: Ocultan automáticamente el sidebar lateral (`.ds-sidebar`), el overlay móvil, la barra superior (`.ds-topbar`), el encabezado de módulo y la botonera de acciones, presentando un documento con márgenes limpios y cabecera impresa.

---

## 3. Las dos páginas de muestra desarrolladas

### Muestra 1: «Cobrar una cuota» (Operativa)
- **Archivo:** `resources/manual/operativo/cobrar-cuota.md`
- **Contenido:**
  - Explicación de la tarea y requisito previo de caja abierta.
  - Paso 1: Búsqueda del alumno por nombre o DNI.
  - Paso 2: Selección de cuotas adeudadas, matrícula o punitorios.
  - Paso 3: Selección del medio de pago (Efectivo, Transferencia, Tarjeta).
  - Paso 4: Confirmación del cobro y emisión del recibo oficial.
  - Apartado **«Qué hago si…»**: Pagos parciales, cobros erróneos en caja abierta vs caja validada (DU-27), transferencias no acreditadas y cobros sin caja abierta.

### Muestra 2: «Corregir un movimiento mal cargado en una caja ya validada» (Dueño - DU-27)
- **Archivo:** `resources/manual/admin/corregir-movimiento-caja-validada.md`
- **Contenido:**
  - **Explicación de la regla contable:** Detalle claro de por qué una caja validada nunca se reabre ni se edita (es el arqueo físico cerrado y rendido de ese turno; reabrirla destruiría la trazabilidad de la rendición del empleado y la correspondencia con el efectivo contado).
  - Explicación de la solución: Carga de un **ajuste directo en Cashflow** con motivo obligatorio.
  - Paso 1: Ingreso al libro Cashflow y verificación de saldos por medio.
  - Paso 2: Apertura del formulario de Nuevo movimiento.
  - Paso 3: Carga del movimiento compensatorio (Ingreso o Egreso según el error, tipo de caja, importe exacto, rubro y motivo descriptivo).
  - Paso 4: Guardado y verificación del balance general auditado.
  - Apartado **«Qué hago si…»**: Casos de cajas aún no validadas (rechazo para rectificación por la operativa), deudas de alumnos afectadas y diferenciación con transferencias bancarias (DU-29).

---

## 4. Nómina propuesta de páginas del manual

Se redactó y presentó la nómina completa en `docs/06-pruebas/PRU-04/T12-LISTA-DE-PAGINAS.md`, con **54 páginas propuestas** organizadas por rol:
- **Operativo (20 páginas):** Cubriendo alumnos, inscripciones, cobranzas, manejo de caja, egresos de caja menor [DU-20], cierres, arqueos, rectificaciones y asistencias.
- **Admin (29 páginas):** Cubriendo el capítulo completo de Primera Carga (4 páginas), validación de cajas, libro Cashflow, ajustes por corrección [DU-27], transferencias entre medios propios [DU-29], regla de uso del dinero del dueño [DU-20], liquidaciones de profesores, reportes visuales y analíticos, deportes, grupos, niveles, rubros, tipos de caja y usuarios.
- **Profesor (5 páginas):** Cubriendo consulta de clases programadas, marcado de asistencia, modificación de lista y detección de clases sin asistencia.

---

## 5. Pruebas automatizadas de permisos y funcionamiento

Se implementó la suite `tests/Feature/AyudaManualT12T13Test.php` con **17 pruebas y 79 aserciones**, todas en verde en `wings_testing_gemini`:
1. `test_invitado_sin_sesion_es_redirigido_a_login`: Redirección a `/login` para rutas `/ayuda` y `/ayuda/{slug}`.
2. `test_admin_ve_todas_las_secciones_en_el_indice_de_ayuda`: Admin ve todas las guías en `/ayuda`.
3. `test_operativo_ve_solo_sus_guias_en_el_indice`: Operativa no ve guías de admin ni de profesor.
4. `test_profesor_ve_solo_sus_guias_en_el_indice`: Profesor no ve guías de admin ni de operativa.
5. `test_admin_puede_abrir_cualquier_pagina_del_manual`: Acceso libre para el dueño.
6. `test_operativo_puede_ver_guia_operativa_pero_recibe_403_al_forzar_guia_de_admin_o_profesor`: Bloqueo con HTTP 403 ante URL forzada.
7. `test_profesor_puede_ver_su_guia_pero_recibe_403_al_forzar_guia_de_admin_u_operativo`: Bloqueo con HTTP 403 ante URL forzada.
8. `test_pagina_inexistente_devuelve_404`: HTTP 404 si el slug no existe.
9. `test_menu_lateral_muestra_acceso_a_ayuda_para_cada_rol`: Enlace presente en layout para los 3 roles.
10. `test_boton_de_ayuda_aparece_en_pantallas_de_muestra_y_no_en_otras`: Botón presente en `/caja/cobrar/{id}` y `/cashflow`, y ausente en otras vistas.
11. `test_pagina_de_ayuda_tiene_botones_volver_e_imprimir`: Presencia de botones canónicos y atributo para impresión.
12. `test_admin_puede_ver_todas_las_imagenes_de_las_muestras`: El admin accede a cada una de las 8 imágenes de muestra recibiendo HTTP 200 y `Content-Type: image/png`.
13. `test_operativo_puede_ver_imagenes_operativas_pero_recibe_403_en_imagenes_admin`: La operativa accede a sus capturas pero recibe HTTP 403 si intenta ver capturas de administración.
14. `test_profesor_recibe_403_al_pedir_imagenes_de_admin_u_operativo`: Los profesores reciben HTTP 403 si intentan pedir imágenes de admin u operativo.
15. `test_invitado_sin_sesion_al_pedir_imagen_es_redirigido_a_login`: Los usuarios sin sesión son redirigidos al ingreso.
16. `test_imagen_inexistente_devuelve_404`: Solicitudes a imágenes que no existen devuelven HTTP 404.
17. `test_paginas_de_ayuda_renderizan_imagenes_con_rutas_de_la_aplicacion`: Verifica que el HTML generado por el manual use rutas nativas `/ayuda/img/{rol}/...`.

---

## 6. Evidencia y galería de aprobación

- **Galería visual:** `docs/06-pruebas/PRU-04/APROBAR-T12-T13-MUESTRAS.html`
- **Carpeta de capturas:** `docs/06-pruebas/PRU-04/evidencia-t12-t13/`
- **Ubicación interna servida en Wings:** `resources/manual/img/operativo/` y `resources/manual/img/admin/`
- Todas las capturas fueron tomadas de la aplicación real mediante Chrome headless (`C:/Program Files/Google/Chrome/Application/chrome.exe`), con dimensiones de 1366 px para escritorio y dentro de marco iframe para móvil de 375 px (respetando la regla de Windows para pantallas móviles de AGENTS.md).
- **Verificación de huellas MD5:**
  - `cobrar-cuota-paso1.png` (`a6af9d40...`), `paso2.png` (`9b39ee45...`), `paso3.png` (`1849c7ea...`), `paso4.png` (`29ffd725...`) &rarr; 4 huellas distintas.
  - `corregir-movimiento-paso1.png` (`56ecd764...`), `paso2.png` (`b5a15df3...`), `paso3.png` (`e2fda4a1...`), `paso4.png` (`e42a42fe...`) &rarr; 4 huellas distintas.

---

## 7. Huecos detectados en Wings para decisión de Carlos

Durante la verificación minuciosa del manual contra el código real y las pruebas en el sistema, se comprobó una limitación funcional concreta en Wings:

### Reversión de cobros por caja validada y deuda del alumno
- **Situación:** En un turno de caja de una operativa se cobra una cuota por error a un alumno (por ejemplo, cobro duplicado o asignado a otro alumno). El turno finaliza y el dueño valida la caja.
- **Lo que pasa en el dinero:** Siguiendo la regla contable DU-27, el dueño realiza un ajuste compensatorio directo en **Cashflow** (un EGRESO compensatorio por la diferencia), lo que equilibra los saldos monetarios del club sin tocar la caja cerrada.
- **Lo que pasa en la cuenta del alumno (el hueco):** Hoy Wings no tiene ninguna vía para devolverle la deuda al alumno:
  1. Desde la ficha del alumno, intentar anular el cobro es rechazado por `PagoCuotaService::anularCobro` (`PagoCuotaService.php:1266`): *«Este cobro se hizo por caja: se anula desde el detalle de esa caja»*.
  2. Desde la caja, intentar cancelarlo es rechazado por `CajaWebController::cancelarMovimiento` (`CajaWebController.php:601`), ya que solo admite estados `ABIERTA` o `RECHAZADA`.
  3. Intentar reactivar la cuota desde la cuenta corriente es rechazado por `PagoCuotaService::reactivarCuota` (`PagoCuotaService.php:380`), que exige estrictamente: *«Solo se pueden reactivar cuotas CONDONADAS»*.
- **Consecuencia actual:** El dinero queda compensado por Cashflow, pero en el sistema la cuota del alumno permanece indebidamente como saldada (pagada), sin posibilidad de reabrir la deuda.
- **Tratamiento en el manual:** Se explicó con total transparencia la regla en `resources/manual/admin/corregir-movimiento-caja-validada.md` (apartado «¿Qué hago si el error afectó la deuda de un alumno?»), indicando que hoy Wings no permite reabrir la deuda del alumno tras validar la caja. Queda este reporte para que Carlos defina si a futuro se diseña un mecanismo administrativo especial de desvinculación o reapertura de deuda.

---

## 8. Corrección de la Etapa 1 y método obligatorio aplicado

Conforme a la devolución de Carlos y la revisión de Claude:
1. **Método de ejecución previa:** Cada paso y cada alternativa de «Qué hago si…» fue ejecutado en el sistema sobre entorno local (`wings_manual_gemini` / `wings_testing_gemini`) con usuarios de cada rol y datos sintéticos.
2. **Textos literales sin improvisaciones:** Las negritas corresponden con exactitud a botones, campos, títulos y opciones de pantalla. Los mensajes entre comillas latinas «» corresponden a cadenas literales emitidas por controladores o servicios de Wings.
3. **Fichas de fuentes:** Se elaboraron las 3 fichas de correspondencia en `docs/06-pruebas/PRU-04/evidencia-t12-t13/fuentes/`:
   - `operativo-cobrar-cuota.md`
   - `admin-corregir-movimiento-caja-validada.md`
   - `profesor-tomar-asistencia.md`
   con trazabilidad completa de afirmación, `archivo:linea` y resultado de ejecución.
4. **Muestra 3 incorporada:** Se sumó `resources/manual/profesor/tomar-asistencia.md` con 3 pasos reales y sus capturas con huellas MD5 100% únicas.
5. **Verificación automática:** `php scripts/manual/verificar-manual.php` ejecutado con éxito: **3 páginas revisadas, 0 problemas**.
6. **Revisión independiente:** Informe en `docs/06-pruebas/PRU-04/evidencia-t12-t13/revisiones/2026-10-28-etapa1.md` con 0 hallazgos graves y 0 medios abiertos.

---

## 10. Entrega de la Etapa A (Operativa, páginas 2 a 20) y Buscador de Ayuda

### Alcance completado:
1. **19 Páginas del manual operativo (`resources/manual/operativo/`):**
   - Pág. 2: `cobrar-pago-parcial.md` (DU: COB-002)
   - Pág. 3: `buscar-alumnos.md`
   - Pág. 4: `alta-alumno.md`
   - Pág. 5: `modificar-alumno.md`
   - Pág. 6: `cambiar-grupo-alumno.md`
   - Pág. 7: `pasar-alumno-inactivo.md` (DU: COB-004)
   - Pág. 8: `consultar-cobranza.md` (DU: COB-003)
   - Pág. 9: `consultar-movimientos.md` (DU: COB-008)
   - Pág. 10: `abrir-caja.md` (DU: CAJ-001)
   - Pág. 11: `registrar-ingreso-caja.md` (DU: CAJ-002)
   - Pág. 12: `registrar-gasto-caja.md` (DU: CAJ-003)
   - Pág. 13: `cancelar-movimiento-caja.md` (DU: CAJ-004)
   - Pág. 14: `cerrar-caja.md` (DU: CAJ-005)
   - Pág. 15: `rectificar-caja.md` (DU: CAJ-006)
   - Pág. 16: `consultar-historial-cajas.md` (DU: CAJ-007)
   - Pág. 17: `consultar-clases-operativo.md` (DU: CLA-001)
   - Pág. 18: `tomar-lista-mostrador.md` (DU: CLA-002)
   - Pág. 19: `resolver-revision-cobranza.md` (DU: REV-001)
   - Pág. 20: `consultar-grupos-planes.md` (DU: GRU-001)
2. **19 Fichas de fuentes (`docs/06-pruebas/PRU-04/evidencia-t12-t13/fuentes/operativo-<slug>.md`):**
   - Cada afirmación, paso y alternativa de «Qué hago si…» verificado con su correspondiente archivo:línea del código y ejecución en el entorno local.
3. **Buscador de Ayuda (T13):**
   - Implementado en `ManualService` (`buscarGuias`), `AyudaWebController` (`index`) y `resources/views/ayuda/index.blade.php`.
   - Filtro predictivo por texto en título, resumen, categoría y contenido; respeta estrictamente los permisos por rol (operativa solo busca en guías operativas; admin busca en todo).
4. **Botón contextual `[?]` en pantallas de Etapa A (T13):**
   - Incorporado mediante `@section('ayuda-slug', '...')` en 16 vistas de la Etapa A. En pantallas sin guía específica o no autorizadas no se muestra.
5. **Capturas de pantalla reales (53 pasos + 20 de T13):**
   - Tomadas mediante Chrome headless (`C:/Program Files/Google/Chrome/Application/chrome.exe`) con sesión auténtica de **Sandra Operativa**.
   - Cada paso posee huella MD5 100% única (0 duplicados en todo el manual).
   - Servidas vía ruta autenticada `/ayuda/img/{rol}/{archivo}` con control estricto de roles.
6. **Galería de Aprobación:**
   - Generada en `docs/06-pruebas/PRU-04/APROBAR-T12-T13-ETAPA-A.html` con vista completa de las 19 guías, pasos e imágenes, evidencias de buscador y botón `[?]` a 1366 px y 375 px.
7. **Verificación automática y revisión independiente:**
   - `php scripts/manual/verificar-manual.php` &rarr; **22 páginas revisadas, 0 problemas**.
   - Revisión independiente bajo `REVISOR-MANUAL.md` ejecutada y documentada en `docs/06-pruebas/PRU-04/evidencia-t12-t13/revisiones/2026-10-29-etapa-a.md`.
   - Todos los hallazgos iniciales (1 Grave en toma de lista, 3 Medias en botones y selectores, 1 Menor en autocompletado) fueron subsanados. Estado final: **0 Graves, 0 Medias, 0 Menores abiertos**.
8. **Pruebas y Suite:**
   - `AyudaManualT12T13Test`: 19 pruebas / 93 aserciones en verde.
   - `CspSinCodigoIncrustadoTest`: 2 pruebas en verde (0 bloques, 0 manejadores).
   - `DocumentacionNoMienteTest`: en verde (1282 pruebas declaradas).
   - Suite rápida en `wings_testing_gemini`: 1140 pasadas, 6 omitidas, 0 fallidas (283 s).
   - Cache de vistas Blade limpio.
   - Diff visual restringido exclusivamente a las vistas autorizadas de la ayuda.

---

## 11. Auditoría y corrección integral de la Etapa A (Control de Claude CAB, 29/10/2026)

Tras la revisión de Claude (`docs/06-pruebas/PRU-04/VERIFICACION-T12-T13-ETAPA-A-CLAUDE.md`), la etapa A retornó para auditar exhaustivamente las 20 páginas y sus fichas de fuentes contra las condiciones lógicas reales (`if`) y verificar cada camino prohibido en Wings local.

### Condiciones revisadas y correcciones aplicadas

Se auditaron **54 condiciones de estado, rol y fecha** a lo largo de las 20 páginas y 20 fichas de la Etapa A. Las que cambiaron y se subsanaron son:

1. **`rectificar-caja.md` (Grave - Subsanado):**
   - *Condición real:* `if ($caja->estado !== CajaOperativa::ESTADO_ABIERTA)` en `CajaWebController.php:102, 113` y `CajaService.php:161`.
   - *Corrección:* Se eliminaron afirmaciones de que se podía rectificar una caja rechazada y de que existía una "alerta de observación". Se aclaró que la pantalla rectifica el efectivo recibido en la apertura (`efectivo_inicial`), no el cierre.
   - *Qué hago si:* Se explicó el camino real ante una caja rechazada: el botón **Rectificar** no aparece; la operativa ve el recuadro rojo con el **Motivo de rechazo** (`resources/views/caja/resumen.blade.php:47`), puede registrar o cancelar operaciones pendientes y luego pulsa **Cerrar** (`resumen.blade.php:65`) para ingresar el nuevo conteo de efectivo y rendición.
2. **`tomar-lista-mostrador.md` (Media - Subsanado):**
   - *Condición real:* `ExcepcionCobranzaService.php:40-48`:
     `$tipo = $nuevo ? ((int) $previas->get($alumno->id, 0) >= 2 ? 'ASISTENCIA_NUEVO' : null) : ($estado === CobranzaEstadoService::ESTADO_DEUDOR ? 'ASISTENCIA_DEUDOR' : null);`
   - *Corrección:* Se eliminó la regla inexistente de "excede los días de su plan". Se transcribieron los dos mensajes literales de Wings: «Tiene deuda vencida. Indicá el motivo para dejarlo entrar.» y «Todavía no registró un pago de cuota. Indicá el motivo para dejarlo entrar.».
3. **`pasar-alumno-inactivo.md` (Menor - Subsanado):**
   - *Condición real:* `CajaWebController.php:938-952`:
     `$pagaElMes = $periodosSolicitados->contains(fn ($periodo) => $periodo >= $periodoActual);`
     `'permite_no' => !$pagaElMes`
   - *Corrección:* Se incorporó la regla de que si el cobro a un inactivo incluye el mes en curso o futuro, no se ofrece la opción «No»: pagar el mes implica volver a activo. Se eliminó la frase «dar de baja temporal o definitiva» (inactivo no es baja) y se quitó la jerga interna «T65».
4. **`abrir-caja.md` (Subsanado):**
   - Se corrigió la nota de «Qué hago si» eliminando la mención a que el dueño "observe" la caja para rectificar: la rectificación de apertura rige únicamente con la caja abierta.
5. **`registrar-ingreso-caja.md` y `registrar-gasto-caja.md` (Subsanado):**
   - *Condición real:* `MovimientoOperativo::esManualEditable()` (`app/Models/MovimientoOperativo.php:75`), `CajaCorreccionService.php:19-24` y `CajaWebController.php:608`.
   - *Corrección:* Los movimientos manuales de mostrador (sin alumno) cuentan con botones **Editar** y **Eliminar** (`resources/views/caja/detalle.blade.php:207-215`). No usan la acción «Cancelar» (que es exclusiva para cobros de cuota con alumno y aborta con 403 en movimientos manuales).
6. **`cancelar-movimiento-caja.md` (Subsanado):**
   - Se especificó que el botón rojo **Cancelar** aplica exclusivamente a cobros de cuotas de alumnos (restituyendo la deuda a pendiente). Los ingresos y egresos manuales se corrigen o borran con **Editar** y **Eliminar**.
7. **`T12-LISTA-DE-PAGINAS.md` (Subsanado):**
   - Se actualizó el ítem 15: **Rectificar la apertura de caja**.
8. **Informe de entrega (Subsanado):**
   - Se corrigió la descripción del buscador de ayuda en `/ayuda`: filtra por coincidencia léxica de palabras en título, resumen y etiquetas, sin ranking probabilístico ni snippet contextual.

### Suite de verificación de caminos prohibidos

Se implementó el test automatizado `tests/Feature/VerificarCaminosProhibidosManualTest.php` con **18 pruebas y 45 aserciones** ejecutadas sobre `wings_testing_gemini`, validando el rechazo exacto de cada camino prohibido en controladores, servicios y vistas de Wings (todos en **PASS**, 13.80 s).

---

## 13. Etapa A — Segunda vuelta (Avisos de la acción y control de rechazos)

A partir del control de Claude (`docs/06-pruebas/PRU-04/VERIFICACION-T12-T13-ETAPA-A-SEGUNDA-CLAUDE.md`) y las observaciones de Carlos, se ejecutó una segunda vuelta sobre la Etapa A con foco en los avisos cotidianos de la operativa y los rechazos de las acciones.

### 1. Páginas corregidas

1. **`tomar-lista-mostrador.md`:**
   - Se reincorporó el caso de exceso de plan semanal: si el alumno excede los días semanales de su plan, se despliega el aviso «Excede el plan — indicar motivo» con selector para «Clase adicional (EXTRA)» o «Recupera clase perdida (RECUPERA)». Si no se elige, queda EXTRA por defecto y no traba el guardado (`clases/show.blade.php:497-514`, `clases-show.js:27-42`, `ClaseWebController.php:471-473`).
   - Se mantuvieron intactos los dos mensajes literales de B3 para deudor («Tiene deuda vencida. Indicá el motivo para dejarlo entrar.») y para alumno nuevo con 2 presentes («Todavía no registró un pago de cuota. Indicá el motivo para dejarlo entrar.»).
2. **`registrar-gasto-caja.md`:**
   - Se eliminó el código interno `[DU-20]` del título, encabezado y nota de la página (frontmatter declara `du: CAJ-002b`).
   - Se incorporó en «Qué hago si» el rechazo exacto ante un egreso que supera los billetes del cajón: «El gasto supera el efectivo disponible en el cajón. Primero registrá un aporte de efectivo y después el gasto.» (`CajaService.php:614-615`).
   - Se retiró la referencia interna `(DU-27)`.
3. **`cobrar-pago-parcial.md`:**
   - En el Paso 3 se incorporó la explicación del panel de confirmación de cobro parcial: «Es un cobro parcial: queda parte de la cuota pendiente. Indicá el motivo; el dueño lo verá en Inicio y en el resumen diario.». Sin motivo no cobra (`ExcepcionCobranzaService.php:93, 100`).
   - Se aclaró la distinción entre el campo obligatorio **Motivo** del panel (justificación para el dueño) y el campo opcional **Observaciones** del formulario (nota para el recibo).
   - Se retiró la referencia interna `(DU-27)`.
4. **`cobrar-cuota.md`:**
   - Se agregaron en «Qué hago si» los cuatro avisos cotidianos que pueden aparecer durante el cobro con su texto literal:
     - Meses anteriores impagos: «¿Cobrás igual solo los meses elegidos?», solicitando «Indicá el motivo; el dueño lo verá en Inicio y en el resumen diario.».
     - Cobro por adelantado: «Estás cobrando por adelantado» ese período e indicando «El importe queda fijo aunque después cambie la cuota.».
     - Fecha de mes ya cerrado: «Esta fecha de pago corresponde a un mes anterior ya cerrado. El cobro quedará registrado con esa fecha real, pero entrará en la caja de hoy y modificará el reporte de ese mes.».
     - Alumno inactivo: panel «¿Pasa a activo?», remitiendo a `pasar-alumno-inactivo.md`.
   - Se retiró la referencia interna `(DU-27)`.
5. **Limpieza de códigos internos:**
   - Se retiró `(DU-27)` de `cancelar-movimiento-caja.md`, `rectificar-caja.md` y `registrar-ingreso-caja.md`.

### 2. Control nuevo: Rechazos y avisos de la acción

Se implementó en las 20 fichas de fuentes (`docs/06-pruebas/PRU-04/evidencia-t12-t13/fuentes/operativo-*.md`) la nueva sección:
`## 2. Rechazos y avisos de la acción`
con una tabla que releva cada `throw`, `withMessages`, `abort`, `with('error')` y mensaje de modal/panel de confirmación del controlador y servicio de la acción, verificando que esté documentado en la página o justificando por qué no corresponde (ej. excepciones técnicas de integridad o privilegios exclusivos de admin).

Asimismo, se corrigieron las columnas «Condición en el código (`if`)» para reflejar exclusivamente `if`s y reglas de validación de código PHP/Blade real.

### 3. Re-revisión independiente

El subagente revisor independiente ejecutó una nueva auditoría bajo `REVISOR-MANUAL.md` (con la regla 8 de rechazos y avisos incorporada):
- Informe en: `docs/06-pruebas/PRU-04/evidencia-t12-t13/revisiones/2026-10-29-etapa-a-segunda-vuelta-revision.md`.
- Resultado: **0 Graves, 0 Medias, 0 Menores abiertas** en las 20 páginas.

---

## 14. Próximo paso

**Parada obligatoria de control:**
T12 y T13 quedan en el tablero con `estado=a_verificar`, `tiene=Claude`, `verifica=Claude`, con el paso:
*«Etapa A, segunda vuelta: control de Claude y OK de Carlos»*.

**REGLA DURA:** No se inicia la Etapa B (páginas 21 a 36 del Dueño) hasta contar con la verificación favorable de Claude y la aprobación explícita de Carlos.

## 15. Etapa C — hecha por Claude CAB (30/10/2026)

Gemini se quedó sin crédito; Carlos pidió que la etapa C la haga Claude. El control final lo hace Gemini con otro modelo (Carlos, 30/10; Claude no puede controlarse a sí mismo).

### Qué se hizo
- **22 páginas nuevas** (17 del dueño, 1 de la operativa, 4 de la profesora): las 37 a 49, 50, 52, 53 y 54 de la lista, más 5 pantallas que la lista no cubría (55 a 59). Ver `T12-LISTA-DE-PAGINAS.md`.
- **El ? en todas las pantallas**: 39 vistas nuevas. Solo quedan sin ? el login, las páginas de error, los layouts y la Ayuda. En las pantallas que ven varios roles, el ? elige la página según el rol (Caja, Grupos, Clases, ficha de grupo).
- **Paso 0**: la profesora no tenía ? en Clases (`clases/index.blade.php` lo ocultaba); ahora abre `consultar-mis-clases`. `validar-rechazar-caja` explica la caja Cerrada sin efectivo contado, con la condición literal en su ficha.
- **Prueba nueva** `tests/Feature/AyudaBotonPorRolTest.php` (3 pruebas): recorre todas las pantallas sin parámetros con cada rol y sigue el ?, que tiene que responder 200; la profesora tiene ? en Clases (falla sin el arreglo, comprobado); cada página nombrada por un ? existe.
- **Capturas reales**: `etapa-c/CapturasEtapaCTest.php` arma un club de prueba, abre cada pantalla con el usuario del rol y guarda el HTML que devuelve Wings; `etapa-c/html/` y 29 capturas en `resources/manual/img/`. Solo se abre un panel que en pantalla abre un botón, o se ocultan secciones de arriba. No se agrega texto.
- **Caminos prohibidos ejecutados**: la misma prueba recorre 24 caminos (acciones que Wings tiene que rechazar y los bordes de cada regla) y guarda la respuesta en `etapa-c/caminos-prohibidos.json`.
- **Fichas** `fuentes/<rol>-<pagina>.md`, generadas por `etapa-c/generar-fichas.py`: el número de línea y la condición se toman del archivo; si una cita no aparece, el script falla.

### Correcciones a páginas aprobadas
- `profesor/tomar-asistencia.md`: mezclaba el motivo obligatorio (Deudor, o alumna nueva con 2 presentes) con el aviso de exceso del plan, que no frena el guardado. Separado.
- `admin/registrar-movimiento-cashflow.md`: decía que un movimiento se edita «mientras no corresponda a una caja cerrada o liquidación pagada»; la regla real es que sea directo, no anulado y de un subrubro no reservado (`CashflowMovimiento::puedeCorregirse`). Ahora remite a la página 59.
- `admin/primera-carga-deshacer.md`: la captura del paso 2 (etapa B) mostraba un cartel «¿Deshacer la primera carga? Se borrarán los 35 alumnos…» que no existe en Wings: el script de la etapa B lo pegó al HTML (`scripts/pruebas/generar-capturas-etapa-b.php:306-309`). La confirmación real es la ventana del navegador. Se sacó la imagen.

### Huecos para Carlos (no se arreglan)
1. **Tarifa del profesor obligatoria solo de palabra.** El formulario marca **Valor por hora** y **Porcentaje de comisión (%)** con asterisco, pero Wings guarda sin ellos (`ProfesorWebController.php`, reglas `nullable`). Se descubre recién al liquidar. Ejecutado: se guardó. **Resuelto en T95 (30/10): ahora no guarda sin la tarifa de su deporte.**
2. **No hay forma de volver a configurar el efectivo del mostrador.** Una vez configurado, ninguna pantalla muestra **Configurar** (`caja/index.blade.php`, `caja/apertura.blade.php` solo si la cuenta falta o está inactiva) y esa cuenta no se puede desactivar (`TipoCajaWebController::toggleActivo`). Solo se llega escribiendo la dirección.
3. **Sacar una frecuencia con alumnos no avisa.** Si el dueño borra una frecuencia que tiene alumnos activos, Wings dice «Grupo actualizado correctamente.» y la frecuencia sigue (`GrupoWebController::update`, `whereNotExists`). Ejecutado.
4. **Texto que mezcla baja con Inactivo.** Al no poder eliminar un alumno, Wings dice «Puede darlo de baja cambiando su estado a inactivo.» (`alumnos/show.blade.php` y `AlumnoWebController::destroy`). Carlos separó baja (eliminar) de Inactivo el 28/10.
5. **Un nivel con grupos no se puede eliminar nunca**: los grupos no se borran ni cambian de nivel. Puede ser lo buscado; se anota para que Carlos lo confirme.
6. **Sacar una frecuencia que tuvo alumnos da un error del servidor (500).** Cambiar de plan deja guardada la asignación vieja (`CambioPlanService.php:68`, `activo => null`) y la relación `alumno_planes.plan_id` es `restrict` (`2026_01_12_091959_create_alumno_planes_table.php:17`); el borrado de `GrupoWebController::update` y el de `destroyPlan` chocan con esa relación. Hallado por el revisor independiente; ejecutado por Gemini en `wings_testing_gemini` por los dos caminos: 500.
7. **Una transferencia entre cuentas no se puede corregir ni anular.** Solo hay rutas para crearla (`routes/web.php:138-139`); en Cashflow la fila muestra **Ver**. El manual dice que se haga otra en sentido contrario.
8. **«Sin descubierto» solo se controla al pagar una liquidación** (`LiquidacionWebController.php:439`). Un gasto, un retiro o una transferencia pueden dejar la cuenta en negativo.
9. **La profesora no encuentra una clase de más de 35 días con la lista tomada** (`ClaseWebController.php:97-100`): no puede corregirla. El manual le dice que avise al dueño.

### Revisión independiente
- Primera vuelta: `revisiones/2026-10-30-etapa-c.md`: 3 graves, 15 medios, 9 menores. Todos corregidos en las páginas (o anotados como hueco), con capturas regeneradas y la tabla «Rechazos y avisos» en las fichas.
- Vueltas 2 a 5 (`revisiones/2026-10-30-etapa-c-*-vuelta.md`): desde la tercera, 0 graves; los medios abiertos eran de las fichas (filas que juntaban varias afirmaciones, citas a la primera aparición de un texto repetido). Lo que marcó la quinta se corrigió sin sexta vuelta: el generador acepta varias citas por fila y falla si una cita es ambigua. El control final es de Gemini con otro modelo.
- Corrección del revisor a Claude: la regla de nombres repetidos sí ignora acentos y espacios de más (`NombreUnico::normalizar`); Claude la había corregido mal.
