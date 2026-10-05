# Verificación independiente: A43 y Permisos (A29, A30, A31)

**Fecha:** 05/10/2026  
**Agente verificador:** Gemini (CAB)  
**Entregas verificadas:**
- A43: Commit `218ffc5` por Codex CAB ([Informe de implementación](IMPLEMENTACION-A43.md))
- Permisos (A29, A30, A31): Commit `97cf933` por Codex CAB ([Informe de implementación](IMPLEMENTACION-PERMISOS.md))  
**Autorización de Carlos:** `Diseno-autorizado: Carlos aprueba A43 y A29, A30 y A31 según las maquetas presentadas, conservando el diseño Wings.`  
**Resultado general:** **APROBADAS AMBAS ENTREGAS SIN DEFECTOS NUEVOS.**

---

## 1. Alcance y Métodos de Verificación

Se aplicó el protocolo de verificación cruzada (§6a y §6c de `AGENTS.md`):
1. **Inspección estática de código:** Verificación directa en el código fuente de controladores, modelos, servicios, vistas Blade y scripts JavaScript.
2. **Suite de pruebas automatizadas:** Ejecución completa de la suite de Laravel sobre la base de datos exclusiva del agente (`wings_testing_gemini`):
   ```bash
   $env:DB_DATABASE='wings_testing_gemini'; php artisan test
   ```
   **Resultado:** 380 pruebas aprobadas / 2242 aserciones (100% verde).
3. **Navegación interactiva en navegador real:** Ejecución en Chrome headless vía CDP (`scratch/run_full_verification.mjs`, `capture_fichas.mjs`, `recapture_a43_desktop.mjs`) operando con las credenciales reales de cada rol (`sandra.vidal@wings.test`, `lucia.gaitan@wings.test`, `admin@wings.test`), verificando respuestas HTTP, renderizado en escritorio (1280px) y móvil (375px), interacción con botones y comprobación posterior en base de datos.

---

## 2. Verificación de A43 — Alta manual con fecha de ingreso antigua

### A. Criterios requeridos y verificados

| Requisito | Código / Implementación | Comprobación en pantalla y BD | Estado |
|---|---|---|:---:|
| **Fecha corriente o futura no pregunta nada** | `resources/js/alumnos-inscripcion.js:66` oculta el fieldset si `!datos.mes_cerrado`. En `app/Services/PagoCuotaService.php:369` aplica `reglaDelDia`. | Probado con fecha `2026-10-05` (Alumno 71). El aviso `#cuota-alta-aviso` permaneció oculto (`hidden = true`). Guardó directo sin solicitar selección. Creó cuota octubre con porcentaje del día (100%). | **APROBADO** |
| **Fecha en mes cerrado advierte antes de guardar** | `resources/views/alumnos/_form.blade.php:192-206` define el fieldset `#cuota-alta-aviso`. `resources/js/alumnos-inscripcion.js:69` expone el aviso dinámico tras preview asíncrono. | Probado con fecha `2020-01-20`. Aviso visible con mensaje exacto: *"Este alumno ingresó en enero de 2020, un mes ya cerrado. ¿Le generamos la cuota de este mes?"*. Radios obligatorios. | **APROBADO** |
| **Opción "Sí" genera cuota del mes en curso al 100%** | `app/Services/PagoCuotaService.php:369, 397-404` genera `DeudaCuota` con `periodo` corriente (`2026-10`), monto completo ($30.000) y `porcentaje_alta: 100`. | Alumno 69 guardado con "Sí". En BD: cuota `2026-10` por $30.000 (100%), 0 cuotas de meses históricos. Ficha muestra estado `En plazo` (5 días de gracia). | **APROBADO** |
| **Opción "No" no genera cuota pero conserva inscripción** | `app/Services/PagoCuotaService.php:394` (`if (!$generarCuotaActual) return null;`). `app/Services/InscripcionService.php` mantiene el cargo de inscripción. | Alumno 70 guardado con "No". En BD: 0 registros en `deuda_cuotas`. Conserva inscripción $5.000 pendiente. Ficha muestra estado **`Al día`** (la inscripción impaga no lo vuelve deudor, cumpliendo ENT-01 y A52). | **APROBADO** |
| **Auditoría: queda registrado qué se eligió y quién** | `app/Http/Controllers/AlumnoWebController.php:256-262` guarda en columna `alumnos.alta_cuota` JSON: modo, usuario_id, timestamp, fecha_ingreso, periodo, monto y porcentaje. | Comprobado en BD:<br>- Alumno 69 (Sí): `modo: MES_ACTUAL`, `usuario_id: 2` (Sandra Vidal), `monto: 30000`, `porcentaje: 100`.<br>- Alumno 70 (No): `modo: SIN_CUOTA`, `usuario_id: 2`, `monto: 0`, `porcentaje: null`.<br>- Alumno 71 (Corriente): `modo: AUTOMATICA`, `usuario_id: 2`. | **APROBADO** |
| **Comportamiento en móvil a 375 px** | `resources/css/app.css`, layout adaptable sin desbordes. | Verificado con viewport de 375px: `scrollWidth = 375`. Los textos no se superponen, los radios son legibles y los botones Cancelar/Guardar están alineados y son accionables. | **APROBADO** |

### B. Inspección de código de A43
- `app/Services/PagoCuotaService.php:363-382`: `previsualizarCuotaAlta()` centraliza el cálculo del mes cerrado (`Carbon::parse($fechaIngreso)->startOfMonth()->lt(now()->startOfMonth())`) y los importes sin escribir en base.
- `app/Services/PagoCuotaService.php:385-405`: `crearCuotaAlta()` rechaza con `ValidationException` si no se envió decisión en mes cerrado; si es `false`, retorna `null` suprimiendo la cuota corriente sin tocar inscripciones.
- `app/Http/Controllers/AlumnoWebController.php:199-263`: Ejecuta dentro de `DB::transaction()` con bloqueo contra carreras (`bloquear()`, `lockForUpdate()`), valida fingerprints contra dobles envíos y congela importes contra cambios concurrentes (`cuota_periodo_visto`, `cuota_importe_visto`).
- `database/migrations/2026_10_04_150000_add_alta_cuota_to_alumnos.php:13`: columna `alta_cuota` nullable en tabla `alumnos`.

### C. Evidencia gráfica de A43
- **Formulario con aviso (Escritorio):** [`a43-aviso-desktop.png`](evidencia/verificacion-a43-permisos/a43-aviso-desktop.png)
- **Formulario con aviso (375 px):** [`a43-aviso-mobile.png`](evidencia/verificacion-a43-permisos/a43-aviso-mobile.png)
- **Ficha tras seleccionar SÍ (Escritorio):** [`a43-resultado-si-ficha.png`](evidencia/verificacion-a43-permisos/a43-resultado-si-ficha.png)
- **Ficha tras seleccionar SÍ (375 px):** [`a43-resultado-si-ficha-mobile.png`](evidencia/verificacion-a43-permisos/a43-resultado-si-ficha-mobile.png)
- **Ficha tras seleccionar NO (Escritorio):** [`a43-resultado-no-ficha.png`](evidencia/verificacion-a43-permisos/a43-resultado-no-ficha.png)
- **Ficha tras seleccionar NO (375 px):** [`a43-resultado-no-ficha-mobile.png`](evidencia/verificacion-a43-permisos/a43-resultado-no-ficha-mobile.png)

---

## 3. Verificación de Permisos (A29, A30, A31)

### A. Comprobación exhaustiva por rol ingresando URLs en la barra de direcciones

#### 1. Rol OPERATIVO (`sandra.vidal@wings.test`)
| Dirección probada | Código HTTP | Mensaje en pantalla | Destino de "Volver" | ¿Filtra datos o números? |
|---|:---:|---|---|:---:|
| `/cashflow` | **403** | *"No podés entrar a esta sección"* | `/operativo` | No |
| `/liquidaciones` | **403** | *"No podés entrar a esta sección"* | `/operativo` | No |
| `/configuraciones` | **403** | *"No podés entrar a esta sección"* | `/operativo` | No |
| `/usuarios` | **403** | *"No podés entrar a esta sección"* | `/operativo` | No |
| `/admin/dashboard` | **403** | *"No podés entrar a esta sección"* | `/operativo` | No |
| `/admin` | **404** | Laravel 404 estándar (ruta no existente) | N/A | No |
| `/caja/validaciones` | **404** | Laravel 404 estándar (ruta no existente) | N/A | No |

**Prueba de navegación con "Volver":** Al hacer clic en el botón `Volver` desde la pantalla 403 de `/cashflow`, el navegador navegó inmediatamente a `http://gestion-wings/operativo`, conservando la sesión intacta de Sandra Vidal. En ningún momento se redirigió al login (A31 resuelto).

#### 2. Rol PROFESOR (`lucia.gaitan@wings.test`)
| Dirección probada | Código HTTP | Mensaje en pantalla | Destino de "Volver" | ¿Filtra datos o números? |
|---|:---:|---|---|:---:|
| `/cashflow` | **403** | *"No podés entrar a esta sección"* | `/clases` | No |
| `/liquidaciones` | **403** | *"No podés entrar a esta sección"* | `/clases` | No |
| `/configuraciones` | **403** | *"No podés entrar a esta sección"* | `/clases` | No |
| `/usuarios` | **403** | *"No podés entrar a esta sección"* | `/clases` | No |
| `/admin/dashboard` | **403** | *"No podés entrar a esta sección"* | `/clases` | No |
| `/alumnos` | **403** | *"No podés entrar a esta sección"* | `/clases` | No |
| `/caja` | **403** | *"No podés entrar a esta sección"* | `/clases` | No |
| `/grupos` | **403** | *"No podés entrar a esta sección"* | `/clases` | No |

**Prueba de navegación con "Volver":** Al hacer clic en el botón `Volver` desde la pantalla 403 de `/alumnos`, el navegador navegó inmediatamente a `http://gestion-wings/clases`, conservando la sesión activa de Lucía Gaitán.

#### 3. Rol ADMIN (`admin@wings.test`)
| Dirección probada | Código HTTP | Mensaje en pantalla | Destino de "Volver" | ¿Filtra datos o números? |
|---|:---:|---|---|:---:|
| `/usuarios/8/edit` (superadmin protegido) | **403** | *"No podés entrar a esta sección"* | `/admin/dashboard` | No |

**Prueba de navegación con "Volver":** El admin común intentando editar una cuenta con `es_superadmin = true` es rechazado con 403 y el botón `Volver` apunta a su propio tablero `/admin/dashboard`.

### B. Inspección de código de Permisos
- `app/Http/Middleware/EnsureAdminWeb.php:23`: Reemplaza la redirección silenciosa antigua por `abort(403)`.
- `app/Http/Middleware/RejectProfesorWeb.php:15`: Aplica `abort(403)` para cualquier intento de acceso del rol PROFESOR a rutas fuera de su alcance.
- `resources/views/errors/403.blade.php:8-13`: Determina dinámicamente el destino del botón `Volver`:
  - ADMIN -> `route('admin.dashboard')`
  - OPERATIVO -> `route('web.operativo.dashboard')`
  - PROFESOR -> `route('web.clases.index')`
  - Invitado / fallback -> `route('login')`
- `app/Http/Controllers/UsuarioWebController.php:264-269`: Aplica `abort(403)` cuando un admin sin flag superadmin intenta editar una cuenta protegida.

### C. Evidencia gráfica de Permisos
- **Operativo en `/cashflow` (Escritorio):** [`permisos-operativo-cashflow-desktop.png`](evidencia/verificacion-a43-permisos/permisos-operativo-cashflow-desktop.png)
- **Operativo en `/cashflow` (375 px):** [`permisos-operativo-cashflow-mobile.png`](evidencia/verificacion-a43-permisos/permisos-operativo-cashflow-mobile.png)
- **Profesor en `/alumnos` (Escritorio):** [`permisos-profesor-alumnos-desktop.png`](evidencia/verificacion-a43-permisos/permisos-profesor-alumnos-desktop.png)
- **Profesor en `/alumnos` (375 px):** [`permisos-profesor-alumnos-mobile.png`](evidencia/verificacion-a43-permisos/permisos-profesor-alumnos-mobile.png)
- **Profesor en `/caja` (Escritorio):** [`permisos-profesor-caja-desktop.png`](evidencia/verificacion-a43-permisos/permisos-profesor-caja-desktop.png)
- **Admin en edición protegida (Escritorio):** [`permisos-admin-superadmin-edit-desktop.png`](evidencia/verificacion-a43-permisos/permisos-admin-superadmin-edit-desktop.png)

---

## 4. Qué no se pudo comprobar

1. **Servidor remoto de prueba (VPS):** No se ejecutó despliegue ni verificación en el servidor remoto porque el protocolo estipula que las tareas se entregan y verifican en local antes de que Carlos autorice el despliegue.
2. **Importador masivo Excel:** Tal como definió Carlos, la primera carga masiva por Excel está fuera de esta entrega y se tratará en su propia tarea.

---

## 5. Dictamen Final

- **A43 (Alta manual con fecha de ingreso antigua):** **APROBADO**. Se comprobó la ausencia de aviso en fechas corrientes, la advertencia clara en meses cerrados, la generación al 100% de la cuota corriente con Sí sin crear meses históricos, la supresión de cuota con No manteniendo inscripción y estado "Al día", y la auditoría completa en JSON.
- **A29, A30, A31 (Permisos y retorno a inicio propio):** **APROBADO**. Se eliminaron las redirecciones silenciosas; el error 403 muestra un mensaje claro en castellano, no filtra datos y el botón Volver preserva la sesión devolviendo a cada rol a su propio panel.

Los defectos **A29**, **A30**, **A31** y **A43** quedan en condiciones de ser marcados como **CERRADOS**.
