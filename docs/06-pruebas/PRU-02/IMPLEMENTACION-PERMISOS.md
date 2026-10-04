# A29 / A30 / A31 — rechazo de acceso con regreso al inicio

Entrega de Codex CAB, 04/10/2026. **Pendiente de verificación independiente por Gemini; no cerrada ni desplegada.**

Carlos aprobó la maqueta en el navegador, pidió retirar la comparación de perfiles y escribió:

`Diseno-autorizado: Carlos aprueba A43 y A29, A30 y A31 según las maquetas presentadas, conservando el diseño Wings.`

## Cambio

Una cuenta activa sin permiso recibe HTTP 403 con «No podés entrar a esta sección» y una explicación en castellano. **Volver** lleva al tablero ADMIN, al inicio OPERATIVO o a Clases PROFESOR, conservando la sesión. No se agregan permisos ni se alteran dominios/propiedad de registros. Anónimo e inactivo conservan el ingreso y la desconexión correspondientes.

- `app/Http/Middleware/EnsureAdminWeb.php:23`: `abort(403)` reemplaza las dos redirecciones silenciosas; mantiene controles de autenticación y cuenta activa.
- `app/Http/Middleware/RejectProfesorWeb.php:15`: ya rechaza con 403, sin modificarlo.
- `resources/views/errors/403.blade.php:8`: destino según rol; estructura Wings y botón Volver, sin comparación de perfiles, tecnicismos, CSS ni scripts nuevos.
- `routes/web.php:108`: tablero admin; `:87`: inicio operativo. Middleware de rutas conservado.
- `app/Http/Controllers/UsuarioWebController.php:264`: caso alcanzable de ADMIN común intentando editar cuenta protegida; conserva 403 y usa el aviso común.
- `tests/Feature/AccesoSinPermisoTest.php`: 14 métodos explícitos; un caso por rol/ruta. Se comprueba respuesta, texto, destino y que el inicio abre manteniendo usuario.
- `tests/Feature/CancelarLiquidacionCerradaTest.php:184`: POST prohibidos de operativo/profesor ahora exigen 403; sigue comprobando liquidación intacta. Anónimo sigue al login.

- `tests/Feature/RubroReservadoTest.php:218`: ADMIN conserva rechazo del catálogo con aviso; operativo/profesor exigen 403 y siguen dejando intactos rubro/subrubro. La primera suite encontró esa expectativa antigua de redirección; se ajustó sin modificar el catálogo.

Se verificó que `ensure.profesor.web` no está aplicado a ninguna ruta actual; su guardia no se modificó. API apagada y permisos de API fuera de alcance. No se hicieron cambios en formularios ni navegación general.

## Pruebas y límites

Pruebas previas contra el código anterior: **12 fallidas / 2 aprobadas, 21 aserciones**. Después del cambio: **25 dirigidas aprobadas / 158 aserciones**, incluyendo rechazo de cancelación y CSP. Base exclusiva `wings_testing_codex`.

Suite completa final: **380 pruebas / 2242 aserciones**, todas verdes (104,46 s),
corrida por Codex en copia exclusiva de `218ffc5` más fuentes de esta entrega.
SHA256 de las cinco fuentes de permisos igual a las de la copia probada.
PHP sin errores, Blade cache/clear correctos; CSP conserva 19 bloques/10 handlers.
Documentos de corte, plan y dos tableros actualizados. Sin CSS ni script nuevos.

## Navegador real local

Cuentas ficticias con roles comprobados en la base exclusiva. OPERATIVO en Cashflow,
PROFESOR en Cashflow y Alumnos, ADMIN común en edición de cuenta protegida.
Todos muestran el mismo mensaje y Volver: operativo a `/operativo`, profesor a
`/clases`, admin a `/admin/dashboard`, con sesión conservada. Los tres también
revisados a 375 px; ancho del documento 375 y botón visible/legible, sin superposición.

| Rol | Escritorio | 375 px |
|---|---|---|
| ADMIN | [Captura](evidencia/permisos/admin-desktop.png) | [Captura](evidencia/permisos/admin-mobile.png) |
| OPERATIVO | [Captura](evidencia/permisos/operativo-desktop.png) | [Captura](evidencia/permisos/operativo-mobile.png) |
| PROFESOR | [Captura](evidencia/permisos/profesor-desktop.png) | [Captura](evidencia/permisos/profesor-mobile.png) |

Los demás rechazos listados están comprobados por pruebas HTTP, no por una nueva
recorrida manual de cada pantalla. No se vuelve a reportar ese alcance como visual.

La verificación propia no cierra los defectos. Gemini debe repetir código y pantalla según AGENTS §6a. No se verificó ni modificó el sitio remoto; no hay migración en esta entrega.
