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
- `GET /ayuda/{slug}`: `web.ayuda.show` &rarr; `AyudaWebController@show`.

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

Se implementó la suite `tests/Feature/AyudaManualT12T13Test.php` con **11 pruebas y 46 aserciones**, todas en verde:
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

---

## 6. Evidencia y galería de aprobación

- **Galería visual:** `docs/06-pruebas/PRU-04/APROBAR-T12-T13-MUESTRAS.html`
- **Carpeta de capturas:** `docs/06-pruebas/PRU-04/evidencia-t12-t13/`
- Todas las capturas fueron tomadas de la aplicación real mediante Chrome headless (`C:/Program Files/Google/Chrome/Application/chrome.exe`), con dimensiones de 1366 px para escritorio y dentro de marco iframe para móvil de 375 px (respetando la regla de Windows para pantallas móviles de AGENTS.md).

---

## 7. Próximo paso

**Parada de control obligatoria:** T12 y T13 quedan en el tablero con `tiene=Carlos` a la espera de su visto bueno sobre las dos muestras y la lista de 54 páginas. Una vez aprobado por Carlos, se procederá con la Etapa 2 (redacción de las páginas restantes, incorporación del botón `?` en todas las pantallas y armado del buscador).
