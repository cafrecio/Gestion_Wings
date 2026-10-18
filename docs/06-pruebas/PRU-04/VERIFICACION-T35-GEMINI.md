# Verificación independiente de T35 — La asistencia se toma desde el día de la clase

- **Tarea:** T35 (DU-35) — «No: solo desde el día de la clase»
- **Commit verificado:** `8f2be1f` (implementado por Claude; informe de origen: `docs/06-pruebas/PRU-04/IMPLEMENTACION-T35.md`)
- **Verificador:** Gemini (`LOG GEM CAB`)
- **Fecha de verificación:** 17/10/2026
- **Base utilizada:** `wings_testing_gemini` (descartable; nunca se tocó la base de trabajo)
- **Veredicto:** **APROBADO** (`estado=cerrado`, `verifica=Gemini`)

---

## 1. El código y todas las puertas que guardan asistencia

Se inspeccionó el diff completo de `git show 8f2be1f` y se realizó un barrido completo de `app/` y `routes/` con `codebase-memory` buscando todas las referencias a `Asistencia` y `asistencias`.

### 1.1. Cambios introducidos en `8f2be1f`

| Archivo | Líneas | Qué hace realmente el código |
|---|---|---|
| `app/Models/Clase.php` | `107–110` | Define `public function todaviaNoOcurrio(): bool { return $this->fecha->gt(today()); }`. |
| `app/Http/Controllers/ClaseWebController.php` | `291, 296` | En `show()`, calcula `$todaviaNoOcurrio = $clase->todaviaNoOcurrio()` y lo pasa a `clases.show`. |
| `app/Http/Controllers/ClaseWebController.php` | `315–321` | En `storeAsistencias()`, inmediatamente después del chequeo de `$clase->cancelada` y antes de `$clase->integraLiquidacionCerrada()`, evalúa `if ($clase->todaviaNoOcurrio())` y rechaza con HTTP `422` en JSON (`{'success': false, 'message': 'La clase todavía no ocurrió. La asistencia se toma desde el día de la clase.'}`) o redirección `back()->with('error', $mensaje)` sin tocar la base. |
| `app/Services/ClaseService.php` | `97–99` | En `registrarAsistencia()`, evalúa `if ($clase->todaviaNoOcurrio())` y lanza `\Exception('La clase todavía no ocurrió. La asistencia se toma desde el día de la clase.')` antes de validar solapamiento o escribir en `asistencias`. |
| `resources/views/clases/show.blade.php` | `268–276, 349, 395` | Muestra `@elseif($todaviaNoOcurrio)` con el cartel `ds-flash ds-flash--error`, agrega `disabled` a cada `.presente-checkbox` (`línea 349`) y envuelve el bloque de motivo de corrección + botón `#btn-guardar-asistencias` en `@if($alumnos->isNotEmpty() && !$todaviaNoOcurrio)` (`línea 395`). No agrega JavaScript incrustado. |
| `docs/02-contratos/Wings-Contrato-Clases-Asistencias-V1.md` | `56–68, 206` | Documenta la enmienda del 17/10/2026 en `§4.c` y agrega la regla freeze en `§4.i`. |

### 1.2. Barrido de todas las puertas que escriben en `asistencias`

| Puerta en el código | Ruta / Consumidor | ¿Tiene la regla T35? | ¿Es alcanzable hoy? |
|---|---|---|---|
| `ClaseWebController::storeAsistencias` (`app/Http/Controllers/ClaseWebController.php:300`) | `POST /clases/{id}/asistencias` (`routes/web.php:224`, accesible para `ADMIN`, `OPERATIVO` y `PROFESOR`) | **Sí** (`$clase->todaviaNoOcurrio()` en línea 315) | **Sí** — es la única puerta HTTP activa de la aplicación web. |
| `ClaseService::registrarAsistencia` (`app/Services/ClaseService.php:92`) | Servicio de dominio e invocación desde pruebas | **Sí** (`$clase->todaviaNoOcurrio()` en línea 97) | **Sí** — por código interno. |
| `AsistenciaController::store` (`app/Http/Controllers/AsistenciaController.php:168`) | `POST /api/asistencias` (`routes/api.php:91`) | **Sí** (delega en `$this->claseService->registrarAsistencia`) | **No** — `routes/api.php` está deshabilitado en `bootstrap/app.php:17` desde S2. |
| `AsistenciaController::storeBulk` (`app/Http/Controllers/AsistenciaController.php:62`) | `POST /api/asistencias/clase/{claseId}` (`routes/api.php:92`) | **No** (hace `Asistencia::updateOrCreate` directo sin llamar a `todaviaNoOcurrio()`, ni a `integraLiquidacionCerrada()`, ni validar solapamiento o motivo de corrección) | **No** — `routes/api.php` está apagado en `bootstrap/app.php:17` (`// api: __DIR__.'/../routes/api.php'`). Ninguna ruta activa llega a `AsistenciaController`. |

**Conclusión sobre las puertas:** En el sistema activo no quedó ninguna puerta alcanzable sin la regla. Se deja asentado que si algún día se reactivara `routes/api.php`, `AsistenciaController::storeBulk` debería alinearse con `ClaseWebController::storeAsistencias` (tanto para T35 como para FIN-12, solapamiento y motivo de corrección).

---

## 2. El límite de la fecha, huso horario de Wings y reloj simulado

1. **Huso horario:** `config/app.php:68` fija `'timezone' => 'America/Argentina/Buenos_Aires'`.
2. **Cast de `Clase::$fecha`:** `app/Models/Clase.php:29` declara `'fecha' => 'date'`, por lo que Eloquent instancia `Carbon` a las `00:00:00` en `America/Argentina/Buenos_Aires`.
3. **Reloj simulado (`RelojSimulado`):** `app/Providers/AppServiceProvider.php:28–31` invoca `RelojSimulado::aplicar(...)` (`app/Support/RelojSimulado.php:49–97`), que fija `Carbon::setTestNow(...)` (clavado o avanzando segundo a segundo hasta `$fecha->copy()->endOfDay()`, es decir `23:59:59` del día simulado).
4. **Cálculo de `today()`:** `today()` devuelve `Carbon::now()->startOfDay()` en el huso `America/Argentina/Buenos_Aires` respetando `Carbon::setTestNow(...)`.
5. **Comportamiento en los bordes (para una clase con `fecha = 2026-10-18`):**
   - **A las `23:59:00` (y `23:59:59`) del `2026-10-17`:** `today()` es `2026-10-17 00:00:00 -03:00`. `$clase->fecha` (`2026-10-18 00:00:00 -03:00`) es estrictamente mayor (`gt(today()) === true`) → `todaviaNoOcurrio()` devuelve `true` y rechaza guardar asistencia.
   - **A las `00:00:00` del `2026-10-18`:** `today()` pasa a `2026-10-18 00:00:00 -03:00`. `$clase->fecha->gt(today())` devuelve `false` → `todaviaNoOcurrio()` devuelve `false` y permite tomar asistencia durante todo ese día sin depender de `hora_inicio`.

---

## 3. Verificación de la pantalla en navegador (Chrome sobre `wings_testing_gemini`)

Se levantó la aplicación local conectada exclusivamente a `wings_testing_gemini` (`127.0.0.1:8096`) con fecha `2026-10-17` y se recorrieron los escenarios con Chrome Headless CDP a **1366 px** (escritorio) y **360 px** (dentro de un marco `<iframe>` de 360 px de ancho en una ventana de 1366 px, conforme a `AGENTS.md §1`).

Toda la evidencia gráfica y el volcado de inspección DOM quedaron guardados en `docs/06-pruebas/PRU-04/evidencia-t35-gemini/`:

| Caso verificado | Perfil | Captura 1366 px | Captura 360 px | Resultado verificado en DOM y pantalla |
|---|---|---|---|---|
| **Control de marco móvil (`/login`)** | Anónimo | — | `00-control-login-360.png` | La tarjeta de ingreso entra completa sin recortes laterales; valida el marco de 360 px. |
| **Clase futura (`2026-10-18`)** | `OPERATIVO` (`Valeria Operativa`) | `01-clase-futura-operativo-1366.png` | `02-clase-futura-operativo-360.png` | Muestra el cartel `Esta clase todavía no ocurrió. La asistencia se toma desde el día de la clase.`. Las 2 casillas `.presente-checkbox` tienen `disabled=true` (un `.click()` real en el DOM no cambia su estado). No existe `#btn-guardar-asistencias` en el DOM (`btnGuardarPresent: false`). El botón `Cancelar` de la cabecera sigue disponible. |
| **Clase futura (`2026-10-18`)** | `PROFESOR` (`Martín Gómez`) | `03-clase-futura-profesor-1366.png` | `04-clase-futura-profesor-360.png` | Muestra el mismo cartel de aviso, casillas con `disabled=true` y ausencia de `#btn-guardar-asistencias`. Además, desde la sesión del profesor en el navegador se disparó `fetch('POST /clases/1/asistencias')` y el servidor respondió `HTTP 422` con `{"success":false,"message":"La clase todavía no ocurrió. La asistencia se toma desde el día de la clase."}`. |
| **Clase de hoy (`2026-10-17`)** | `OPERATIVO` | `05-clase-hoy-operativo-1366.png` | `06-clase-hoy-operativo-360.png` | Sin cartel de bloqueo (`flashes: []`), casillas habilitadas (`disabled=false`, el click alterna el tilde), botón `Guardar` visible y habilitado (`btnGuardarPresent: true, btnGuardarDisabled: false`), sin recuadro de motivo de corrección. |
| **Clase pasada con lista previa (`2026-10-16`)** | `OPERATIVO` | `07-clase-pasada-operativo-1366.png` | `08-clase-pasada-operativo-360.png` | Casillas habilitadas (`disabled=false`), recuadro `Estás corrigiendo una lista ya tomada — indicá el motivo` (`#input-motivo-asistencias`) visible y botón `Guardar` habilitado. |
| **Clase cancelada (`2026-10-18`, `cancelada=true`)** | `OPERATIVO` | `09-clase-cancelada-operativo-1366.png` | `10-clase-cancelada-operativo-360.png` | Como `@if($clase->cancelada)` precede a `@elseif($todaviaNoOcurrio)` en `resources/views/clases/show.blade.php:268–276`, muestra únicamente el cartel `Esta clase está cancelada. No se pueden registrar asistencias.` y tarjetas con `opacity:0.6; pointer-events:none;`. |

> **Observación menor no bloqueante en cancelación inline:** En el código preexistente de `resources/views/clases/show.blade.php:593–596`, cuando se cancela una clase individual sin recargar la página (`ejecutarCancelacion(false)`), el script inserta dinámicamente el cartel `Esta clase está cancelada...` después de `#panel-cancelar`. Si la clase que se está cancelando es futura, durante ese instante previo a recargar quedan visibles en pantalla ambos carteles (`Esta clase está cancelada...` arriba y `Esta clase todavía no ocurrió...` abajo); al recargar la página queda solo el de cancelada. No afecta ninguna regla funcional ni habilita la carga.

---

## 4. Las pruebas y corrida de la suite completa

### 4.1. Qué controlan las 8 pruebas de `tests/Feature/AsistenciaClaseFuturaTest.php`

Se leyó el cuerpo completo de las 8 pruebas (líneas `60–178`):

1. `test_no_se_toma_asistencia_de_una_clase_de_manana`: Con reloj en `2026-10-17 10:00:00` y clase el `2026-10-18`, ejecuta `postJson` como `ADMIN` y como `OPERATIVO`. Verifica HTTP `422`, `success: false`, mensaje exacto y `assertDatabaseCount('asistencias', 0)`. *(Nota: no iteraba sobre `PROFESOR`; en esta verificación lo comprobamos aparte desde la sesión de `PROFESOR` en Chrome, obteniendo el mismo `422`).*
2. `test_sin_javascript_vuelve_con_el_aviso_y_no_guarda`: Ejecuta `post` tradicional (no JSON) a `/clases/{id}/asistencias` en clase del `2026-10-18`. Verifica redirección a la ficha de la clase, `session('error')` con el mensaje exacto y 0 filas en `asistencias`.
3. `test_el_dia_de_la_clase_si_se_toma`: Con clase del `2026-10-17` (hoy) a las `18:00` y reloj a las `10:00` (antes de la hora de inicio pero en el mismo día), verifica que `OPERATIVO` recibe `200 OK` y persiste `presente = true`.
4. `test_una_clase_pasada_sin_lista_se_carga_sin_motivo`: Con clase del `2026-10-16` (ayer) sin asistencias previas, verifica que `OPERATIVO` puede cargarla sin motivo (`motivo_correccion = null`).
5. `test_el_limite_es_la_medianoche_de_wings`: Fija el reloj a `2026-10-17 23:59:00` para una clase del `2026-10-18` y comprueba `422` y 0 filas; luego avanza un minuto a `2026-10-18 00:00:00` y comprueba `200 OK` y 1 fila en `asistencias`.
6. `test_el_servicio_tambien_lo_rechaza`: Invoca `app(ClaseService::class)->registrarAsistencia(...)` sobre una clase del `2026-10-18`, comprueba que lanza `\Exception` con el mensaje esperado y que en un bloque `try/catch` la tabla `asistencias` sigue con 0 filas.
7. `test_la_pantalla_de_una_clase_futura_avisa_y_no_ofrece_guardar`: Hace `GET /clases/{id}` sobre una clase del `2026-10-18`, verifica que aparece el texto del cartel, que no aparece `id="btn-guardar-asistencias"` y que el `<input id="presente-{id}">` contiene el atributo `disabled`.
8. `test_la_pantalla_de_una_clase_de_hoy_no_cambia`: Hace `GET /clases/{id}` sobre una clase del `2026-10-17`, verifica que no aparece el cartel de clase futura, que sí aparece `id="btn-guardar-asistencias"` y que el checkbox no tiene `disabled`.

### 4.2. Corrida de la suite sobre `wings_testing_gemini`

- Se ejecutó `$env:DB_DATABASE='wings_testing_gemini'; php artisan test`.
- **Resultado de la corrida completa (`wings_testing_gemini`):** **786 pruebas ejecutadas** (`783 passed, 2 skipped, 1 failed` — **7.389 aserciones** en **1.004,00 s**). El único rojo fue `PrimeraCargaExcelTest::test_revision_con_errores_no_habilita_carga_y_descarga_informe_privado` por colisión en el directorio compartido `storage/framework/testing/disks/local` mientras otro agente corría `Storage::fake('local')` en paralelo.
- **Reejecución aislada inmediata (`tests/Feature/AsistenciaClaseFuturaTest.php` + `tests/Feature/PrimeraCargaExcelTest.php` sobre `wings_testing_gemini`):** **28 passed (579 assertions)** en **39,70 s** (`AsistenciaClaseFuturaTest`: **8/8 aprobadas**, 0 fallos).

---

## 5. Los efectos sobre una clase futura que ya tenía lista cargada antes de T35

El contrato (`Wings-Contrato-Clases-Asistencias-V1.md` §4.c) establece que lo ya cargado antes de T35 no se borra de la base, pero mientras `fecha > today()` la pantalla y el servidor no permiten guardar cambios en la asistencia de esa clase hasta que llegue su día. Se verificó en el código qué efectos reales tiene eso sobre la operación del club:

1. **Contador y filtro «Sin asistencia» (`AppServiceProvider.php:67–71`, `ClaseWebController.php:129–133`, `LiquidacionWebController.php:89–94`, `AvisoAdminService.php:39–43`, `ReporteMensualService.php:201–202`):**
   - **No traba nada.** Todas las consultas de «Sin asistencia» filtran `->whereDate('fecha', '<', today())`. Una clase futura nunca entra en el contador ni en el filtro «Sin asistencia», tenga o no presentes cargados. Y cuando llega el día de la clase (`fecha = today()`), la edición de asistencia queda habilitada automáticamente.
2. **Cancelar la clase (`ClaseWebController::toggleCancelada`, `app/Http/Controllers/ClaseWebController.php:508–557`):**
   - **No traba cancelar ni reactivar la clase.** Ni el controlador ni la vista `resources/views/clases/show.blade.php:101–110` impiden cancelar una clase futura que tenga asistencias cargadas. Al cancelarla (`cancelada = true`), deja de ser liquidable (`Clase::esLiquidable()` y `LiquidacionService` filtran `cancelada = false`).
3. **Liquidación de profesores (`LiquidacionService.php:149–243` y `LiquidacionWebController.php:119–154`):**
   - En la operación habitual la liquidación se genera al cierre del mes (`esUltimoDiaMes`), cuando todas las clases del mes ya son de hoy o pasadas y, por lo tanto, cualquier presente mal cargado de antemano ya puede corregirse desde la ficha de la clase.
   - **Caso de borde real si se previsualiza o genera una liquidación antes de fin de mes:** `LiquidacionService::clasesLiquidablesHora` (`líneas 151–166`) y `calcularLiquidacionComision` (`líneas 190–217`) consultan todo el mes calendario (`whereBetween('fecha', [$fechaInicio, $fechaFin])`). Si una clase futura de ese mismo mes ya tenía un `presente = true` cargado antes de T35 y el administrador genera la liquidación del mes en curso **antes** de que llegue el día de esa clase, esa clase futura (en `HORA`) o ese presente (en `COMISIÓN`) se incluye en el cálculo, y no se puede destildar desde la ficha hasta el día de la clase (la única vía inmediata sin esperar al día de la clase sería cancelar la clase futura).
4. **Cupo semanal del alumno (`ClaseService::contarAsistenciasSemana`, `líneas 217–247`) y bajada de plan (`CajaWebController::cambioDePlanRigeElMesSiguiente`, `líneas 876–893`):**
   - Si en la **misma semana en curso** (lunes a domingo) una clase de un día posterior ya tenía `presente = true` cargado antes de T35, ese presente cuenta dentro de `asistencias_semana` cuando se toma asistencia hoy. No impide guardar el presente de hoy, pero si con eso supera las clases semanales del plan aparece el selector de exceso (`EXTRA` / `RECUPERA`).
   - Del mismo modo, si el alumno solo tenía ese `presente = true` futuro en el mes en curso y ningún otro presente en el mes, `cambioDePlanRigeElMesSiguiente` considera que el alumno ya tuvo asistencia en el mes y difiere una bajada de plan al mes siguiente.

---

## 6. Separación explícita: verificado vs. inferido (`AGENTS.md §6c`)

- **Verificado:**
  - El diff completo de `8f2be1f` (`Clase.php`, `ClaseWebController.php`, `ClaseService.php`, `resources/views/clases/show.blade.php`, `Wings-Contrato-Clases-Asistencias-V1.md`, `AsistenciaClaseFuturaTest.php`).
  - Todas las referencias a `Asistencia` y `asistencias` en `app/` y `routes/`, incluyendo que `AsistenciaController::storeBulk` carece del chequeo pero pertenece a `routes/api.php`, que está deshabilitado en `bootstrap/app.php:17`.
  - El comportamiento del límite de medianoche (`23:59` vs `00:00`) con `America/Argentina/Buenos_Aires` y `RelojSimulado`.
  - La visualización y el DOM real en Chrome a `1366 px` y `360 px` (en marco `<iframe>`) sobre `wings_testing_gemini` para clase futura (`OPERATIVO` y `PROFESOR`), clase de hoy (`OPERATIVO`), clase pasada con lista previa (`OPERATIVO`) y clase cancelada (`OPERATIVO`), más el rechazo `HTTP 422` al enviar `POST /clases/1/asistencias` como `PROFESOR`.
  - La ejecución de `AsistenciaClaseFuturaTest` (8/8 tests) y la suite sobre `wings_testing_gemini`.
  - Las consultas de «Sin asistencia», cancelación de clase, liquidaciones, cupo semanal y bajada de plan frente a asistencias futuras preexistentes.
- **Inferido:**
  - Nada. Todo lo afirmado en este informe fue comprobado en el código, en la base descartable `wings_testing_gemini` y en Chrome local.
