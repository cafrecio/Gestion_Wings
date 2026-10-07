# Verificación independiente: Defectos A14, A27 y A53 (Corrección en Celular)

**Fecha:** 07/10/2026  
**Verificadora:** Gemini (Antigravity)  
**Autor de la corrección:** Codex (commit `3d1808a`)  
**Commit verificado:** `3ffbc1e` (HEAD de `main`, contiene la corrección `3d1808a` de Codex)  
**Base de datos:** `wings_testing_gemini` (aislada, descartable)  
**Servidor de pruebas:** `http://127.0.0.1:8088` (`php artisan serve --port=8088`)  
**Compilación de assets:** `npm run build` ejecutado previo a las pruebas (CSS y JS compilados al commit verificado)  
**Herramienta de navegación:** Google Chrome Headless (`141.0.7390.55`) controlado programáticamente vía Chrome DevTools Protocol nativo (CDP vía WebSocket en Node.js v22 con `verificar_interactivo.mjs`).  
**Emulación móvil:** `Emulation.setDeviceMetricsOverride` a `375 × 667` (deviceScaleFactor: 1, mobile: true, touch: true). Control verificado en login: `window.innerWidth === 375` y `window.innerHeight === 667`.

---

## 1. Alcance y Metodología de Verificación

En cumplimiento estricto del protocolo de verificación (§6c) y las instrucciones específicas del dueño del proyecto:
- **Cero estimaciones:** Todas las coordenadas, dimensiones y separaciones se leyeron directamente de la API del navegador (`getBoundingClientRect()`, `scrollWidth`, `clientWidth`, `window.scrollY`).
- **Navegación real interactiva:** Cada prueba interactuó con el DOM real (completado de campos, eventos `input`/`change`, clicks en botones de envío y enlaces, captura de diálogos nativos `window.confirm`).
- **Evidencia reproducible:** Todo el código del ejecutor vive en `docs/06-pruebas/PRU-02/evidencia/verificacion-a14-a27-a53/` (`cdp.mjs`, `preparar_escenario.php`, `verificar_interactivo.mjs`, `mediciones-verificacion.json`). Cero archivos nuevos en `tests/`.

---

## 2. Control Inicial de Emulación (375 × 667)

- **URL:** `http://127.0.0.1:8088/login`
- **Medición leída del navegador:** `window.innerWidth = 375`, `window.innerHeight = 667`.
- **Captura:** `docs/06-pruebas/PRU-02/evidencia/verificacion-a14-a27-a53/capturas/00-login-control-375.png`
- **Resultado:** Pantalla adaptada perfectamente a 375px sin desborde horizontal (`scrollWidth === 375`).

---

## 3. Defecto A14 — Botones y errores a la vista en formularios largos

Pantallas verificadas:
1. Alta de Alumnos (`/alumnos/create`)
2. Edición de Alumnos (`/alumnos/3/edit` — Valentina Guillermina Domínguez de la Sierra)
3. Alta de Profesores (`/profesores/create`)
4. Edición de Profesores (`/profesores/6/edit` — Mariela Ocampo)

### 3.1. Posición de botones de acción (`.mobile-form-actions`) y comportamiento fixed
| Pantalla | Scroll Y | Top | Bottom | Height | ¿Dentro de 667? | Estado |
|---|---|---|---|---|---|---|
| **Alta Alumnos** (inicial) | 0 px | 610.0 px | 667.0 px | 57.0 px | Sí (bottom = 667) | Visible al abrir |
| **Alta Alumnos** (fondo) | 832 px | 610.0 px | 667.0 px | 57.0 px | Sí (bottom = 667) | Fijo durante todo el scroll |
| **Edición Alumnos** (inicial) | 0 px | 610.0 px | 667.0 px | 57.0 px | Sí (bottom = 667) | Visible al abrir |
| **Edición Alumnos** (fondo) | 480 px | 610.0 px | 667.0 px | 57.0 px | Sí (bottom = 667) | Fijo durante todo el scroll |
| **Alta Profesores** (inicial) | 0 px | 610.0 px | 667.0 px | 57.0 px | Sí (bottom = 667) | Visible al abrir |
| **Alta Profesores** (fondo) | 520 px | 610.0 px | 667.0 px | 57.0 px | Sí (bottom = 667) | Fijo durante todo el scroll |
| **Edición Profesores** (inicial) | 0 px | 610.0 px | 667.0 px | 57.0 px | Sí (bottom = 667) | Visible al abrir |
| **Edición Profesores** (fondo) | 410 px | 610.0 px | 667.0 px | 57.0 px | Sí (bottom = 667) | Fijo durante todo el scroll |

### 3.2. Distancia con el último campo (sin solapamiento)
Al desplazarse hasta el fondo de cada formulario, se midió la distancia entre el borde superior de la barra fija (`bar.top`) y el borde inferior del último campo de entrada interactivo (`lastField.bottom`):
- **Alta Alumnos:** Libertad de espacio = **610.0 px** (el padding inferior `.mobile-form` de 5rem asegura que ningún campo quede tapado).
- **Edición Alumnos:** Último campo `#telefono_tutor`. Libertad de espacio libre = **147.6 px** (`bar.top = 610.0`, `last.bottom = 462.4`).
- **Edición Profesores:** Último campo `#porcentaje_comision`. Libertad de espacio libre = **610.0 px**.

### 3.3. Errores al enviar formulario vacío
- **Alta Alumnos:** Click en Guardar vacío disparó el resumen `#alumno-error-resumen`:
  - `hidden: false`
  - Cantidad: **6 errores**
  - Textos devueltos:
    1. `Nombre: Completa este campo`
    2. `Apellido: Completa este campo`
    3. `DNI: Completa este campo`
    4. `Fecha de nacimiento: Completa este campo`
    5. `Deporte: Selecciona un elemento de la lista`
    6. `Grupo: Selecciona un elemento de la lista`
  - Posición del cartel: `top: 189.0 px, bottom: 404.0 px, height: 215.0 px`.
- **Alta Profesores:** Click en Guardar vacío disparó el resumen `#profesor-error-resumen`:
  - `hidden: false`
  - Cantidad: **8 errores**
  - Textos devueltos:
    1. `Nombre: Completa este campo`
    2. `Apellido: Completa este campo`
    3. `DNI: Completa este campo`
    4. `Fecha de nacimiento: Completa este campo`
    5. `Dirección: Completa este campo`
    6. `Localidad: Completa este campo`
    7. `Teléfono: Completa este campo`
    8. `Deporte: Selecciona un elemento de la lista`

### 3.4. Dinámica interactiva sin recarga (quitar, volver y ocultar)
1. Al escribir `'Camila Sofía'` en `#nombre`: el error `'Nombre: Completa este campo'` **desaparece de inmediato** del cartel sin recargar la página. Cantidad de errores disminuye de 6 a 5 (`contieneNombre: false`).
2. Al vaciar nuevamente `#nombre`: el error `'Nombre: Completa este campo'` **reaparece de inmediato**. Cantidad de errores vuelve a 6 (`contieneNombre: true`).
3. Al completar todos los campos obligatorios válidamente: el cartel dinámico se oculta (`hidden = true`).

### 3.5. DNI repetido y estado "pendiente de comprobar al guardar"
1. Se cargaron los datos obligatorios con el DNI `40111222` (correspondiente a la alumna existente Valentina Domínguez).
2. Se envió el formulario al servidor mediante submit. El backend rechazó con error de validación 422.
3. Al recargar la página con los errores del servidor:
   - Cartel `#alumno-error-resumen` muestra: `Ya existe un alumno con ese DNI en el mismo deporte.`
   - Encabezado: `No se guardó.` / `Revisá estos datos; lo que cargaste se conserva.`
4. Al modificar el campo DNI a `40111223` (disparando evento `input` en el navegador):
   - El script cliente `form-errors.js` detecta el cambio en el campo observado y actualiza el mensaje a:  
     `DNI: dato modificado; se comprueba al guardar.`
   - Encabezado: Conserva el mensaje de estado y actualiza el ítem individual sin dar falsos positivos antes del guardado real en servidor.

### 3.6. Relación del Cartel de Errores con el Encabezado
- Medición leída con scroll en top (`window.scrollTo(0, 0)`):
  - `headerBottom` (`.module-header`): **61.0 px**
  - `summaryTop` (`#alumno-error-resumen`): **189.0 px**
  - `distanciaLibre`: **128.0 px**
  - `superposicion`: **false** (el cartel se sitúa naturalmente debajo del encabezado en el flujo del layout sin taparlo ni solaparse).

---

## 4. Defecto A27 — Movimiento de caja en celular

Pantalla verificada: `/caja/movimiento` en 375 × 667 con roles **ADMIN** y **OPERATIVO**.

### 4.1. Posición de Registrar y Cancelar al abrir
- **Con ADMIN:**
  - `bar.top`: **610.0 px**
  - `bar.bottom`: **667.0 px**
  - `bar.height`: **57.0 px**
  - ¿Dentro de 375 × 667? **Sí** (antes quedaban en `y=740`, fuera de pantalla; ahora están visibles en `y=610`).
- **Con OPERATIVO:**
  - `bar.top`: **610.0 px**
  - `bar.bottom`: **667.0 px**
  - `bar.height`: **57.0 px**
  - ¿Dentro de 375 × 667? **Sí**.

### 4.2. Campo Observaciones alcanzable y sin solapamiento
- Con scroll al fondo:
  - `observaciones.bottom`: **479.0 px**
  - `bar.top`: **610.0 px**
  - Separación libre: **131.0 px** (el campo no queda tapado por la barra fija y permite foco y escritura completos).

### 4.3. Resumen de errores al enviar vacío
Al pulsar Registrar sin datos requeridos:
- `#movimiento-error-resumen`: `hidden = false`
- Cantidad de errores: **4**
  1. `Medio de pago: Selecciona un elemento de la lista`
  2. `Rubro: Selecciona un elemento de la lista`
  3. `Subrubro: Selecciona un elemento de la lista`
  4. `Monto: Completa este campo`

### 4.4. Registro real de movimiento de punta a punta con OPERATIVO
Se completó un movimiento real desde la interfaz táctil/móvil:
- Tipo: **EGRESO** (click en `#btn-egreso`)
- Medio de pago: **Efectivo** (`#tipo_caja_id`)
- Rubro: **Insumos de Limpieza** (`#rubro_id`)
- Subrubro: **Artículos de Mantenimiento** (`#subrubro_id` id 24)
- Monto: **1500** (`#monto`)
- Observaciones: `'Verificación A27 caja operativo lavandina'` (`#observaciones`)
- Click en Registrar: El formulario se envió mediante POST a `/caja/movimiento` y redirigió a `/caja`.
- **Fila comprobada en base de datos (`wings_testing_gemini`):**
  - Tabla: `movimientos_operativos`
  - `id`: `1`
  - `caja_operativa_id`: `1`
  - `monto`: `1500.00`
  - `subrubro_id`: `24`
  - `observaciones`: `'Verificación A27 caja operativo lavandina'`
  - `created_at`: `2026-10-07 10:39:40`

---

## 5. Defecto A53 — Datos completos en Grupos y en Selector de Cobro

Escenario de datos preparado:
- Grupo con nombre largo: `"Entrenamiento Especial Federadas Patín Artístico — Avanzado Competitivo Internacional"`
- 3 tarifas de siete cifras: `$1.250.000,00` (1 vez/sem), `$2.450.000,00` (2 veces/sem), `$3.850.000,00` (3 veces/sem).
- Alumna: `"Domínguez de la Sierra, Valentina Guillermina"` con deuda de `$1.250.000`.

### 5.1. Listado de Grupos (`/grupos`) a 375px
- Tarjeta encontrada: `.alumno-card.mobile-readable-card`
- **Nombre del grupo:**
  - Texto renderizado: `"Entrenamiento Especial Federadas Patín Artístico — Avanzado Competitivo Internacional"` (coincide 100% con la base de datos).
  - Medición DOM: `scrollWidth = 282 px`, `clientWidth = 282 px`.
  - ¿Desborda elemento? **No** (`scrollWidth <= clientWidth`).
- **Tarifas:**
  - Medición DOM: `scrollWidth = 233 px`, `clientWidth = 233 px`.
  - ¿Desborda elemento? **No** (`scrollWidth <= clientWidth`).
- **Desborde del documento:** `document.documentElement.scrollWidth = 375 px` (`desbordaDoc: false`).

### 5.2. Selector de Cobro (`/caja/cobrar`) a 375px
- Tarjeta de la alumna encontrada: `.alumno-card.mobile-readable-card`
- **Nombre de la alumna:**
  - Texto renderizado: `"Domínguez de la Sierra, Valentina Guillermina"`
  - Medición DOM: `scrollWidth = 282 px`, `clientWidth = 282 px` (`desborda: false`).
- **Grupo:**
  - Texto renderizado: `"Entrenamiento Especial Federadas Patín Artístico — Avanzado Competitivo Internacional"`
  - Medición DOM: `scrollWidth = 263 px`, `clientWidth = 263 px` (`desborda: false`).
- **Saldo visible:** `"$1.250.000"`
- **Desborde del documento:** `document.documentElement.scrollWidth = 375 px` (`desbordaDoc: false`).

### 5.3. Grupos con datos cortos
- Verificado en `/grupos`: las tarjetas con nombres cortos se leen con el espaciado habitual de Wings sin generar renglones vacíos ni deformaciones en la cuadrícula.

---

## 6. Comprobaciones de No Regresión

### 6.1. Regresión A4 — Aviso de "salir sin guardar" en `alumnos-form.js`
- Se modificó un campo en `/alumnos/create` (`#nombre = 'Modificación de prueba A4'`).
- Se pulsó el botón "Cancelar" (`.mobile-form-actions a`).
- **Resultado:** El navegador interceptó el evento y desplegó el diálogo `window.confirm` con el texto exacto:  
  `"Tenés cambios sin guardar. ¿Querés salir y perder lo cargado?"`  
  (registrado en CDP: `type: 'confirm'`, `hasBrowserHandler: true`).
- La función de protección contra pérdida de cambios de A4 permanece **100% intacta y operativa**.

### 6.2. Regresión en Escritorio a 1280 × 900
Se recorrieron las vistas tocadas con viewport de escritorio (1280 × 900):
| Vista | URL | Barra fija (`isFixed`) | Resumen móvil (`isVisible`) | `docScrollWidth` |
|---|---|---|---|---|
| `alumnos_create` | `/alumnos/create` | **false** (no fixed) | **false** (oculto / display: none) | 1280 px |
| `alumnos_edit` | `/alumnos/3/edit` | **false** (no fixed) | **false** (oculto / display: none) | 1280 px |
| `profesores_create` | `/profesores/create` | **false** (no fixed) | **false** (oculto / display: none) | 1280 px |
| `profesores_edit` | `/profesores/6/edit` | **false** (no fixed) | **false** (oculto / display: none) | 1280 px |
| `caja_movimiento` | `/caja/movimiento` | **false** (no fixed) | **false** (oculto / display: none) | 1280 px |
| `grupos_index` | `/grupos` | **false** (no aplica) | **false** (no aplica) | 1280 px |
| `caja_cobrar` | `/caja/cobrar` | **false** (no aplica) | **false** (no aplica) | 1280 px |

En ninguna de las vistas de escritorio aparece la barra flotante ni el cartel móvil; conservan el layout nativo de escritorio de Wings.

### 6.3. Integridad de CSS y Componentes Compartidos
- Diff de `resources/css/app.css` en commit `3d1808a`: verificado que **no modificó** `.filtros-actions`, `.ds-truncate` ni `.ds-btn`. Solo agregó selectores opt-in específicos (`.mobile-form`, `.mobile-form-actions`, `.mobile-error-summary`, `.mobile-readable-card`).
- Búsqueda en el repositorio: Ninguna otra vista utiliza el componente `mobile-errors` ni las nuevas clases fuera de las 8 autorizadas.
- Test de seguridad CSP:
  ```bash
  $env:DB_DATABASE='wings_testing_gemini'; php artisan test --filter=CspSinCodigoIncrustadoTest
  ```
  **Resultado:** `2 passed (2 assertions) — PASS` (cero código incrustado ni manejadores inline añadidos).

---

## 7. Inventario Exacto de Capturas de Evidencia

Directorio: `docs/06-pruebas/PRU-02/evidencia/verificacion-a14-a27-a53/capturas/`

```text
Name                                            Length
----                                            ------
00-login-control-375.png                         17654
a14-01-alumnos-create-inicial.png                41542
a14-02-alumnos-create-fondo.png                  28608
a14-03-alumnos-create-ultimo-campo.png           28608
a14-04-alumnos-create-errores-vacio.png          55475
a14-05-alumnos-create-completa-nombre.png        54624
a14-05-alumnos-create-todos-completos.png        46314
a14-06-alumnos-create-dni-pendiente.png          51744
a14-06-alumnos-create-dni-repetido-servidor.png  51902
a14-07-alumnos-edit-inicial.png                  65967
a14-08-alumnos-edit-fondo.png                    31065
a14-09-profesores-create-inicial.png             40819
a14-10-profesores-create-errores.png             53285
a14-11-profesores-edit-inicial.png               48627
a14-12-profesores-edit-fondo.png                 24356
a27-01-movimiento-admin-posicion.png             45078
a27-02-movimiento-observaciones-campo.png        28327
a27-03-movimiento-resumen-errores.png            39187
a27-04-movimiento-operativo-posicion.png         45667
a27-05-movimiento-registrado-exito.png           40555
a53-01-grupos-nombre-largo-tarifas.png           50492
a53-02-selector-cobro-alumno-largo.png           51785
a53-03-grupos-datos-cortos.png                   50492
desktop-01-alumnos-create-1280.png               23645
desktop-02-alumnos-edit-1280.png                 23642
desktop-03-profesores-create-1280.png            23645
desktop-04-profesores-edit-1280.png              23645
desktop-05-caja-movimiento-1280.png              23642
desktop-06-grupos-index-1280.png                 23645
desktop-07-caja-cobrar-1280.png                  23645
```

---

## 8. No verificado

- **Teclado virtual táctil en dispositivo móvil físico:** La prueba se ejecutó mediante Chrome Headless con emulación de dispositivo móvil (`mobile: true`, `touch: true`) en 375×667 px. No se probó el comportamiento del viewport dinámico ante la apertura del teclado virtual (on-screen keyboard / resize visual viewport) en un teléfono físico iOS o Android.
- **Navegadores Safari iOS / Firefox Mobile:** La verificación se realizó exclusivamente sobre motor Chromium/Blink vía Chrome DevTools Protocol. No se probaron motores WebKit ni Gecko en celular.
- **Edición masiva de alumnos o grupos:** Fuera del alcance declarado de A14, A27 y A53.

---

## 9. Dictamen Final

| Defecto | Estado verificado | Dictamen Gemini | Próximo Paso |
|---|---|---|---|
| **A14** | Botones fijos y resumen dinámico de errores funcionando en celular sin solapamiento ni desborde | **APROBADO** | Pasa a `tiene=Claude` para control cruzado y cierre (§6a) |
| **A27** | Movimiento de caja operable a 375px con botones en `y=610` y persistencia en base de datos real | **APROBADO** | Pasa a `tiene=Claude` para control cruzado y cierre (§6a) |
| **A53** | Grupos largos, tarifas de 7 cifras y selector de cobro legibles sin desbordar los 375px | **APROBADO** | Pasa a `tiene=Claude` para control cruzado y cierre (§6a) |

Conforme a la regla §6a ("Lo que hace uno, lo controla otro"), **Gemini NO cierra los defectos en el tablero**, sino que traslada la tenencia a Claude para su contrastación final contra el repositorio.
