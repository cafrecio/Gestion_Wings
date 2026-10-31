# Verificación T96 — Botón «Configurar» en Caja para el dueño

- **Tarea:** `T96` — Botón Configurar en Caja para el dueño, inhabilitado con una caja abierta
- **Implementación controlada:** Claude (`c38e38fa` / `459db67a`)
- **Verificador:** Gemini (`LOG GEM CAB`)
- **Fecha:** 31/10/2026
- **Base utilizada:** `wings_testing_gemini` (sin tocar la base local de trabajo ni desplegar al servidor)
- **Dictamen:** **DEVUELTA A CLAUDE (`estado=devuelto`, `tiene=Claude`)** — Los 9 casos del botón, permisos, manual, fichas y suite rápida están en orden, pero el control al peso de **cambio retenido + cambio de cuenta + Cashflow** detectó **1 hallazgo grave** (`H1`) de descuadre contable entre cuentas en Cashflow.

---

## 1. Control de los 9 casos (navegador Chrome + pedidos directos)

Se ejecutaron los 9 casos sobre `wings_testing_gemini` despachando las solicitudes reales contra el kernel HTTP de Laravel y verificando el DOM renderizado en Chrome headless vía CDP (`scripts/pruebas/cdp-helper.mjs`).

| # | Caso | Resultado esperado | Resultado comprobado | Estado |
|---|---|---|---|---|
| 1 | Dueño, mostrador configurado, ninguna caja abierta | `Configurar` habilitado y abre «Efectivo del mostrador»; `Abrir` sigue yendo a la apertura | En `GET /caja` (`HTTP 200`), la fila «Cajón del mostrador» renderiza dos enlaces habilitados: `<a class="ds-btn ds-btn--secondary" href=".../caja/configuracion">Configurar</a>` y `<a class="ds-btn ds-btn--primary" href=".../caja/apertura">Abrir</a>`. Al abrir `/caja/configuracion` (`HTTP 200`) el `<h1>` es `Efectivo del mostrador`, el selector `#tipo_caja_id` está habilitado con la cuenta actual seleccionada y aparece el botón `Guardar`. Al abrir `/caja/apertura` (`HTTP 200`) el `<h1>` es `Apertura de caja`. | **OK (Comprobado)** |
| 2 | Cambiar la cuenta y guardar | «Medio de efectivo configurado.»; quedan autor y fecha en `caja_mostrador`; la caja siguiente abre con la cuenta nueva | `POST /caja/configuracion` con `tipo_caja_id=6` (`Efectivo Alternativo`) responde `HTTP 302` hacia `/caja` con flash `success = "Medio de efectivo configurado."`. En `caja_mostrador` (`id=1`) quedan grabados `tipo_caja_id=6`, `configurado_por_id=1` (Laura Gómez) y `configurado_at=2026-10-31 06:24:43`. La caja abierta a continuación (`Caja #1`) queda con `tipo_caja_efectivo_id=6`. | **OK (Comprobado)** |
| 3 | Dueño, con una caja abierta de cualquier operativo | `Configurar` inhabilitado, sin enlace; al pasar el mouse explica el motivo | Con una caja abierta de la operativa Sandra Ríos, en `GET /caja` (`HTTP 200`) `Configurar` se renderiza como `<button class="ds-btn ds-btn--secondary" title="No se puede cambiar el medio del cajón mientras haya una caja abierta." type="button" disabled aria-disabled="true">Configurar</button>` (sin `href`), mientras `Abrir` sigue siendo `<a href=".../caja/apertura">Abrir</a>`. | **OK (Comprobado)** |
| 4 | Dueño, con caja abierta, escribiendo la dirección | Selector inhabilitado, aviso y sin `Guardar` | En `GET /caja/configuracion` (`HTTP 200`) con caja abierta, aparece la alerta `.ds-alert--warning` con «No se puede cambiar el medio del cajón mientras haya una caja abierta. Cerrá la caja abierta antes de cambiarlo.», `#tipo_caja_id` tiene `disabled=true` y en el formulario solo se renderiza `<a href=".../caja">Volver</a>` (no existe botón `Guardar`). | **OK (Comprobado)** |
| 5 | `POST` directo con otra cuenta y una caja abierta | Rechaza con «No se puede cambiar el medio del cajón mientras haya una caja abierta. Cerrá la caja abierta antes de cambiarlo.»; la cuenta no cambia | `POST /caja/configuracion` enviando `tipo_caja_id=1` mientras hay una caja abierta devuelve `HTTP 302` con `session('error') = "No se puede cambiar el medio del cajón mientras haya una caja abierta. Cerrá la caja abierta antes de cambiarlo."`. En base de datos, `caja_mostrador.tipo_caja_id` permanece en `6` sin cambios. | **OK (Comprobado)** |
| 6 | Dueño, mostrador sin configurar | Un solo botón, `Configurar`; `Abrir` no aparece | Con `caja_mostrador.tipo_caja_id = null`, `GET /caja` (`HTTP 200`) muestra en la fila «Cajón del mostrador» un único botón: `<a class="ds-btn ds-btn--primary" href=".../caja/configuracion">Configurar</a>`. El botón `Abrir` no se renderiza. | **OK (Comprobado)** |
| 7 | Operativa en Caja | No ve `Configurar`; por dirección y por `POST` directo recibe `403` | En `GET /caja` (`HTTP 200`) como operativa (`Sandra Ríos`), no existe la fila «Cajón del mostrador», ni el texto `Configurar`, ni ningún enlace a `/caja/configuracion`. `GET /caja/configuracion` devuelve `HTTP 403` y `POST /caja/configuracion` devuelve `HTTP 403` sin alterar `caja_mostrador`. | **OK (Comprobado)** |
| 8 | Profesora | No llega a esta función; anotá qué responde | En `GET /clases` (`HTTP 200`) como profesora (`Lucía Méndez`), la barra de navegación lateral solo incluye `Clases` y `Ayuda` (sin enlace a `/caja`). Por pedido directo, `GET /caja`, `GET /caja/configuracion` y `POST /caja/configuracion` responden todos **`HTTP 403`** mostrando la vista `errors/403` con título `Sin permiso` («No podés entrar a esta sección») y botón `Volver` a `/clases`. | **OK (Comprobado)** |
| 9 | Se abre una caja mientras el dueño tiene abierta la pantalla de configuración, y después guarda otra cuenta | Rechaza y la cuenta no cambia | 1) El dueño abre `GET /caja/configuracion` sin cajas abiertas (`HTTP 200`, formulario con botón `Guardar` habilitado). 2) Antes de que guarde, otro operativo abre `Caja #2` (`estado = ABIERTA`). 3) El dueño envía `POST /caja/configuracion` con otra cuenta: el servidor rechaza con `HTTP 302` y `session('error') = "No se puede cambiar el medio del cajón mientras haya una caja abierta. Cerrá la caja abierta antes de cambiarlo."`, y `caja_mostrador.tipo_caja_id` permanece sin cambios en `6`. | **OK (Comprobado)** |

---

## 2. Hallazgo en Cashflow al cambiar la cuenta con cambio retenido en el cajón

### H1 — [Grave] Cambiar la cuenta del mostrador cuando la caja anterior dejó `cambio_retenido > 0` descuadra los saldos por cuenta en Cashflow

- **Gravedad:** **Grave** (altera saldos monetarios por cuenta en Cashflow y permite gastar en la cuenta nueva efectivo que contablemente sigue imputado en la cuenta vieja).
- **Archivos y líneas involucradas:**
  - `app/Services/CajaService.php:42-63` (`configurarMostrador`: solo bloquea si existe una caja en estado `ABIERTA`, pero permite cambiar `caja_mostrador.tipo_caja_id` cuando la última caja cerrada dejó `cambio_retenido > 0` sin transferir ese saldo ni exigir cajón en `$0`).
  - `app/Services/CajaService.php:65-74` (`propuestaApertura`: toma `CajaOperativa::whereNotNull('cambio_retenido')->orderByDesc('id')->first()` sin verificar si `ultimaCaja->tipo_caja_efectivo_id` coincide con el nuevo `tipo_caja_id` del mostrador, proponiendo heredar en la `Cuenta B` el `cambio_retenido` que quedó contabilizado en la `Cuenta A`).
  - `app/Services/CajaService.php:127-135`, `196-206` y `609-616` (`abrirCajaOperativa`, `arqueoCaja` y `exigirEfectivoDisponible`: suman `efectivo_inicial` heredado al disponible físico y al esperado del turno bajo `Cuenta B`).
  - `app/Services/CashflowIntegracionCajaService.php:23-90` (`reflejarCajaEnCashflow`: registra en Cashflow cada movimiento operativo en el `tipo_caja_id` en que se cargó y solo genera ajuste por `diferencia_efectivo`, sin registrar ningún traspaso entre `Cuenta A` y `Cuenta B` por el `cambio_retenido` que cambió de cuenta al reconfigurar el mostrador).

#### Prueba ejecutada al peso en `wings_testing_gemini`

Partiendo de dos cuentas de efectivo con `saldo_inicial = $0,00` y sin movimientos previos:
- **Cuenta A:** `#1 Efectivo` (`saldo_inicial = $0,00`)
- **Cuenta B:** `#6 Efectivo Alternativo` (`saldo_inicial = $0,00`)

1. **Turno 1 en `Cuenta A` (`Caja #1`):**
   - El mostrador está configurado con `Cuenta A`.
   - Se abre `Caja #1` con `efectivo_inicial = $0,00`.
   - Ingresa efectivo por **`$30.000,00`** en `Cuenta A`.
   - Se cierra `Caja #1` con `efectivo_contado = $30.000,00`, dejando **`cambio_retenido = $10.000,00`** en el cajón y retirando `efectivo_retirado = $20.000,00` (`diferencia_efectivo = $0,00`).
   - El dueño verifica el movimiento y valida `Caja #1`.
   - **Estado en Cashflow tras validar `Caja #1`:**
     - `Cuenta A (Efectivo)`: **`+$30.000,00`** (`$20.000,00` retirados + `$10.000,00` que siguen en el cajón).
     - `Cuenta B (Efectivo Alternativo)`: **`$0,00`**.

2. **Cambio de cuenta del mostrador (habilitado por el nuevo botón `Configurar`):**
   - Sin ninguna caja abierta, el dueño entra a `Caja → Configurar` y cambia la cuenta del mostrador de `Cuenta A` a `Cuenta B`.
   - `CajaService::propuestaApertura()` devuelve:
     ```json
     {"tipo_caja_id":6,"tipo_caja_nombre":"Efectivo Alternativo","efectivo_heredado":10000,"caja_origen_id":1}
     ```

3. **Turno 2 en `Cuenta B` (`Caja #2`):**
   - Se abre `Caja #2` con `tipo_caja_efectivo_id = 6` (`Cuenta B`), `caja_origen_id = 1` y `efectivo_inicial = $10.000,00` (heredados del cajón de `Caja #1`).
   - En `Caja #2` entran **`$5.000,00`** en efectivo (`Cuenta B`) y se paga un gasto en efectivo de **`$8.000,00`** (`Cuenta B`), que `exigirEfectivoDisponible` permite porque en el cajón hay `$10.000,00` (inicial) + `$5.000,00` (ingreso) = `$15.000,00`.
   - Se cierra `Caja #2` con `efectivo_esperado = $7.000,00` (`10.000 + 5.000 - 8.000`), `efectivo_contado = $7.000,00`, `cambio_retenido = $7.000,00`, `efectivo_retirado = $0,00` y `diferencia_efectivo = $0,00`.
   - El dueño verifica los dos movimientos y valida `Caja #2`.

4. **Saldos resultantes en Cashflow (`ReporteMensualService::saldosPorCaja`, `CashflowIntegracionCajaService::saldoAcumuladoHastaFecha` y pantalla `GET /cashflow` en Chrome):**

| Cuenta | Saldo en Cashflow (`GET /cashflow`) | Realidad física / operativa | Desfase |
|---|---:|---:|---:|
| `Cuenta A (Efectivo)` | **`$30.000,00`** | `$20.000,00` retirados en `Caja #1` + `$0,00` en el cajón (el cajón pasó a operar en `Cuenta B` y ya consumió `$3.000,00` de aquel cambio) | **`+$10.000,00` de más** |
| `Cuenta B (Efectivo Alternativo)` | **`-$3.000,00`** | `$7.000,00` físicos contados y retenidos en el cajón al validar `Caja #2` | **`-$10.000,00` de menos (queda negativa)** |
| **Suma de ambas cuentas** | **`$27.000,00`** | **`$27.000,00`** (`$20.000,00` retirados + `$7.000,00` en el cajón) | `$0,00` en el total global, pero **`$10.000,00` corridos de cuenta** |

- **Por qué ocurre:** En Wings, el `cambio_retenido` de una caja no genera un egreso al cerrar ni un ingreso al abrir la siguiente porque, mientras el mostrador usa siempre la misma cuenta, esos billetes nunca cambian de `tipo_caja_id` en Cashflow. Pero cuando el mostrador cambia de `Cuenta A` a `Cuenta B` habiendo `cambio_retenido > 0`, `Caja #2` toma esos `$10.000,00` como `efectivo_inicial` disponible para gastar o retirar bajo `Cuenta B`, mientras en `cashflow_movimientos` los `$10.000,00` jamás salieron de `Cuenta A` ni entraron a `Cuenta B`.

---

## 3. Controles adicionales solicitados

### 3.1. Manual `resources/manual/admin/configurar-mostrador.md` y `verificar-manual.php`

- Se ejecutó `php scripts/manual/verificar-manual.php`: **`OK: 59 páginas revisadas, 0 problemas`**.
- Se contrastó `resources/manual/admin/configurar-mostrador.md` condición por condición contra el código (`docs/06-pruebas/PRU-04/REVISOR-MANUAL.md` punto 6):
  - **Ruta para llegar:** `Caja` en el menú lateral y luego `Configurar` en la fila **Cajón del mostrador** (`resources/views/caja/index.blade.php:46-52`). Cierto.
  - **Botones según estado del mostrador:** si ya tenía cuenta elegida muestra `Configurar` y `Abrir`; si todavía no tenía cuenta elegida muestra solo `Configurar` (`resources/views/caja/index.blade.php:47-51`). Cierto.
  - **Cuentas que ofrece el selector:** tipos de caja activos salvo los que contienen `mercado pago` o `banco` (`CajaWebController.php:190-192`, `tiposElegiblesParaMostrador`). Cierto.
  - **Bloqueo con caja abierta:** botón `Configurar` inhabilitado en `/caja` (`resources/views/caja/index.blade.php:48`), selector inhabilitado y sin `Guardar` en `/caja/configuracion` (`resources/views/caja/configuracion.blade.php:10-33`) y rechazo en `CajaService::configurarMostrador` (`CajaService.php:52-55`). Cierto.
  - **Efecto sobre cajas cerradas:** las cajas cerradas o validadas conservan su `tipo_caja_efectivo_id` propio (`CajaService.php:129` asigna la cuenta al abrir cada caja nueva). Cierto.

### 3.2. Fichas de fuentes en `docs/06-pruebas/PRU-04/evidencia-t12-t13/fuentes/`

- **`admin-configurar-mostrador.md`:** todas las citas verificadas contra el código actual:
  - `app/Http/Controllers/CajaWebController.php:L3-L192` (`index`, `configuracion`, `guardarConfiguracion`, `tiposElegiblesParaMostrador`). Coincide línea por línea.
  - `app/Services/CajaService.php:L40-L150` (`mostradorConfigurado`, `configurarMostrador`, `propuestaApertura`, `abrirCajaOperativa`). Coincide línea por línea.
  - `resources/views/caja/configuracion.blade.php`, `resources/views/caja/index.blade.php`, `routes/web.php:L45-L95`, `docs/02-contratos/REGLAS-NEGOCIO.md` y `docs/02-contratos/FLUJOS-CRITICOS.md`. Coinciden.
- **`admin-administrar-profesores.md` (citas regeneradas de T95):**
  - `app/Http/Controllers/ProfesorWebController.php:L3-L320` (incluye `exigirLiquidacionCerradaSinSaldosPendientes` en `L305-L319` y su invocación en `update` `L175` y `destroy` `L233`). Coincide exactamente con el archivo real (`320` líneas).
  - `app/Services/LiquidacionService.php:L15-L120`, `L175-L265`, `L380-L539` y `app/Services/ProfesorEstadoService.php`. Coinciden línea por línea.

### 3.3. Control de mutación sobre las 4 pruebas nuevas (`tests/Feature/MedioPagoCajonTest.php:210-257`)

Se verificó que las 4 pruebas nuevas fallan si se retira el botón o la condición de caja abierta, restaurando luego el código intacto:

1. **Mutación 1 — Quitar el botón `Configurar` cuando el mostrador ya está configurado (`resources/views/caja/index.blade.php:48-50`):**
   - `test_caja_ofrece_configurar_al_duenio_con_el_mostrador_ya_configurado`: **FALLÓ** (`Failed asserting that ... contains "/caja/configuracion"`).
   - `test_caja_deja_configurar_inhabilitado_con_una_caja_abierta`: **FALLÓ** (`Failed asserting that ... matches PCRE pattern "/<button[^>]*disabled[^>]*>\s*Configurar\s*<\/button>/"`).
2. **Mutación 2 — Quitar `:disabled="$hayCajaAbierta"` y el botón `Configurar` sin mostrador configurado (`resources/views/caja/index.blade.php:48-51`):**
   - `test_caja_deja_configurar_inhabilitado_con_una_caja_abierta`: **FALLÓ** (el botón deja de ser `<button disabled>` y pasa a ser un enlace activo).
   - `test_caja_sin_mostrador_configurado_solo_ofrece_configurar`: **FALLÓ** (`Failed asserting that ... contains "/caja/configuracion"`).
3. **Mutación 3 — Sacar `Configurar` fuera de `@if(Auth::user()->isAdmin())`:**
   - `test_operativo_no_ve_configurar_en_caja`: **FALLÓ** (`Failed asserting that ... does not contain "/caja/configuracion"`).
4. Se restauró `resources/views/caja/index.blade.php` y se comprobó `git diff --stat -- resources/views resources/css` vacío y las 17 pruebas de `MedioPagoCajonTest` en verde (`17 passed, 50 assertions`).

### 3.4. Suite rápida completa en `wings_testing_gemini`

Se verificó que no hubiera ningún otro proceso `php.exe` corriendo pruebas y se ejecutó la suite rápida completa en `wings_testing_gemini`:

```text
$env:DB_DATABASE='wings_testing_gemini'; php artisan test --exclude-group=lento
Tests:    6 skipped, 1178 passed (8850 assertions)
Duration: 262.29s
```

---

## 4. Separación explícita: Comprobado vs. Inferido

### Comprobado (ejecutado contra `wings_testing_gemini` y navegador Chrome)
- Los 9 casos de la tabla (renderizado en Chrome de `/caja`, `/caja/configuracion`, `/caja/apertura`, `/clases`, `errors/403` y `/cashflow`, además de las respuestas HTTP y el estado de `caja_mostrador` y `cajas_operativas` en base de datos).
- El escenario completo en Cashflow al peso (`Caja #1` en `Cuenta A` con `$30.000,00` de ingreso y `$10.000,00` de `cambio_retenido` → cambio de mostrador a `Cuenta B` → `Caja #2` en `Cuenta B` que hereda `$10.000,00`, ingresa `$5.000,00`, gasta `$8.000,00` y retiene `$7.000,00` → saldos finales en Cashflow: `Cuenta A = $30.000,00` y `Cuenta B = -$3.000,00`).
- La coincidencia línea por línea de `resources/manual/admin/configurar-mostrador.md`, `admin-configurar-mostrador.md` y `admin-administrar-profesores.md` contra el código, y `php scripts/manual/verificar-manual.php` en verde.
- La falla de las 4 pruebas nuevas de `MedioPagoCajonTest` ante las mutaciones de vista y su pase al restaurar el código.
- La corrida íntegra en verde de `php artisan test --exclude-group=lento` en `wings_testing_gemini` (`1178 passed, 6 skipped`).

### Inferido
- Nada: todas las afirmaciones de este informe provienen de ejecuciones directas sobre `wings_testing_gemini`, inspección del DOM en Chrome y lectura directa de los archivos citados.
