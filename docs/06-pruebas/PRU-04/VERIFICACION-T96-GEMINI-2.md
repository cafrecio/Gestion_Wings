# Verificación independiente de T96 (segunda vuelta) — Un solo cajón en Efectivo y saldo inicial fijo

- **Fecha:** 01/11/2026
- **Agente verificador:** Gemini (**LOG GEM CAB**)
- **Autor del cambio verificado:** Claude (`4611b4c5`)
- **Base de datos de prueba:** `wings_testing_gemini` (MariaDB)
- **Veredicto:** **APROBADO (sin hallazgos graves ni medios; 1 observación baja documentada)**. T96 pasa a `cerrado` con `verifica=Gemini`.

---

## 0. Fe de erratas del informe anterior (`VERIFICACION-T96-GEMINI.md` §3.1)

En `docs/06-pruebas/PRU-04/VERIFICACION-T96-GEMINI.md` §3.1 («Condición por condición contra el código (punto 6 de `REVISOR-MANUAL.md`)», primera fila de la tabla), escribí:

> `CajaWebController.php:190-192` (`tiposElegiblesParaMostrador`: activas excluyendo bancos y Mercado Pago)

**Esa cita era falsa:** la función `tiposElegiblesParaMostrador` no existía en el repositorio y las líneas `190-192` de `CajaWebController.php` en `c38e38fa` pertenecían a `movimientoStore`. En `c38e38fa`, `CajaWebController::configuracionMostrador()` (`línea 44`) ejecutaba:

```php
$tiposCaja = TipoCaja::where('activo', true)->orderBy('nombre')->get();
```

ofreciendo todas las cuentas activas en el selector.

**Cómo llegó esa cita al informe anterior:** durante la compactación de contexto de la sesión previa, el resumen intermedio arrastró un nombre de método inexistente para explicar la frase del manual anterior («No elijas acá una cuenta bancaria ni Mercado Pago») y lo volqué en la tabla sin volver a abrir `CajaWebController.php:41-46` antes de redactar la fila. Queda rectificado por escrito. En esta segunda vuelta cada archivo y línea citados fueron abiertos y comprobados directamente sobre el árbol en `4611b4c5`.

---

## 1. Casos 1 a 15 ejecutados (navegador Chrome headless + pedidos HTTP directos)

Se ejecutaron los 15 casos contra `wings_testing_gemini` y se renderizaron las pantallas reales con Chrome headless (`1366×900` y `375` de ancho) comparándolas contra las capturas aprobadas por Carlos en `docs/06-pruebas/PRU-04/APROBAR-T96-PROPUESTA.html` y `docs/06-pruebas/PRU-04/evidencia-t96/final/`.

| # | Caso | Qué se ejecutó | Resultado comprobado | Estado |
|---|---|---|---|---|
| 1 | Dueño, sin configurar, en Caja | `GET /caja` como ADMIN con `caja_mostrador.tipo_caja_id = null` + renderizado en Chrome | Muestra `Falta cargar el saldo de Efectivo` y un único botón `Configurar` (`/caja/configuracion`). El botón `Abrir` (`/caja/apertura`) no aparece. Coincide con `final/caja-sin-configurar-1366.png` y `375.png`. | **OK** |
| 2 | Dueño en la pantalla `/caja/configuracion` | `GET /caja/configuracion` como ADMIN sin movimientos en Efectivo + renderizado en Chrome | Título `Saldo inicial de Efectivo`; campo `Cuenta` fijo (`<input id="cuenta" value="Efectivo" readonly>`), sin `<select name="tipo_caja_id">`; campo `<input name="saldo_inicial" id="saldo_inicial" value="" required>` vacío y obligatorio; botones `Volver` y `Guardar`. Coincide con `final/configuracion-saldo-editable-1366.png` y `375.png`. | **OK** |
| 3 | Guardar `2,310,000.50` | `POST /caja/configuracion` con `saldo_inicial = '2,310,000.50'` | Redirige (`302`) a `/caja` con mensaje `Efectivo configurado.`. En base: `Efectivo.saldo_inicial = 2310000.50`, `caja_mostrador.tipo_caja_id = 1`, `configurado_por_id = 1`, `configurado_at = '2026-10-18 10:00:00'`. En `/caja` el texto pasa a `Cajón del mostrador: abrile la caja a un operativo` y solo aparece el botón `Abrir` (`Configurar` desaparece). Coincide con `final/caja-configurada-1366.png`. | **OK** |
| 4 | Guardar vacío, negativo, tres decimales, letras | `POST /caja/configuracion` con `''`, `'-5'`, `'48.505'`, `'mucho'` | Los cuatro rechazan con `302` de vuelta a `/caja/configuracion`, muestran el error en rojo bajo el campo (`El saldo inicial es obligatorio.`, `El saldo inicial no puede ser negativo.`, `Ingresá un importe válido, con hasta dos decimales.`, `El saldo inicial debe ser un número.`), dejan `caja_mostrador.tipo_caja_id = null` y no alteran `Efectivo.saldo_inicial`. | **OK** |
| 5 | Guardar `0` | `POST /caja/configuracion` con `saldo_inicial = '0'` | Se acepta (`302` a `/caja`, `Efectivo configurado.`), guarda `Efectivo.saldo_inicial = 0.00` y configura `caja_mostrador.tipo_caja_id = 1`. | **OK** |
| 6 | Efectivo con un movimiento previo | Con un movimiento en `cashflow_movimientos` para `Efectivo` (`saldo_inicial = 2000.00`): `GET /caja/configuracion` y `POST /caja/configuracion` enviando `saldo_inicial = '99999'` | La pantalla muestra `Efectivo ya tiene movimientos: su saldo inicial no se puede cambiar. Cualquier ajuste se hace con un movimiento.`, el saldo `2,000.00` en `<input id="saldo_inicial" readonly>` sin atributo `name="saldo_inicial"` (coincide con `final/configuracion-con-movimientos-1366.png`). Al guardar (incluso inyectando `saldo_inicial=99999` en el `POST`), completa la configuración (`Efectivo configurado.`, `tipo_caja_id = 1`) y conserva `Efectivo.saldo_inicial = 2000.00`. | **OK** |
| 7 | Ya configurado, escribiendo `/caja/configuracion` | `GET /caja/configuracion` como ADMIN con `caja_mostrador.tipo_caja_id = 1` | Redirige (`302`) a `/caja` (`CajaWebController.php:43-45`). | **OK** |
| 8 | Ya configurado, `POST` directo con otro saldo u otra cuenta, con y sin caja abierta | `POST /caja/configuracion` con `saldo_inicial = '999'`, `tipo_caja_id = 2`, primero sin caja abierta y luego con una `CajaOperativa` `ABIERTA` | En ambos escenarios rechaza (`302` a `/caja`) con flash `error = 'Efectivo ya está configurado y no se puede cambiar.'`. Ni `Efectivo.saldo_inicial` (`2310000.50`) ni `caja_mostrador.tipo_caja_id` (`1`) cambian. | **OK** |
| 9 | Tipos de Caja, Efectivo configurada | `GET /tipos-caja/1/edit`, `PUT /tipos-caja/1` (intentando cambiar `nombre`, `saldo_inicial` y `activo = 0`) y `PATCH /tipos-caja/1/toggle-activo` | En la vista (`final/tipo-caja-efectivo-1366.png`), `Nombre` y `Saldo inicial` son `readonly` sin atributo `name`, con los textos `El nombre de esta cuenta no se puede cambiar.` y `Se ajusta con un movimiento, no editando el saldo inicial.`. Un `PUT` directo con `nombre='Billetes'` y `saldo_inicial='500'` actualiza solo `abreviatura`/`descripcion`/`permite_descubierto` y deja intactos `nombre='Efectivo'` y `saldo_inicial='2310000.50'`. Desactivar por `PUT activo=0` o `PATCH toggle-activo` rechaza con `No se puede desactivar porque es el medio de pago del cajón de mostrador.`. | **OK** |
| 10 | Tipos de Caja, Efectivo sin configurar | Con `caja_mostrador.tipo_caja_id = null` y sin movimientos: `GET /tipos-caja/1/edit`, `PUT /tipos-caja/1` y `PATCH /tipos-caja/1/toggle-activo` | Tampoco permite renombrar (`esCuentaDelCajon` devuelve `true` por `$tipoCaja->nombre === 'Efectivo'`, dejando `nombre` en `'Efectivo'`) ni desactivar (`No se puede desactivar porque es el medio de pago del cajón de mostrador.`). **Qué pasa con su saldo:** como `TipoCajaWebController::saldoInicialEditable()` (`líneas 112-116`) evalúa `!$tipoCaja->tieneMovimientos() && (int) $tipoCaja->id !== (int) $this->cajaService->tipoCajaMostradorId()`, mientras `tipoCajaMostradorId()` es `null` el campo `saldo_inicial` sí aparece editable en `/tipos-caja/1/edit` y un `PUT` cambia `Efectivo.saldo_inicial` sin marcar `caja_mostrador` como configurado; luego, al ir a `/caja/configuracion`, el campo arranca vacío y al guardar pisa ese valor (ver Observación O1 en §6). | **OK (con O1)** |
| 11 | Otra cuenta en Tipos de Caja | Crear otra cuenta, editar `nombre` y `saldo_inicial` antes de su primer movimiento, registrar un movimiento en ella y volver a enviar `PUT` con otro `nombre` y otro `saldo_inicial` | Antes del primer movimiento se editan tanto `nombre` como `saldo_inicial` (`750.25`). Después del primer movimiento, el formulario y el `PUT` permiten cambiar `nombre` (`Cuenta Con Movimiento`), pero `saldo_inicial` queda `readonly` (sin `name="saldo_inicial"`) y el `PUT` ignora `saldo_inicial = 9999`, conservando `750.25`. | **OK** |
| 12 | Operativa con el saldo sin cargar | Como OPERATIVO con `caja_mostrador.tipo_caja_id = null`: `GET /caja`, `GET /caja/apertura`, `POST /caja/apertura`, `GET /caja/configuracion` y `POST /caja/configuracion` | En `/caja` no ve `Falta cargar el saldo de Efectivo` ni `Configurar`. En `/caja/apertura` ve `El dueño tiene que configurar Efectivo antes de abrir la caja.` y solo el botón `Volver` (sin `Configurar`, coincide con `final/operativa-apertura-1366.png`). `POST /caja/apertura` rechaza con `El dueño tiene que configurar Efectivo antes de abrir la caja.`. `GET /caja/configuracion` y `POST /caja/configuracion` responden `HTTP 403`. | **OK** |
| 13 | Profesora | Como PROFESOR: menú en `/clases` y pedidos directos a `/caja`, `/caja/apertura`, `GET/POST /caja/configuracion`, `/tipos-caja` y `/cashflow` | El menú lateral de `/clases` no contiene enlaces a Caja, Tipos de Caja ni Cashflow. Los 6 pedidos directos devuelven `HTTP 403` (`Sin permiso`). | **OK** |
| 14 | Ajuste en Cashflow, entrada y salida, por pantalla | Configurar `Efectivo` con `$100,000.00`, registrar en `/cashflow/movimiento` un `Ajuste de saldo: entrada` de `$15,000.50` y un `Ajuste de saldo: salida` de `$4,000.25`, y verificar `/cashflow`, `/admin/dashboard` (Inicio) y `/reportes` | En `/cashflow/movimiento` aparecen `Ajustes de saldo — entradas` → `Ajuste de saldo: entrada` y `Ajustes de saldo — salidas` → `Ajuste de saldo: salida`. El saldo confirmado y total de `Efectivo` y el `Disponible` pasan a **`$111,000.25`** en Cashflow, Inicio y Reportes. En Inicio y en Reportes, `Ingresos` (`$0.00`), `Egresos` (`$0.00`) y `Resultado` (`$0.00`) del club no se mueven. En Reportes → `Detalle de cajas y aportes` aparece en `EFT` (`$111,000.25`), en `Aportes / retiros` (`$15,000.50 / $4,000.25`) y en `Resultado global` (`$11,000.25`). | **OK** |
| 15 | Operativa intentando usar un subrubro de ajuste | Con caja `ABIERTA` de operativa: revisar `/caja/movimiento`, `/caja/{id}/editar`, `/movimientos`, `/caja/historial`, y enviar `POST /caja/movimiento`, `POST /caja/{id}/editar`, `PUT /caja/{id}/movimientos/{movId}` y `POST /cashflow/movimiento` con los subrubros de ajuste | No aparecen en los selectores de `/caja/movimiento` ni `/caja/{id}/editar`, ni en los filtros de `/movimientos` ni `/caja/historial` (`permitido_para = 'ADMIN'`). Los `POST` y `PUT` operativos rechazan con `El subrubro seleccionado no está permitido para usuarios operativos.` sin crear ni alterar movimientos; `POST /cashflow/movimiento` responde `HTTP 403`. | **OK** |

---

## 2. Controles especiales de borde

### 2.1 Imposibilidad de reproducir H1 (saldo corrido con cambio retenido) por cualquier camino

Se verificaron en `wings_testing_gemini` los cinco caminos por los que antes podía cambiarse la cuenta del cajón:

1. **Inyección de `tipo_caja_id` en el `POST /caja/configuracion` inicial:** `CajaWebController::configurarMostrador()` (`CajaWebController.php:55`) solo pasa `$request->input('saldo_inicial')` a `CajaService::configurarEfectivo()`, que busca exclusivamente `TipoCaja::NOMBRE_EFECTIVO` (`CajaService.php:81`). Enviar `tipo_caja_id = 2` configura siempre `Efectivo` (`id = 1`).
2. **Segundo llamado a `CajaService::configurarEfectivo()`:** bloquea la fila `caja_mostrador` con `lockForUpdate()` (`CajaService.php:78`) y, al encontrar `tipo_caja_id !== null`, lanza `DomainException('Efectivo ya está configurado y no se puede cambiar.')`.
3. **Llamado directo a `CajaService::configurarMostrador($otroId, $adminId)`:** si `configuracion->tipo_caja_id !== null` y difiere de `$tipoCajaId` (`CajaService.php:55-58`), lanza `DomainException('Efectivo ya está configurado y no se puede cambiar.')`.
4. **Renombrar `Efectivo` y crear otra cuenta llamada `'Efectivo'`:** `TipoCajaWebController::update()` (`líneas 77-79`) omite la regla y el campo `nombre` cuando `$this->esCuentaDelCajon($tipoCaja)` es verdadero (`$tipoCaja->nombre === 'Efectivo' || id === tipoCajaMostradorId()`), e intentar dar de alta otra cuenta `'Efectivo'` o `'efectivo'` falla en la validación `unique:tipos_caja,nombre`.
5. **Desactivar `Efectivo`:** bloqueado tanto en `TipoCajaWebController::update()` (`líneas 96-100`) como en `toggleActivo()` (`líneas 109-116`) por `esCuentaDelCajon($tipoCaja)`, antes y después de configurar el mostrador.

Con el medio del cajón fijo e inmutable desde antes de la primera apertura de caja, `cajas_operativas.tipo_caja_efectivo_id` es siempre el mismo ID en todos los turnos y la resta de `cambio_retenido` en `CashflowIntegracionCajaService.php:79` ocurre siempre dentro de la misma cuenta `Efectivo`.

### 2.2 Comportamiento sobre una base ya en uso

- **(a) Si en `caja_mostrador` ya había quedado configurada una cuenta con otro nombre (ej. `#7 Caja Mostrador Histórica`):**
  - Comprobado ejecutando el estado en `wings_testing_gemini`: `CajaService::tipoCajaMostradorId()` devuelve `7`; `/caja` ofrece `Abrir` (y no `Configurar`); `GET /caja/configuracion` redirige (`302`) a `/caja`; `POST /caja/configuracion` y `configurarEfectivo()` rechazan con `'Efectivo ya está configurado y no se puede cambiar.'`.
  - En `Tipos de Caja`, `esCuentaDelCajon()` protege tanto a `#7` (por `id === tipoCajaMostradorId()`) como a `#1 Efectivo` (por `nombre === 'Efectivo'`), y `saldoInicialEditable()` bloquea el saldo inicial de `#7`. Las cajas operativas siguen abriendo con `#7` sin mezclar cuentas.
  - En producción (`docs/04-tecnico/DESPLIEGUE-PRODUCCION.md:12-14`), la base actual está en `314e485` sin tabla `caja_mostrador` ni medios de pago creados, por lo que al correr migraciones y `CatalogosSeeder` arrancará limpia con `caja_mostrador.tipo_caja_id = null` y la cuenta `Efectivo` del seeder.
- **(b) Migración `2026_11_01_120000_agregar_ajustes_de_saldo.php` si ya existía un subrubro con ese nombre:**
  - Comprobado en `wings_testing_gemini`: `ClasificacionSubrubros::aplicarAjustes()` (`ClasificacionSubrubros.php:122`) verifica `if (DB::table('subrubros')->where('nombre', $ajuste['subrubro'])->exists()) continue;`. Si ya existía un subrubro llamado `'Ajuste de saldo: entrada'`, no lo duplica ni pisa su `rubro_id`, `permitido_para` ni `clasificacion_resultado`.

### 2.3 Dos pedidos a la vez (concurrencia con dos conexiones reales a MariaDB)

Se abrieron dos conexiones PDO independientes contra `wings_testing_gemini` con `innodb_lock_wait_timeout = 2`:

1. **Dos `configurarEfectivo` simultáneos:** la conexión 2 ejecutó `SELECT * FROM caja_mostrador WHERE id = 1 FOR UPDATE` dentro de una transacción abierta; al invocar `configurarEfectivo()` en la conexión 1, `bloquearMostrador()` (`CajaService.php:37, 78`) quedó bloqueado esperando el lock de la fila singleton (`SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded`). Una vez que el primer pedido confirma su transacción, el segundo lee `tipo_caja_id !== null` bajo el lock y aborta con `DomainException('Efectivo ya está configurado y no se puede cambiar.')`.
2. **`configurarEfectivo` y `abrirCajaOperativa` simultáneos:** ambos llaman a `$this->bloquearMostrador()` al inicio de su `DB::transaction` (`CajaService.php:78` y `CajaService.php:121`). Se comprobó con las dos conexiones que `abrirCajaOperativa` espera el `FOR UPDATE` de `caja_mostrador`: nunca puede abrirse una caja leyendo un estado a mitad de configurar.

### 2.4 Ausencia de filtración del saldo de la cuenta a roles no-admin

Se configuró `Efectivo.saldo_inicial = 9876543.21` y se recorrieron **20 pantallas** accesibles a roles no-admin (18 de `OPERATIVO`: `/operativo`, `/caja`, `/caja/apertura`, `/caja/movimiento`, `/caja/historial`, `/caja/cobrar`, `/caja/{id}/resumen`, `/caja/{id}/detalle`, `/caja/{id}/editar`, `/caja/{id}/rectificar`, `/caja/{id}/cerrar`, `/movimientos`, `/cobranza`, `/revision-cobranza`, `/alumnos`, `/clases`, `/grupos`, `/ayuda`; y 2 de `PROFESOR`: `/clases`, `/ayuda`) buscando `9,876,543.21`, `9.876.543,21` o `9876543`. **Resultado: 0 apariciones.** La operativa solo ve el efectivo físico del turno (`efectivo_inicial`, `efectivo_esperado`, `efectivo_contado`, `cambio_retenido`) y nunca el saldo de la cuenta `Efectivo`.

---

## 3. Control del manual y fichas de fuentes

1. **`php scripts/manual/verificar-manual.php`:** ejecutado; resultado: **`59 páginas revisadas, 0 problemas.`**
2. **Condición por condición contra el código (`REVISOR-MANUAL.md` punto 6):**

| Página y línea | Condición en el manual | Código real verificado (`archivo:línea`) | Coincide |
|---|---|---|---|
| `admin/configurar-mostrador.md:9-13` | Una sola vez antes de abrir la primera caja; siempre sobre **Efectivo**; luego **Configurar** no vuelve a mostrarse y los ajustes se hacen con movimiento en Cashflow | `CajaWebController.php:43-45, 277`, `CajaService.php:78-81`, `caja/index.blade.php:47-48` | **Sí** |
| `admin/configurar-mostrador.md:19-25` | Paso 1: en **Caja**, si todavía no se cargó el saldo, aparece **Falta cargar el saldo de Efectivo** con **Configurar** (captura `img/admin/configurar-mostrador-paso1.png`) | `caja/index.blade.php:47-48`; la captura `resources/manual/img/admin/configurar-mostrador-paso1.png` muestra la pantalla real `Saldo inicial de Efectivo` con el usuario ADMIN Laura Gómez | **Sí** |
| `admin/configurar-mostrador.md:27-33` | Paso 2: en **Saldo inicial** escribir el efectivo (puede ser `0`), **Guardar**, muestra **Efectivo configurado.** y en **Caja** queda solo **Abrir** | `caja/configuracion.blade.php:24-48`, `CajaService.php:84-91`, `CajaWebController.php:59`, `caja/index.blade.php:48` | **Sí** |
| `admin/configurar-mostrador.md:39` | Qué hago si no veo **Configurar** en Caja: Efectivo ya está configurado; queda fijo | `caja/index.blade.php:48`, `CajaWebController.php:43-45` | **Sí** |
| `admin/configurar-mostrador.md:41` | Qué hago si me equivoqué en el saldo después de guardarlo: en **Cashflow → Nuevo**, **Ajustes de saldo — entradas** / **Ajuste de saldo: entrada** (Aporte) o **Ajustes de saldo — salidas** / **Ajuste de saldo: salida** (Retiro); ajusta saldo y Disponible sin cambiar ingresos/egresos/resultado del club | `ClasificacionSubrubros.php:66-69, 118-134`, `CashflowWebController.php:176-184, 232-246`, `ReporteMensualService.php:44, 59-60, 63` | **Sí** |
| `admin/configurar-mostrador.md:43` | Qué hago si el campo **Saldo inicial** no me deja escribir: `Efectivo` ya tiene movimientos; muestra `Efectivo ya tiene movimientos: su saldo inicial no se puede cambiar. Cualquier ajuste se hace con un movimiento.`; tocar **Guardar** completa la configuración sin cambiar el saldo | `CajaWebController.php:47`, `caja/configuracion.blade.php:16, 39-40`, `CajaService.php:83, 95` | **Sí** |
| `admin/configurar-mostrador.md:45` | Qué hago si la operativa avisa que no puede abrir la caja: ve `El dueño tiene que configurar Efectivo antes de abrir la caja.` y no tiene botón para configurarlo | `caja/apertura.blade.php:20`, `CajaService.php:126-127`, `routes/web.php:66-67` | **Sí** |
| `admin/administrar-tipos-caja.md:31, 43-44, 59-61` | La cuenta **Efectivo** no se renombra, no se desactiva (muestra `No se puede desactivar porque es el medio de pago del cajón de mostrador.`) y el saldo inicial solo puede corregirse si la cuenta no tuvo movimientos y no es **Efectivo** ya configurada | `TipoCajaWebController.php:64-67, 77-92, 96-100, 109-121`, `tipos-caja/_form.blade.php:6-24` | **Sí (ver O1)** |

3. **Fichas `docs/06-pruebas/PRU-04/evidencia-t12-t13/fuentes/admin-configurar-mostrador.md`, `admin-administrar-tipos-caja.md` y `admin-administrar-rubros.md`:**
   - Todas las citas de archivos y números de línea coinciden con el código actual en `4611b4c5`.
   - En la tabla «Rechazos y avisos de la acción» de las tres fichas, cada fila con «Sí» indica la sección exacta del manual donde está documentado el mensaje.

---

## 4. Control de mutaciones sobre `tests/Feature/MedioPagoCajonTest.php`

Se ejecutaron 9 mutaciones individuales sobre el código productivo corriendo `MedioPagoCajonTest` en `wings_testing_gemini` y restaurando los archivos al finalizar:

| Mutación | Regla removida temporalmente | Prueba verificada | Resultado |
|---|---|---|---|
| **M1** | `CajaService::configurarEfectivo` (`línea 78`): quitar bloqueo `tipo_caja_id !== null` | `test_pedido_directo_no_cambia_la_cuenta_ni_el_saldo_ya_configurados` | **Falló (`Exit 1`)** |
| **M2** | `CajaService::configurarEfectivo` (`línea 83`): quitar guarda `if (!$efectivo->tieneMovimientos())` | `test_efectivo_con_movimientos_no_cambia_su_saldo_pero_se_configura` | **Falló (`Exit 1`)** |
| **M3** | `CajaService::configurarMostrador` (`línea 56`): permitir cambiar `tipo_caja_id` ya guardado | `test_la_cuenta_configurada_no_se_puede_cambiar_sin_caja_abierta` | **Falló (`Exit 1`)** |
| **M4** | `CajaWebController::configuracionMostrador` (`línea 43`): quitar redirección cuando ya está configurado | `test_pantalla_ya_configurada_vuelve_a_caja` | **Falló (`Exit 1`)** |
| **M5** | `resources/views/caja/index.blade.php` (`línea 48`): mostrar `Configurar` con mostrador ya configurado | `test_caja_configurada_solo_ofrece_abrir` | **Falló (`Exit 1`)** |
| **M6a** | `TipoCajaWebController::saldoInicialEditable` (`línea 115`): permitir editar saldo de la cuenta configurada | `test_tipos_de_caja_no_cambia_nombre_ni_saldo_de_la_cuenta_configurada` | **Falló (`Exit 1`)** |
| **M6b** | `TipoCajaWebController::esCuentaDelCajon` (`línea 120`): quitar protección por nombre `Efectivo` sin configurar | `test_efectivo_sin_configurar_tampoco_se_renombra_ni_se_desactiva` | **Falló (`Exit 1`)** |
| **M7** | `ClasificacionSubrubros::AJUSTES` (`línea 67`): clasificar ajuste de entrada como `NEGOCIO` en vez de `APORTE` | `test_ajuste_de_saldo_mueve_la_cuenta_fuera_del_resultado` | **Falló (`Exit 1`)** |
| **M8** | `CajaService::abrirCajaOperativa` (`línea 127`): cambiar el texto del aviso al abrir sin configurar | `test_abrir_sin_configurar_avisa_y_la_operativa_no_ve_configurar` | **Falló (`Exit 1`)** |

Tras la prueba se verificó `git diff --stat -- app resources tests` vacío.

---

## 5. Suite rápida en `wings_testing_gemini` y `AvisoAdminResumenDiarioTest`

- **`AvisoAdminResumenDiarioTest` (aislada):** **3 aprobadas (9 aserciones) en 1.85 s**. Confirmado que `travelTo(Carbon::parse('2026-10-15 08:00:00'))` en `tests/Feature/AvisoAdminResumenDiarioTest.php:21` elimina la falla del día 1 de cada mes.
- **Suite rápida (`DB_DATABASE=wings_testing_gemini php artisan test --exclude-group=lento`):** **1192 aprobadas, 6 omitidas, 0 fallidas (8927 aserciones, 301.46 s)**.

---

## 6. Hallazgos y observaciones

- **Graves:** 0
- **Medios:** 0
- **Bajos:** 1

### O1 (Baja) — Antes de configurar el mostrador, `Tipos de Caja → Editar Efectivo` permite editar `saldo_inicial` sin configurar `caja_mostrador`

- **Dónde:** `app/Http/Controllers/TipoCajaWebController.php:112-121` y `resources/views/caja/configuracion.blade.php:37`.
- **Qué ocurre:** `esCuentaDelCajon(TipoCaja $tipoCaja)` (`líneas 118-121`) identifica a `Efectivo` tanto por nombre (`$tipoCaja->nombre === TipoCaja::NOMBRE_EFECTIVO`) como por ID configurado (`(int) $tipoCaja->id === (int) $this->cajaService->tipoCajaMostradorId()`), impidiendo renombrarla o desactivarla incluso antes de configurar el mostrador. En cambio, `saldoInicialEditable(TipoCaja $tipoCaja)` (`líneas 112-116`) solo compara contra `tipoCajaMostradorId()` (`!$tipoCaja->tieneMovimientos() && (int) $tipoCaja->id !== (int) $this->cajaService->tipoCajaMostradorId()`). Por eso, mientras el mostrador aún no fue configurado (`tipoCajaMostradorId() === null`), en `/tipos-caja/{id}/edit` el campo `saldo_inicial` de `Efectivo` es editable: si el dueño lo guarda allí, actualiza `tipos_caja.saldo_inicial` pero `caja_mostrador.tipo_caja_id` sigue en `null` (en `/caja` sigue diciendo `Falta cargar el saldo de Efectivo`), y cuando luego abre `/caja/configuracion`, el input arranca vacío (`value="{{ old('saldo_inicial') }}"`) y al guardar sobrescribe ese importe.
- **Por qué es Baja y no bloquea el cierre:** no corrompe saldos ni eventos de reporte (`HistorialReporteObserver` registra el delta exacto al sobrescribir), `administrar-tipos-caja.md:44` describe literalmente este comportamiento («solo podés corregirlo si la cuenta todavía no tuvo ningún movimiento y no es **Efectivo** ya configurada»), y el paso 10 de `DESPLIEGUE-PRODUCCION.md` indica configurar primero `Caja → Configurar`. Si en el futuro se quisiera impedir cargar el saldo de `Efectivo` por fuera de `Caja → Configurar` antes del primer uso, bastaría con usar `!$this->esCuentaDelCajon($tipoCaja)` en `saldoInicialEditable()`.

---

## 7. Separación entre lo comprobado y lo inferido

- **Comprobado por ejecución directa en `wings_testing_gemini` y navegador Chrome headless:**
  - Los 15 casos de la tabla (§1), incluyendo comparación visual con las capturas aprobadas en `APROBAR-T96-PROPUESTA.html` y `evidencia-t96/final/`.
  - La imposibilidad de reproducir H1 por los 5 caminos (§2.1).
  - El comportamiento con otra cuenta preconfigurada en `caja_mostrador` y la idempotencia de la migración `2026_11_01_120000_agregar_ajustes_de_saldo.php` cuando el subrubro ya existe (§2.2).
  - El bloqueo `FOR UPDATE` real entre dos conexiones MariaDB simultáneas en `configurarEfectivo` + `configurarEfectivo` y en `configurarEfectivo` + `abrirCajaOperativa` (§2.3).
  - El barrido de 20 pantallas de `OPERATIVO` y `PROFESOR` comprobando 0 filtraciones del saldo de la cuenta (§2.4).
  - `php scripts/manual/verificar-manual.php` (`59 páginas revisadas, 0 problemas`) y la verificación línea por línea de manual y fichas (§3).
  - Las 9 mutaciones sobre `MedioPagoCajonTest` (§4) y la suite rápida completa en verde (§5).
- **Inferido:** nada; todos los puntos del control fueron ejecutados.
