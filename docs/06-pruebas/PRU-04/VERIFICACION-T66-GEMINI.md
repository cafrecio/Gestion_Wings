# Verificación de Lógica T66 — Profesora obligatoria y liquidaciones protegidas

**Fecha:** 19/10/2026  
**Verificador:** Gemini (Oficina CyE)  
**Implementó:** Codex CAB (commit `9bb7bd68`)  
**Resultado:** **APROBADA** (sin hallazgos de lógica, validaciones ni permisos)

---

## 1. Contexto, decisiones y límites de la verificación

- **Aprobación visual previa de Carlos:** El aspecto visual de las tres vistas afectadas (Nueva clase, Editar clase y reasignación de profesores) fue aprobado expresamente por Carlos el 19/10/2026: *«Vista aprobadas, poner esta decision en el reporte final, no quiero que claude me vuelva a preguntar»*. Conforme a AGENTS.md §6a, lo visual aprobado por Carlos no fue reevaluado ni se solicitaron nuevas capturas.
- **Alcance de esta verificación:** Exclusivamente la lógica de negocio, validaciones del servidor, atomicidad en transacciones, bloqueo de clases en liquidaciones cerradas/pagadas y matriz de permisos por rol.
- **Entorno de pruebas:** Base de datos descartable exclusiva `wings_testing_gemini`. Quedó intacta y no se consultó la base `wings` del servidor de producción ni del sitio de prueba.
- **Fuera de alcance establecido:** Desactivar a la única profesora asignada a clases futuras (registrado para T46), cambio de deporte de una profesora existente en su ficha, y los controladores bajo `routes/api.php` que permanecen apagados en `bootstrap/app.php:17`.

---

## 2. Verificación detallada de la lógica

### a) `store`: alta única y serie recurrente
- **Validación temprana:** En `ClaseWebController::store`, las reglas `'profesores' => 'required|array|min:1'` y `'profesores.*' => 'exists:profesores,id'` se ejecutan en `$request->validate()` antes de cualquier lógica de calendario, aviso de cancha o transacción de base de datos.
- **Rechazo total:** Si el campo `profesores` se omite, es `null`, un string o una lista vacía `[]`, el controlador responde `422 Unprocessable Entity` con mensaje de error específico sin persistir filas en `clases` ni en `clase_profesor`.
- **Atomicidad en serie recurrente:** La creación de la serie y la asignación de profesores (`$this->asignarProfesoresValidando`) se ejecutan dentro de `DB::transaction`. Si alguna fecha de la serie genera solapamiento de horario para un profesor, se dispara una excepción y la transacción completa realiza rollback (todo o nada). No se generan clases huérfanas sin profesores.

### b) `validarProfesoresDelDeporte`: exigencia y consistencia
- El método privado `validarProfesoresDelDeporte`:
  1. Exige que el arreglo no esté vacío (`min:1`).
  2. Deduplica los identificadores recibidos y los normaliza a enteros: `array_values(array_unique(array_map('intval', ...)))`.
  3. Consulta en base de datos: `Profesor::whereIn('id', $ids)->where('deporte_id', $deporteId)->where('activo', true)->count() === count($ids)`.
  4. Si algún ID no existe, pertenece a un profesor inactivo o corresponde a otro deporte, falla inmediatamente arrojando `ValidationException` con el mensaje: *«Solo se pueden asignar profesores activos del deporte de la clase.»*.

### c) `update`: edición de clases
- En `ClaseWebController::update`:
  - Para clases de hoy o futuras (`!$pasada`), el campo `profesores` es obligatorio (`required|array|min:1`). Si se envía lista vacía o se omite, la petición es rechazada con 422.
  - Para clases pasadas (`$pasada`), el campo `profesores` se excluye de la validación general para respetar que la reasignación histórica se realiza desde la ficha (`show`) bajo las reglas de §4.d, preservando la asignación existente (`$finales = $actuales`).
  - Todo el bloque se ejecuta dentro de `DB::transaction` con bloqueo de fila (`lockForUpdate`). Cualquier rechazo por disponibilidad de profesor, solapamiento de alumno presente o liquidación revierte de forma atómica tanto los cambios de horario y fecha (`hora_inicio`, `hora_fin`, `fecha`) como las asignaciones de profesores.

### d) `actualizarProfesores` y `validarCambioProfesores`: liquidaciones y concurrencia
- `ClaseWebController::actualizarProfesores` utiliza `DB::transaction` con `Clase::whereKey($id)->lockForUpdate()->firstOrFail()`.
- Antes de procesar la reasignación, invoca `validarCambioProfesores($clase)`:
  - **Clase cancelada:** Arroja `ValidationException` (*«La clase está cancelada. No se pueden cambiar sus profesoras.»*).
  - **Liquidación por HORA:** Bloquea mediante `lockForUpdate()` las liquidaciones que contienen a la clase en `LiquidacionDetalle` (`tipo_referencia = CLASE`).
  - **Liquidación por COMISIÓN:** Bloquea mediante `lockForUpdate()` las liquidaciones de tipo `COMISION` de las profesoras actuales de la clase para el mes y año correspondientes.
  - **Liquidación PAGADA:** Si alguna liquidación está pagada (`estaPagada()`), arroja `ValidationException` (*«La clase integra una liquidación pagada y no se puede modificar.»*).
  - **Liquidación CERRADA:** Si alguna liquidación está cerrada y pendiente de pago (`estaCerrada()`), arroja `ValidationException` (*«Primero cancelá la liquidación cerrada antes de cambiar las profesoras de esta clase.»*).
  - **Retiro del bypass:** Se verificó que el parámetro `confirmar=true` ya no tiene ningún efecto ni bypass en el servidor; el bloqueo es absoluto hasta que la liquidación cerrada sea cancelada administrativamente.

### e) Barrido de rutas y puertas de entrada alternativas
- Se inspeccionaron todas las rutas web en `routes/web.php` y los controladores en `app/Http/Controllers/`.
- No existe ningún otro endpoint web que inserte o actualice registros en `clase_profesor` o cree instancias de `Clase`.
- Los métodos `asignarProfesor` y `crearClase` de `ClaseController` y `Admin\ClaseController` se encuentran mapeados únicamente en `routes/api.php`, la cual permanece deshabilitada en `bootstrap/app.php:17`.

### f) Integridad de las 16 pruebas automatizadas
- Se inspeccionó el archivo `tests/Feature/ClaseProfesoraObligatoriaT66Test.php`.
- Las 16 pruebas verifican no solo el código de estado HTTP o la sesión, sino el estado efectivo de la base de datos tras cada operación:
  - `assertDatabaseCount('clases', 0)` y `assertDatabaseCount('clase_profesor', 0)` en altas rechazadas.
  - Comprobación de rollback verificando que los atributos de la clase en base de datos quedan idénticos a los previos al intento fallido (`$this->assertEquals($antes, $clase->fresh()->getAttributes())`).
  - Verificación de atributos e importes intactos en liquidaciones cerradas y pagadas.
  - Verificación de asignación histórica (`motivo_cambio_profesor` en `null` para primera asignación, y obligatorio para correcciones).

### g) Matriz de permisos y roles (PERMISOS-ROLES.md)
- `routes/web.php`: Las rutas de creación y edición general (`create`, `store`, `edit`, `update`, `toggleValidada`) están protegidas por el middleware `ensure.admin.web`.
- `actualizarProfesores`:
  - `PROFESOR` es rechazado con HTTP 403 (*«No tenés permiso para esta acción.»*).
  - `OPERATIVO` puede reasignar profesores en clases de hoy o futuras (operación diaria de mostrador).
  - En clases pasadas (`$esPasada`), solo `ADMIN` puede reasignar; el `OPERATIVO` es rechazado con HTTP 403 (*«Esta clase ya pasó: solo un administrador puede cambiar sus profesores.»*).
  - `ADMIN` conserva acceso total en todos los casos. Se cumple estrictamente la regla de oro: el ADMIN nunca tiene menos facultades que el OPERATIVO.

---

## 3. Pruebas ejecutadas y resultados

Las pruebas se ejecutaron sobre la base descartable `wings_testing_gemini`:

1. **Pruebas específicas T66:**
   ```bash
   DB_DATABASE=wings_testing_gemini php artisan test tests/Feature/ClaseProfesoraObligatoriaT66Test.php
   ```
   **Resultado:** 16 pasadas (115 aserciones), duración: 48.47s.

2. **Pruebas de edición y regresión de clases (6 suites):**
   - `AsistenciaClaseFuturaTest.php`
   - `ClaseProfesoraObligatoriaT66Test.php`
   - `ClasesCanceladasHoyTest.php`
   - `ClaseSinAsistenciaYDniT34T67Test.php`
   - `EditarClaseTest.php`
   - `ProgramarClasesA15A16Test.php`
   
   **Resultado:** 60 pasadas (593 aserciones), duración: 34.78s.

3. **Suite completa del proyecto:**
   ```bash
   DB_DATABASE=wings_testing_gemini php artisan test
   ```
   **Resultado:** **920 aprobadas, 4 omitidas, 0 fallidas (8167 aserciones)**, duración: 1246.60s, salida 0.

4. **Sintaxis y vistas:**
   - `php -l app/Http/Controllers/ClaseWebController.php` (Sin errores de sintaxis).
   - `php -l tests/Feature/ClaseProfesoraObligatoriaT66Test.php` (Sin errores de sintaxis).
   - `php -l tests/Feature/EditarClaseTest.php` (Sin errores de sintaxis).
   - `php artisan view:cache` y `php artisan view:clear` ejecutados con éxito.

---

## 4. Separación entre comprobado y no comprobado

### Comprobado fehacientemente:
1. El código de `ClaseWebController` implementa todas las restricciones solicitadas por Carlos y formalizadas en el contrato §4.d/§4.i.
2. Las peticiones sin profesora o con profesoras inválidas son rechazadas en el servidor antes de cualquier persistencia.
3. Las series de clases y las actualizaciones se ejecutan de manera atómica con rollback garantizado ante cualquier falla.
4. Las clases en liquidación cerrada no pueden modificarse hasta su cancelación administrativa previa.
5. Las clases en liquidación pagada permanecen estrictamente intocables y sin atajos de confirmación.
6. La matriz de permisos por rol cumple las especificaciones de `PERMISOS-ROLES.md`.
7. La suite completa de 920 pruebas pasa limpia en la base descartable de Gemini.

### Supuesto / No comprobado en esta instancia:
1. No se realizaron consultas directas sobre la base de producción ni del sitio de pruebas (prohibido explícitamente en el protocolo). Se asume que no existen clases huérfanas en producción tal como relevó Codex en su informe previo.
2. No se ejecutaron pruebas de estrés de concurrencia distribuida en múltiples servidores físicos, validándose únicamente el comportamiento de bloqueos transaccionales (`lockForUpdate`) mediante Eloquent y MySQL local.

---

## 5. Dictamen final

**Tarea T66 APROBADA.** La lógica implementada por Codex es robusta, cumple con todas las decisiones de negocio y no introduce regresiones en el sistema. Se procede al cierre de la tarea en el tablero único.
