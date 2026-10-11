# IMPLEMENTACIÓN T23 — Apertura de caja con aviso por diferencia grande y rectificación

**Resultado: IMPLEMENTADO Y EN ESPERA DE REVISIÓN VISUAL POR CARLOS Y VERIFICACIÓN POR OTRO AGENTE**

Estado en el tablero: `a_verificar` (hizo Gemini, tiene Carlos para aprobar capturas; después verifica otro agente la regla de negocio).

---

## 1. Qué cambió y decisiones aplicadas

Carlos decidió el 11/10/2026:
> *«Puede pasar que quien abre la caja tipee mal el efectivo recibido, por ejemplo un cero de más.*
> *Antes de confirmar la apertura, si lo declarado difiere más de 50%, para arriba o para abajo, de lo que dejó el cierre anterior, el sistema avisa de forma enfática.*
> *Si la persona sigue igual, puede abrir.*
> *Mientras la caja esté abierta, lo declarado se puede rectificar.*
> *Al rectificar, el efectivo esperado del turno se recalcula con el valor nuevo.*
> *Una caja cerrada o validada no se puede rectificar, tampoco por un pedido directo.*
> *No cambia nada del cierre, del arqueo ni de lo que recibe el turno siguiente.*
> *El contrato V5 queda actualizado con la regla nueva.»*

### Decisiones acordadas sobre puntos abiertos:
1. **Umbral del 50%:** Aplica estrictamente cuando la diferencia supera el 50% (`abs($inicial - $heredado) > 0.5 * $heredado`) y el cierre anterior dejó efectivo > $0.
2. **Primera apertura o cierre anterior en $0:** No emite aviso del 50%. En el caso de $0 solo rige el motivo habitual si el declarado es distinto de cero.
3. **Quién puede rectificar:** Tanto el operativo responsable del turno como el Administrador (`$caja->usuario_operativo_id === Auth::id() || Auth::user()->isAdmin()`).
4. **Auditoría de rectificación:** Se exige motivo obligatorio (`motivo_rectificacion`) y se conservan el valor inicial previo (`efectivo_inicial_anterior`), fecha (`rectificado_at`) y usuario que rectificó (`usuario_rectificacion_id`).
5. **Caja cerrada o validada:** Queda estrictamente bloqueada cualquier rectificación (incluso vía POST directo).

---

## 2. Archivos modificados y creados

1. **`database/migrations/2026_10_11_120000_add_rectificacion_to_cajas_operativas.php`:**
   - Agrega a `cajas_operativas`: `efectivo_inicial_anterior` (decimal 12,2), `motivo_rectificacion` (text), `rectificado_at` (timestamp) y `usuario_rectificacion_id` (foreignId `users` nullOnDelete).

2. **`app/Models/CajaOperativa.php`:**
   - Agrega los nuevos campos a `$fillable` y `$casts` (`rectificado_at` como datetime, `efectivo_inicial_anterior` como decimal:2).
   - Define la relación `usuarioRectificacion(): BelongsTo`.

3. **`app/Services/CajaService.php`:**
   - En `abrirCajaOperativa()`: valida que si lo declarado difiere más del 50% del cierre anterior (con heredado > 0), y no se ha confirmado mediante `confirmar_diferencia_grande`, se lance `ValidationException` requiriendo confirmación explícita. Si la diferencia es <= 50%, únicamente exige el motivo habitual.
   - En `rectificarApertura()`: método transaccional con bloqueo de mostrador y de turno. Valida que el estado sea estrictamente `ABIERTA`, verifica permisos de usuario (operativo del turno o admin), exige motivo obligatorio, respalda el importe inicial anterior, asigna el nuevo valor, registra autor y timestamp, y recalcula de inmediato el `efectivo_esperado` del turno usando `arqueoCaja()`.

4. **`app/Http/Controllers/CajaWebController.php`:**
   - En `abrir()`: normaliza importes y procesa la apertura con el nuevo validador.
   - Nuevos métodos `rectificar(int $id)` (GET) y `guardarRectificacion(Request $request, int $id)` (POST).
   - En `resumen(int $id)`: eager load de `usuarioRectificacion`.

5. **`routes/web.php`:**
   - Rutas añadidas: `GET /caja/{id}/rectificar` (`web.caja.rectificar`) y `POST /caja/{id}/rectificar` (`web.caja.rectificar.guardar`).

6. **`resources/views/caja/apertura.blade.php`:**
   - Al detectar `$errors->has('confirmar_diferencia_grande')`, muestra banner enfático con borde `var(--color-warning)` detallando el monto tipeado vs el importe del último cierre, alertando por tipeo erróneo de ceros, y presentando el checkbox de confirmación explícita: `Confirmo que el efectivo recibido difiere más del 50% del cierre anterior.`.
   - Sin JavaScript invasivo, 100% Blade server-rendered.

7. **`resources/views/caja/rectificar.blade.php` (nueva vista):**
   - Vista para corregir el importe inicial. Muestra responsable, fecha de apertura e inicial declarado actual.
   - Formulario con `<x-ds.money-input>` para el nuevo importe y campo requerido para el motivo de la rectificación.
   - Botones con verbos únicos: `Volver` y `Guardar`.

8. **`resources/views/caja/resumen.blade.php`:**
   - Agrega botón `Rectificar` visible cuando la caja se encuentra en estado `ABIERTA` para el operativo del turno o admin.
   - Exhibe bloque de auditoría si la caja fue rectificada: fecha, usuario que rectificó, importe inicial previo y motivo.

9. **`docs/02-contratos/Wings-Contrato-Caja-Cashflow-V5.md`:**
   - Contrato actualizado en tabla general y en la sección *Apertura y turnos* con las reglas de aviso por diferencia > 50% y rectificación de apertura.

---

## 3. Pruebas permanentes en `tests/Feature/CajaAperturaDiferenciaYRectificacionTest.php`

8 pruebas automáticas, todas pasando (41 aserciones, 100% PASS):
1. `test_aviso_por_diferencia_mayor_al_50_por_ciento_hacia_arriba`: Heredado $10.000, declarar $16.000 sin confirmación falla con error `confirmar_diferencia_grande`. Con confirmación abre OK.
2. `test_aviso_por_diferencia_mayor_al_50_por_ciento_hacia_abajo`: Heredado $10.000, declarar $4.000 sin confirmación falla. Con confirmación abre OK.
3. `test_sin_aviso_cuando_la_diferencia_no_supera_el_50_por_ciento`: Declarar $15.000 (+50% exacto), $5.000 (-50% exacto) o $12.000 (+20%) con motivo abre directo sin requerir confirmación de diferencia grande.
4. `test_sin_aviso_del_50_por_ciento_en_primera_apertura_o_cuando_cierre_dejo_cero`: Primera apertura (sin cierre) y apertura con cierre previo en $0 no emiten aviso del 50%.
5. `test_rectificacion_con_caja_abierta_por_operativo_y_por_admin`: Operativo y Admin pueden rectificar la caja abierta; guarda auditoría completa (`efectivo_inicial_anterior`, `motivo_rectificacion`, `rectificado_at`, `usuario_rectificacion_id`).
6. `test_rectificacion_exige_motivo_obligatorio`: Sin motivo, la rectificación falla validación y no modifica la caja.
7. `test_rechazo_de_rectificacion_cuando_caja_esta_cerrada_o_validada`: Intento de rectificar caja en estado CERRADA o VALIDADA (por web o servicio) es estrictamente rechazado.
8. `test_efectivo_esperado_se_recalcula_al_rectificar`: Con movimiento de ingreso activo, rectificar el inicial recalcula de inmediato el esperado del turno.

---

## 4. Capturas del sistema andando (Escritorio y Celular 360px con marco)

Guardadas en `docs/06-pruebas/PRU-04/evidencia-t23/`:

### Control de método (Login)
- **Escritorio (1366px):** [`docs/06-pruebas/PRU-04/evidencia-t23/login-control-desktop.png`](evidencia-t23/login-control-desktop.png)
- **Celular (360px con marco):** [`docs/06-pruebas/PRU-04/evidencia-t23/login-control-mobile-360.png`](evidencia-t23/login-control-mobile-360.png)

### Apertura con aviso enfático de diferencia grande (> 50%)
- **Operativo — Escritorio:** [`docs/06-pruebas/PRU-04/evidencia-t23/operativo-apertura-aviso-desktop.png`](evidencia-t23/operativo-apertura-aviso-desktop.png)
- **Operativo — Celular (360px):** [`docs/06-pruebas/PRU-04/evidencia-t23/operativo-apertura-aviso-mobile-360.png`](evidencia-t23/operativo-apertura-aviso-mobile-360.png)
- **Admin — Escritorio:** [`docs/06-pruebas/PRU-04/evidencia-t23/admin-apertura-aviso-desktop.png`](evidencia-t23/admin-apertura-aviso-desktop.png)
- **Admin — Celular (360px):** [`docs/06-pruebas/PRU-04/evidencia-t23/admin-apertura-aviso-mobile-360.png`](evidencia-t23/admin-apertura-aviso-mobile-360.png)

### Pantalla de Rectificación
- **Operativo — Escritorio:** [`docs/06-pruebas/PRU-04/evidencia-t23/operativo-rectificacion-desktop.png`](evidencia-t23/operativo-rectificacion-desktop.png)
- **Operativo — Celular (360px):** [`docs/06-pruebas/PRU-04/evidencia-t23/operativo-rectificacion-mobile-360.png`](evidencia-t23/operativo-rectificacion-mobile-360.png)
- **Admin — Escritorio:** [`docs/06-pruebas/PRU-04/evidencia-t23/admin-rectificacion-desktop.png`](evidencia-t23/admin-rectificacion-desktop.png)
- **Admin — Celular (360px):** [`docs/06-pruebas/PRU-04/evidencia-t23/admin-rectificacion-mobile-360.png`](evidencia-t23/admin-rectificacion-mobile-360.png)

---

## 5. Lo que no se probó

- No se probó interacción manual en un teléfono físico (se probó en marco de iframe a 360px de ancho real con Chrome sin ventana).
- No se modificó el componente global de importes `money-input.blade.php`, que está siendo trabajado por Codex en T24; se utilizó dicho componente sin alterar sus fuentes compartidas.
- No se tocó el servidor de producción ni el sitio de pruebas en la nube ni su reloj simulado.
