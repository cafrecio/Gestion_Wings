# Verificación de Lógica de T59 y T47 — Gemini (CyE)

Fecha: 19/10/2026  
Verificador: Gemini (CyE) — `LOG GEM CYE`  
Implementación auditada: Commit `d7b0a5c8` (Claude CAB)  
Documentos de referencia:
- `docs/06-pruebas/PRU-04/IMPLEMENTACION-T59-T47.md`
- `docs/02-contratos/PERMISOS-ROLES.md`
- AGENTS.md §6-ter y §6a-bis

---

## 1. Resumen Ejecutivo

| Tarea | Estado Lógica | Estado Visual | Resultado Final |
|---|---|---|---|
| **T59** (DU-24, MOV-006: Dueño a Cashflow en Nuevo movimiento de caja) | **APROBADA** | N/A (redirección a pantalla existente) | **APROBADA** |
| **T47** (DU-02, ACC-014: Avisos en castellano, pantalla 405 y AvisoError) | **APROBADA** | Aprobado por Carlos el 19/10 («T47 visual aprobado») | **APROBADA** |

---

## 2. Verificación Detallada de T59 (DU-24, MOV-006)

### a) `CajaWebController::movimientoForm` (Redirección por rol)
- **Código inspeccionado (`app/Http/Controllers/CajaWebController.php:1118`):**
  ```php
  if ($user->isAdmin()) {
      return redirect()->route('web.cashflow.movimiento');
  }
  ```
- **Comprobación:**
  - El usuario con rol `ADMIN` que intenta acceder por GET a `/caja/movimiento` (`web.caja.movimiento`) es redirigido inmediatamente (302) a `web.cashflow.movimiento`.
  - El usuario con rol `OPERATIVO` no es interceptado por esa guarda y continúa al formulario operativo de caja `cajas.movimiento`.
  - El dueño no recibe el molesto e improcedente mensaje «Debés abrir la caja y confirmar el efectivo antes de operar», ya que no abre la pantalla de caja de mostrador.

### b) `CajaWebController::movimientoStore` (Protección contra envíos forzados)
- **Código inspeccionado (`app/Http/Controllers/CajaWebController.php:1140-1144`):**
  ```php
  if ($user->isAdmin()) {
      return redirect()->route('web.cashflow.movimiento')
          ->with('error', 'Tus movimientos se cargan en Cashflow; la caja es de la operativa. No se registró nada.');
  }
  ```
- **Comprobación en base y código:**
  - Si un `ADMIN` intenta enviar un POST a `/caja/movimiento` (`web.caja.movimiento.store`) (vía pestaña vieja, curl o manipulación de formularios), la ejecución se aborta antes de validar datos y antes de invocar `CajaOperativaService`.
  - Retorna redirect a `web.cashflow.movimiento` con mensaje descriptivo en sesión.
  - Se comprobó que no se crea absolutamente nada en `movimientos_operativos` (0 registros), ni en `cashflow_movimientos` (0 registros), y el turno abierto de la operativa en `cajas_operativas` permanece intacto sin modificaciones en sus saldos o estado.

### c) Permisos y contratos (`docs/02-contratos/PERMISOS-ROLES.md`)
- El ADMIN no pierde facultades de supervisión ni de operación:
  - Carga sus egresos/ingresos generales a través del módulo correspondiente: Cashflow.
  - Conserva acceso a la consulta de movimientos de caja (`/caja/movimientos`), arqueos, cierres y liquidaciones.
  - La operativa sigue operando su caja de mostrador con normalidad.

---

## 3. Verificación Detallada de T47 (DU-02, ACC-014)

### d) Fallback 405 y cabecera `Allow` (`routes/web.php`)
- **Código inspeccionado (`routes/web.php:307-316`):**
  ```php
  Route::fallback(function (Request $request) {
      $router = app('router');
      $permitidos = collect($router->getRoutes()->getRoutes())
          ->filter(fn (Route $r) => $r->matches($request, false))
          ->map(fn (Route $r) => $r->methods())
          ->flatten()->unique()
          ->reject(fn ($m) => in_array($m, ['HEAD', $request->method()], true))
          ->values()->all();

      if ($permitidos !== []) {
          abort(405, '', ['Allow' => implode(', ', $permitidos)]);
      }
      abort(404);
  });
  ```
- **Comprobación:**
  - Inspecciona las rutas registradas con `$r->matches($request, false)`. Si la URL coincide con una ruta definida pero para otros métodos HTTP, responde 405 con cabecera estándar `Allow` enumerando los métodos permitidos.
  - Si la URL no coincide con ninguna ruta registrada, arroja 404 («Página no disponible»).
  - Si la petición solicita JSON (`Accept: application/json` o `expectsJson()`), responde JSON con código 405 y cabecera `Allow`.

### e) Vista 405 y botón Volver (`resources/views/errors/405.blade.php`)
- **Código inspeccionado:**
  - Emplea el componente compartido `errors._contenido` manteniendo la estética, tipografía y tokens del sistema.
  - Textos en castellano claro para el usuario: «Esta dirección no se abre a mano», «Es la dirección de una acción: se usa desde su botón en la pantalla. No se hizo ningún cambio.»
  - Resolución del botón `Volver`:
    - `ADMIN` → `admin.dashboard`
    - `OPERATIVO` → `web.operativo.dashboard`
    - `PROFESOR` → `web.clases.index`
    - No autenticado / anónimo → `login`
- **Revisión visual:**
  - Inspeccionadas las capturas tomadas con Chrome real (`02-t47-direccion-de-accion-1366.png`, `03-t47-direccion-de-accion-375.png`, `04-t47-operativa-validar-1366.png`).
  - No presenta desbordes horizontales en 375px ni en 1366px; el botón `Volver` queda perfectamente posicionado a la derecha.

### f) Manejo centralizado de excepciones con `App\Support\AvisoError::texto`
- **Código inspeccionado (`app/Support/AvisoError.php`):**
  - Si `$e instanceof ModelNotFoundException`: devuelve mensaje amigable en castellano: «Ese registro no existe o ya no está. Volvé a la pantalla y probá de nuevo.». Se eliminan completamente los mensajes técnicos en inglés como `No query results for model [App\Models\...]`.
  - Si `$e instanceof QueryException`, `PDOException` o `ErrorException`: registra el error con traza completa en el log mediante `report($e)` y devuelve el mensaje amigable: «No se pudo completar. Probá de nuevo y, si se repite, avisá al dueño.», evitando filtrar consultas SQL, nombres de tablas o columnas.
  - En cualquier otra excepción (ej. `DomainException`, `Exception` de negocio): retorna `$e->getMessage()`, preservando los mensajes específicos del dominio (p. ej. «Debés abrir la caja y confirmar el efectivo antes de operar», «Medio no válido», etc.).

### g) Los 29 `catch` en controladores web
- Se verificó mediante búsqueda estática en el árbol de código que los 29 bloques `catch` en los 6 controladores web invocan `AvisoError::texto($e)`:
  - `AlumnoWebController.php` (6 bloques)
  - `CajaWebController.php` (13 bloques)
  - `LiquidacionWebController.php` (7 bloques)
  - `CashflowWebController.php` (1 bloque)
  - `RevisionCobranzaWebController.php` (1 bloque)
  - `ClaseWebController.php` (1 bloque)
- No queda ningún `catch` en controladores web que devuelva `getMessage()` crudo a la vista o redirección de sesión.

---

## 4. Separación entre lo Comprobado y lo Supuesto

- **Comprobado:**
  - Inspección exhaustiva de código y diff del commit `d7b0a5c8`.
  - Verificación de ausencia de código inline `<script>` en las vistas tocadas.
  - Verificación de rutas y resoluciones de fallback en `routes/web.php`.
  - Inspección de capturas de pantalla a 1366px y 375px para descartar desbordes.
  - Verificación de pruebas dirigidas y suite de pruebas sobre base de datos descartable `wings_testing_gemini`.
- **Supuesto / No verificado:**
  - No se ejecutaron pruebas manuales sobre el servidor VPS ni sobre datos de producción (prohibido por protocolo).

---

## 5. Pruebas Ejecutadas y Resultados

### a) Pruebas Dirigidas de T59 y T47 (`wings_testing_gemini`)
Ejecución: `$env:DB_DATABASE='wings_testing_gemini'; php artisan test tests/Feature/DuenoMovimientoCajaT59Test.php tests/Feature/AvisosCastellanoT47Test.php tests/Feature/PantallasErrorTest.php`

```text
   PASS  Tests\Feature\DuenoMovimientoCajaT59Test
  ✓ el duenio que entra a nuevo movimiento de caja va a cashflow                                                11.06s  
  ✓ si el duenio envia el formulario de caja no se registra nada                                                 0.26s  
  ✓ la operativa sigue cargando movimientos en su caja                                                           0.82s  

   PASS  Tests\Feature\AvisosCastellanoT47Test
  ✓ abrir a mano la direccion de una accion muestra la pagina en castellano                                      0.56s  
  ✓ enviar a una direccion que solo se abre tambien avisa en castellano                                          0.36s  
  ✓ la accion no se ejecuta y la caja queda como estaba                                                          0.34s  
  ✓ un pedido json con el metodo equivocado sigue recibiendo json                                                0.33s  
  ✓ condonar o reactivar algo que no existe avisa en castellano                                                  0.42s  
  ✓ un fallo de base no muestra la consulta y queda en el log                                                    0.28s  
  ✓ los avisos del negocio se muestran como estaban                                                              0.24s  

   PASS  Tests\Feature\PantallasErrorTest
  ✓ las cuatro pantallas en castellano para cada perfil                                                          0.28s  
  ✓ una direccion inventada conserva el inicio del rol                                                           0.16s  
  ✓ registro inexistente conserva permisos y login                                                               0.19s  
  ✓ volver desde un error grave recupera el inicio de cada rol                                                   0.16s  
  ✓ las respuestas json no reciben html para ningun perfil                                                       0.14s  
  ✓ api sigue en json incluso si se pide html                                                                    0.08s  
  ✓ 500 y 503 no consultan base autenticacion ni sesion                                                          0.10s  
  ✓ render directo de mantenimiento no consulta sesion ni base                                                   0.09s  
  ✓ conserva retry after en 429 y 503                                                                            0.07s  
  ✓ error grave no filtra detalles aun con debug activo                                                          0.09s  
  ✓ conserva respuestas de validacion y sesion expirada                                                          0.08s  
  ✓ inicio coincide con el aviso de primera carga                                                                0.11s  
  ✓ fallos 500 se registran y los otros http no llenan el log                                                    0.06s  

  Tests:    23 passed (431 assertions)
  Duration: 16.53s
```
**Resultado:** 23 pasadas, 0 fallidas, 431 aserciones en 16.53 segundos.

### b) Suite Completa de Regresión (`wings_testing_gemini`)
Dado que T59 y T47 tocan módulos de dinero y control transversal (caja, cashflow, liquidaciones y avisos de excepciones), se ejecutó la suite completa respetando AGENTS.md §6-ter tras verificar la no concurrencia de otros agentes en la máquina:

Ejecución: `$env:DB_DATABASE='wings_testing_gemini'; php artisan test`

```text
  Tests:    4 skipped, 921 passed (8179 assertions)
  Duration: 1118.68s (18.6 min)
  Exit code: 0
```
**Resultado:** 921 pruebas aprobadas, 4 omitidas, 0 fallidas, 8179 aserciones. Suite 100% en verde.

---

## 6. Conclusión y Dictamen

- **T59 (Lógica): APROBADA.** El dueño es redirigido a Cashflow al intentar abrir el movimiento de caja y, ante un POST forzado a caja, la operación se intercepta atómicamente sin persistir movimientos ni afectar el turno operativo.
- **T47 (Lógica y Visual): APROBADA.** La pantalla 405 se genera correctamente con cabecera `Allow` para URLs con métodos no permitidos, preserva 404 para inexistentes y responde JSON a peticiones JSON. El componente `AvisoError` protege las pantallas contra excepciones de base de datos sin suprimir los avisos de negocio. La vista 405 fue aprobada visualmente por Carlos el 19/10/2026 («T47 visual aprobado», commit `04bb9228`).
- Ambas tareas quedan cerradas en el tablero con verificación independiente por Gemini (`verifica=Gemini`).

