# Implementación T12 y T13 — Etapa 1: Estructura mínima, dos muestras y lista completa

**Gemini CyE, 28/10/2026**
**Firma:** LOG GEM CYE

Entrega de la **Etapa 1** correspondiente a las tareas:
- **T12:** Manual del usuario: contenido por tarea y por rol.
- **T13:** Ayuda dentro de Wings: entrada en el menú y signo de pregunta en cada pantalla.

Conforme a las directivas de Carlos (10/10, 13/10 y 28/10), el trabajo se dividió en dos etapas con una parada obligatoria al finalizar la Etapa 1 para revisión y aprobación antes de proceder con el resto de las páginas y el buscador.

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

## 9. Próximo paso

**Parada de control obligatoria:** T12 y T13 quedan en el tablero con `a_verificar`, `tiene=Claude`, `verifica=Claude` con paso *«Corrección etapa 1: control de Claude y OK de Carlos»*. No se avanza a la Etapa 2 hasta contar con la validación de Claude y el visto bueno explícito de Carlos.
