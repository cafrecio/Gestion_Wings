# Propuesta de Diseño A12 y A24 — El inicio del operativo

**Fecha:** 07/10/2026 · **Autor:** Gemini (Antigravity) · **Base de prueba:** `wings_testing_gemini` (commit `c65d628`)  
**Visor interactivo de comparación:** [`visor-comparacion.html`](evidencia/a12-a24/visor-comparacion.html)  
**Carpeta de evidencia y capturas:** [`evidencia/a12-a24/`](evidencia/a12-a24/)

---

## 1. ETAPA 1 — Relevamiento del Estado Actual

La pantalla de inicio del operativo (`/operativo`, vista `resources/views/operativo/dashboard.blade.php`, controlador `app/Http/Controllers/OperativoDashboardController.php`) se diseñó originalmente en julio de 2026, antes de que el 06/10 se implementara el contrato A25 de apertura explícita y cajón compartido único por club.

Se evaluaron las **7 situaciones reales del día**, obteniendo el render real de Laravel y capturando en Chromium tanto en **Escritorio (1280×900)** como en **Celular (375×667 en marco de medición sin recorte)**. Además, se ejecutó una prueba de navegación HTTP automatizada sobre todos los botones presentados ([`analisis-botones-antes.json`](evidencia/a12-a24/analisis-botones-antes.json)).

### Diagnóstico de cálculos en `OperativoDashboardController`

1. **Stats del día en cero:**
   - `$cajas = CajaOperativa::where('usuario_operativo_id', $user->id)->whereDate('apertura_at', $hoyAr->toDateString())...`
   - **Cobrado hoy ($0):** Suma importes de ingresos de cajas abiertas hoy por este usuario. Al inicio del día da $0 **porque todavía no hubo cobros**, no porque la suma esté rota. Sin embargo, colocar tres cajas gigantes con ceros mudos en la parte superior ocupa el área más visible sin aportar guía para iniciar la jornada.
   - **Cobros registrados (0):** Cuenta movimientos de ingreso en cajas de hoy de este usuario (0 al inicio).
   - **Cajas hoy (0):** Cuenta turnos abiertos hoy por este usuario (0 al inicio).
   - **Falla de dominio compartido:** Si el turno de la mañana lo abrió otro operativo (ej. Marcos Peña) y cobró cuotas, cuando entra el operativo de la tarde (ej. Sandra Vidal) el controlador filtra estrictamente por `usuario_operativo_id == $user->id`, por lo que Sandra ve $0 recaudados y 0 cajas hoy, ignorando la actividad del club.

2. **Caja y estado (`OperativoEstadoService`):**
   - El servicio busca únicamente cajas donde `usuario_operativo_id == $userId`.
   - Ignora si el club ya tiene un turno abierto por otro compañero en el cajón compartido.
   - Si el usuario no tiene caja abierta propia, la vista cae en el bloque `@else` ("Sin caja hoy").

3. **Diagnóstico del defecto A24 (Comprobado vivo por HTTP real):**
   - En la tarjeta "Sin caja hoy", la vista actual renderiza:
     `<a href="{{ route('web.caja.cobrar-cuota') }}" ...>Cobrar</a>`
   - Cuando el usuario hace clic en **"Cobrar"**, el navegador pide `GET /caja/cobrar`.
   - En `CajaWebController::cobrarCuotaSelect()` (línea 643):
     `if ($this->aperturaNecesaria()) return redirect()->route('web.caja.apertura');`
   - El servidor responde **HTTP 302 Redirect** y rebota al usuario forzosamente a `/caja/apertura`.
   - **El defecto A24 está 100% activo:** La pantalla incita a cobrar cuando el sistema no lo permite sin antes declarar el cambio inicial en la apertura.

4. **Destino erróneo de enlaces de apoyo:**
   - La tarjeta métrica **"Con deuda"** enlaza a `route('web.alumnos.index')` (el padrón general de alumnos) en lugar de `route('web.cobranza.index')` (la pantalla de cobranza del mostrador donde se gestionan las deudas).
   - La tarjeta métrica **"Posibles inactivos"** es un contenedor mudo (`<div>`), a pesar de que el operativo tiene habilitada la pantalla `route('web.revision-cobranza.index')`.
   - Cuando la caja se cierra hoy, el botón dice **"Nueva caja"** (dos palabras, viola la regla de un solo verbo corto) y enlaza a `route('web.caja.index')` (el listado general de cajas) en vez de llevar a la apertura (`/caja/apertura`).

---

### Relevamiento de las 7 situaciones reales

| # | Situación | Qué muestra hoy | Botones que ofrece | Destino y Comportamiento | Captura Desktop | Captura 375 |
|---|---|---|---|---|---|---|
| **1** | **Recién llega (sin caja abierta hoy)** | 3 cuadros en cero arriba. Tarjeta "Sin caja hoy - No hay caja registrada para hoy". Clases y alumnos abajo. | **Cobrar** (en tarjeta caja)<br>**Lista** (en clases)<br>**Con deuda** (en alumnos) | • **Cobrar:** apunta a `/caja/cobrar` y **rebota con 302** a `/caja/apertura` (A24 vivo).<br>• **Lista:** carga `/clases/{id}` (200 OK).<br>• **Con deuda:** carga `/alumnos` (200 OK, destino erróneo). | [`escenario-1-desktop.png`](evidencia/a12-a24/capturas-antes/escenario-1-desktop.png) | [`escenario-1-375.png`](evidencia/a12-a24/capturas-antes/escenario-1-375.png) |
| **2** | **Turno abierto por él** | Métricas con recaudación ($15.000, 1 cobro, 1 caja). Tarjeta "Caja abierta - Abierta desde 09:15". Clases, alumnos y card de caja abajo. | **Cobrar**, **Movimiento**, **Resumen** (en tarjeta caja)<br>**Lista** (en clases)<br>**Detalle** (en card de caja) | • **Cobrar:** carga `/caja/cobrar` (200 OK).<br>• **Movimiento:** carga `/caja/movimiento` (200 OK).<br>• **Resumen:** carga `/caja/{id}/resumen` (200 OK).<br>• **Detalle:** carga `/caja/{id}/detalle` (200 OK). | [`escenario-2-desktop.png`](evidencia/a12-a24/capturas-antes/escenario-2-desktop.png) | [`escenario-2-375.png`](evidencia/a12-a24/capturas-antes/escenario-2-375.png) |
| **3** | **Turno abierto por otro operativo (cajón compartido)** | Muestra erróneamente "Sin caja hoy" porque filtra por su usuario. Ignora que el cajón del club ya está abierto por Marcos Peña. | **Cobrar** | • **Cobrar:** rebota con 302 a `/caja/apertura`, y en apertura el sistema le dice que **no puede abrir** porque el turno de Marcos ya está activo (confusión total). | [`escenario-3-desktop.png`](evidencia/a12-a24/capturas-antes/escenario-3-desktop.png) | [`escenario-3-375.png`](evidencia/a12-a24/capturas-antes/escenario-3-375.png) |
| **4** | **Cerró su turno y espera validación** | Tarjeta verde: "Caja cerrada - Última caja: CERRADA — cerrada a las 13:00". | **Nueva caja** | • **Nueva caja:** lleva a `/caja` (200 OK), el listado histórico de cajas, en vez de abrir un turno nuevo. Viola regla de un solo verbo. | [`escenario-4-desktop.png`](evidencia/a12-a24/capturas-antes/escenario-4-desktop.png) | [`escenario-4-375.png`](evidencia/a12-a24/capturas-antes/escenario-4-375.png) |
| **5** | **Tiene una caja rechazada para corregir** | Banner rojo superior: "Tenés 1 caja rechazada. El administrador rechazó una caja tuya...". Abajo repite "Sin caja hoy" y "Cobrar". | **Ver** (en banner rechazo)<br>**Cobrar** (en tarjeta caja) | • **Ver:** carga `/caja` (200 OK).<br>• **Cobrar:** rebota con 302 a `/caja/apertura`. | [`escenario-5-desktop.png`](evidencia/a12-a24/capturas-antes/escenario-5-desktop.png) | [`escenario-5-375.png`](evidencia/a12-a24/capturas-antes/escenario-5-375.png) |
| **6** | **Hay clases hoy con y sin lista** | Listado de clases con badges: verde ("5 presentes") o amarillo ("Sin lista"). | **Lista** (en cada clase) | • **Lista:** carga `/clases/{id}` (200 OK). | [`escenario-6-desktop.png`](evidencia/a12-a24/capturas-antes/escenario-6-desktop.png) | [`escenario-6-375.png`](evidencia/a12-a24/capturas-antes/escenario-6-375.png) |
| **7** | **Día sin nada (sin clases, sin deudores)** | 3 cuadros en cero. Tarjeta "Sin caja hoy". Cuadro "No hay clases programadas hoy". Deudas en 0. | **Cobrar** | • **Cobrar:** rebota con 302 a `/caja/apertura`. Pantalla mudo sin actividad. | [`escenario-7-desktop.png`](evidencia/a12-a24/capturas-antes/escenario-7-desktop.png) | [`escenario-7-375.png`](evidencia/a12-a24/capturas-antes/escenario-7-375.png) |

---

## 2. ETAPA 2 — Las Dos Opciones Propuestas

La pregunta fundamental del mostrador: **«Llego a trabajar, ¿qué hago ahora?»**

Ambas opciones cumplen estrictamente con:
- Tokens de color del design system (`var(--color-brand)`, `var(--color-success)`, `var(--color-warning)`, `var(--color-danger)`).
- Botones con **un solo verbo corto**: `Abrir`, `Cobrar`, `Registrar`, `Cerrar`, `Lista`, `Ver`.
- Sin librerías externas de CSS ni JavaScript inline.
- Sin inventar componentes.

---

### Opción 1 — Mínima y fiel a la estructura existente ("Próximo paso claro")

Conserva exactamente la disposición visual y grilla actual (Stats arriba, Tarjeta de estado en el medio, Clases y Alumnos abajo, Cajas al pie), pero corrige la lógica de guía y los botones:

1. **Sin caja abierta:**
   - La tarjeta pasa a titularse **"Sin caja abierta"** con borde `var(--color-warning)`.
   - Texto de guía: *"Abrí la caja y declará el cambio inicial para empezar a cobrar."*
   - Botón: **`Abrir`** (primario), enlace directo a `route('web.caja.apertura')`.
   - **Elimina de raíz el botón "Cobrar" y el rebote 302.**
2. **Cajón compartido con turno de otro compañero:**
   - El controlador detecta si el club ya tiene un turno abierto hoy por otro operativo.
   - Título: **"Caja en curso"**.
   - Texto: *"Abierta por Marcos Peña desde las 08:30. El cajón es compartido."*
   - Botones: **`Cobrar`**, **`Registrar`**, **`Detalle`**.
3. **Caja cerrada:**
   - Botón: **`Abrir`** (un solo verbo corto, apunta a `route('web.caja.apertura')` para abrir el siguiente turno si corresponde).
4. **Enlaces de Alumnos:**
   - "Con deuda" enlaza a `route('web.cobranza.index')` (Cobranza de mostrador).
   - "Posibles inactivos" enlaza a `route('web.revision-cobranza.index')`.

**Archivos que tocaría si se aprueba:**
1. `resources/views/operativo/dashboard.blade.php` (actualización de tarjeta de estado, botones y enlaces de alumnos).
2. `app/Http/Controllers/OperativoDashboardController.php` (detección de caja compartida del club y cálculo global del mostrador).

---

### Opción 2 — Estructura orientada al mostrador ("Flujo de atención")

Diseñada específicamente para la ergonomía del mostrador diario: **la acción requerida se coloca primero, arriba de todo**, y los números de recaudación pasan a ser datos de soporte:

1. **Tarjeta de Acción de Mostrador ARRIBA DE TODO (antes de las métricas):**
   - **Al llegar sin caja:** Tarjeta destacada con dot de marca:  
     **Inicio de mostrador — Cajón listo para iniciar**  
     *"Para cobrar cuotas o registrar movimientos en efectivo, primero declará el cambio inicial."*  
     Botón grande y claro: **`Abrir`** (a `/caja/apertura`).
   - **Con caja abierta (propia o compartida):** Tarjeta destacada con dot verde/amarillo:  
     **Mostrador activo — Caja abierta**  
     *"Abierta por vos a las 09:15"* (o por Marcos Peña).  
     Acciones directas de mostrador: **`Cobrar`** (primario), **`Registrar`** (secundario), **`Resumen`** / **`Detalle`**.
   - **Con caja cerrada:** Tarjeta de cierre con botón **`Abrir`** para el turno de la tarde.
2. **El trabajo diario en dos columnas al centro:**
   - Columna izquierda: **Clases de hoy** (ordenadas por horario, con botón `Lista`).
   - Columna derecha: **Atención a alumnos** (tarjetas de acceso directo con enlaces explícitos `Ir a cobranza →` e `Ir a revisión →`).
3. **Recaudación del día (Stats de soporte abajo):**
   - Tres tarjetas compactas con "Cobrado hoy", "Cobros registrados" y "Cajas del día" ubicadas debajo del bloque de trabajo, evitando que el mostrador abra con tres ceros gigantes que no guían qué hacer.

**Archivos que tocaría si se aprueba:**
1. `resources/views/operativo/dashboard.blade.php` (reordenamiento ergonómico del mostrador).
2. `app/Http/Controllers/OperativoDashboardController.php` (detección de caja compartida del club).

---

## 3. Comparación y Evidencia Fotográfica

Todas las capturas se generaron en Chromium headless sobre `wings_testing_gemini` y están disponibles para inspección interactiva en [`visor-comparacion.html`](evidencia/a12-a24/visor-comparacion.html).

### Galería de capturas por situación

| Situación | Actual (Antes) | Opción 1 (Mínima) | Opción 2 (Ergonómica) |
|---|---|---|---|
| **1. Recién llega (Desktop)** | [`escenario-1-desktop.png`](evidencia/a12-a24/capturas-antes/escenario-1-desktop.png) | [`opcion-1-escenario-1-desktop.png`](evidencia/a12-a24/capturas-propuestas/opcion-1-escenario-1-desktop.png) | [`opcion-2-escenario-1-desktop.png`](evidencia/a12-a24/capturas-propuestas/opcion-2-escenario-1-desktop.png) |
| **1. Recién llega (375)** | [`escenario-1-375.png`](evidencia/a12-a24/capturas-antes/escenario-1-375.png) | [`opcion-1-escenario-1-375.png`](evidencia/a12-a24/capturas-propuestas/opcion-1-escenario-1-375.png) | [`opcion-2-escenario-1-375.png`](evidencia/a12-a24/capturas-propuestas/opcion-2-escenario-1-375.png) |
| **2. Turno propio (Desktop)** | [`escenario-2-desktop.png`](evidencia/a12-a24/capturas-antes/escenario-2-desktop.png) | [`opcion-1-escenario-2-desktop.png`](evidencia/a12-a24/capturas-propuestas/opcion-1-escenario-2-desktop.png) | [`opcion-2-escenario-2-desktop.png`](evidencia/a12-a24/capturas-propuestas/opcion-2-escenario-2-desktop.png) |
| **3. Turno compañero (Desktop)** | [`escenario-3-desktop.png`](evidencia/a12-a24/capturas-antes/escenario-3-desktop.png) | [`opcion-1-escenario-3-desktop.png`](evidencia/a12-a24/capturas-propuestas/opcion-1-escenario-3-desktop.png) | [`opcion-2-escenario-3-desktop.png`](evidencia/a12-a24/capturas-propuestas/opcion-2-escenario-3-desktop.png) |
| **4. Turno cerrado (Desktop)** | [`escenario-4-desktop.png`](evidencia/a12-a24/capturas-antes/escenario-4-desktop.png) | [`opcion-1-escenario-4-desktop.png`](evidencia/a12-a24/capturas-propuestas/opcion-1-escenario-4-desktop.png) | [`opcion-2-escenario-4-desktop.png`](evidencia/a12-a24/capturas-propuestas/opcion-2-escenario-4-desktop.png) |
| **5. Caja rechazada (Desktop)** | [`escenario-5-desktop.png`](evidencia/a12-a24/capturas-antes/escenario-5-desktop.png) | [`opcion-1-escenario-5-desktop.png`](evidencia/a12-a24/capturas-propuestas/opcion-1-escenario-5-desktop.png) | [`opcion-2-escenario-5-desktop.png`](evidencia/a12-a24/capturas-propuestas/opcion-2-escenario-5-desktop.png) |
| **6. Clases sin lista (Desktop)** | [`escenario-6-desktop.png`](evidencia/a12-a24/capturas-antes/escenario-6-desktop.png) | [`opcion-1-escenario-6-desktop.png`](evidencia/a12-a24/capturas-propuestas/opcion-1-escenario-6-desktop.png) | [`opcion-2-escenario-6-desktop.png`](evidencia/a12-a24/capturas-propuestas/opcion-2-escenario-6-desktop.png) |
| **7. Día sin nada (Desktop)** | [`escenario-7-desktop.png`](evidencia/a12-a24/capturas-antes/escenario-7-desktop.png) | [`opcion-1-escenario-7-desktop.png`](evidencia/a12-a24/capturas-propuestas/opcion-1-escenario-7-desktop.png) | [`opcion-2-escenario-7-desktop.png`](evidencia/a12-a24/capturas-propuestas/opcion-2-escenario-7-desktop.png) |

---

## 4. Recomendación de Gemini

**Recomiendo la Opción 2 porque al llegar al club pone inmediatamente a la vista el estado del cajón y el botón para empezar a operar, ubicando los números de recaudación en su lugar natural de balance al pie de la jornada.**
