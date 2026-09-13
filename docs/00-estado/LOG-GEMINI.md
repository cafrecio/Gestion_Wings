# Wings — Bitácora activa de GEMINI

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-GEMINI.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).

## 2026-09-13 — LOG GEM CAB — SEG-11 Bajar los 14 onclick de las vistas

- **Objetivo:** Migrar los 14 manejadores `onclick` en línea de las vistas Blade hacia JavaScript externo (`resources/js/ds-app.js`) mediante atributos `data-*` y delegación global de eventos, allanando el camino para el endurecimiento de la CSP (`script-src 'self'`).
- **Cambios reales:**
  1. `resources/js/ds-app.js`: Agregado módulo de delegación global para `[data-confirmar]`, `[data-abrir-condonar]`, `[data-cerrar-condonar]`, `[data-abrir-rechazar]`, `[data-cerrar-rechazar]`, `[data-abrir-cancelar]`, `[data-cerrar-cancelar]`, `[data-incluir-hoy]`, `[data-abrir-revision]` y `[data-cerrar-revision]`.
  2. Vistas Blade actualizadas sin alterar ningún estilo, clase ni layout:
     - `resources/views/alumnos/show.blade.php`: 3 reemplazos (`data-confirmar`, `data-abrir-condonar`, `data-cerrar-condonar`).
     - `resources/views/caja/detalle.blade.php`: 4 reemplazos (`data-abrir-rechazar`, `data-abrir-cancelar`, `data-cerrar-cancelar`, `data-cerrar-rechazar`).
     - `resources/views/caja/resumen.blade.php`: 2 reemplazos (`data-abrir-rechazar`, `data-cerrar-rechazar`).
     - `resources/views/liquidaciones/create.blade.php`: 2 reemplazos (`data-incluir-hoy="true"`, `data-incluir-hoy="false"`).
     - `resources/views/revision-cobranza/index.blade.php`: 3 reemplazos (`data-cerrar-revision`, `data-abrir-revision` con `data-tipo="CONTINUA"` e `INACTIVO`).
  3. Preservados intactos los 10 `onsubmit="return confirm(...)"` de eliminación y los 26 bloques `<script>`.
  4. Compilados los assets de producción con Vite (`npm run build`).
  5. `tests/Feature/CspSinCodigoIncrustadoTest.php`: Reducida la constante `MANEJADORES_PERMITIDOS` de 24 a 10 con documentación explicativa.
- **Verificaciones:**
  - `php -l`: sintaxis limpia en las 5 vistas, el test y los archivos tocados.
  - `php artisan view:cache; php artisan view:clear`: compilación Blade sin errores.
  - `php artisan test --filter CspSinCodigoIncrustadoTest`: 2 passed (2 assertions).
  - `git diff --stat -- resources/css`: vacío (diseño intacto).
  - `php artisan test`: 214 tests passed (1315 assertions).
- **Siguiente paso:** Tareas de administración y alertas (ENT-06) o siguiente etapa de CSP.

## 2026-09-13 — LOG GEM CAB — ENT-05 Acceso directo al recibo tras cobrar y en la ficha

- **Objetivo:** Dar acceso directo al recibo de cuota a un clic en dos momentos clave: inmediatamente después de cobrar (para entregar al padre en mostrador) y en la ficha del alumno (para reimpresión o consulta posterior).
- **Cambios reales:**
  1. `app/Http/Controllers/CajaWebController.php`: en `pagar()`, agregada clave `recibo_pago_id` en el flash de sesión del redirect; en `index()`, agregado `session()->reflash()` si la petición es AJAX para conservar el flash ante follows de fetch.
  2. `resources/views/layouts/ds-app.blade.php`: en el banner `.ds-flash--success`, incorporado botón `Recibo` (`class="ds-btn-row ds-btn-row--sec"`, `target="_blank"`, `inline=1`) cuando existe `recibo_pago_id` o `recibo_url`.
  3. `resources/views/alumnos/show.blade.php`: en cada fila del widget "Historial de pagos", agregado botón `Recibo` (`class="ds-btn-row ds-btn-row--sec"`, `target="_blank"`, `inline=1`), distinguiendo además pagos anulados (`· Anulado`).
  4. Ambos botones protegidos con `!auth()->user()?->isProfesor()`.
  5. `resources/js/ds-app.js`: condicionado el auto-dismiss de 3s en `.ds-flash` para que banners con acciones (`<a>` o `<button>`) no desaparezcan automáticamente. Compilado con Vite (`npm run build`).
  6. Agregada prueba de regresión `tests/Feature/CobroReciboAccesoTest.php` (3 pruebas, 18 aserciones).
  7. Sincronizados ambos tableros (`PLAN-TRABAJO-IA-v2026-09-08.md` y `PLAN-TRABAJO-CARLOS-v2026-09-08.html`), `RESUMEN-ARRANQUE.md` y `ESTADO-ACTUAL.md`.
- **Verificaciones:**
  - `php -l`: sintaxis limpia en controladores, vistas y tests.
  - `php artisan view:cache && php artisan view:clear`: compilación Blade OK.
  - `php artisan test --filter CobroReciboAccesoTest`: 3 passed (18 assertions).
  - `php artisan test --filter TablerosNoDivergenTest`: 2 passed (9 assertions).
  - `php artisan test --filter CspSinCodigoIncrustadoTest`: 2 passed (2 assertions).
  - `git diff --stat -- resources/views resources/css`: solo las dos vistas autorizadas tocadas.
- **Siguiente paso:** Tareas de administración y alertas (ENT-06) o reportes.

## 2026-09-12 — LOG GEM CAB — ENT-02 Nuevo recibo de cuota y versión anulada

- **Objetivo:** Implementar el diseño visual aprobado por Carlos y Vanina (`docs/03-diseno-ui/INSTRUCTIVO-IMPLEMENTACION-RECIBOS.md`) para el comprobante de cuota y su versión anulada, bajo el estándar estricto DomPDF CSS 2.1 (A5 vertical).
- **Cambios reales:**
  1. `resources/views/pdfs/recibo-cuota.blade.php`: Rediseñado por completo en layout de tablas porcentuales (`width: 58%` / `42%`, etc.) y `border-collapse: collapse;`, sin flexbox ni grid.
  2. Formato de página A5 vertical (`148mm × 210mm`) con márgenes `10mm 12mm 10mm 12mm` y tipografías DejaVu Sans / DejaVu Sans Mono.
  3. Logo institucional dentro de contenedor `.logo-frame` con fondo `#0F172A` para asegurar contraste y legibilidad.
  4. Importes y totales formateados en `#0F172A` (prohibido rojo en montos). Rojo reservado exclusivamente a la cabecera `.recibo-sello-anulado` y borde de auditoría de cancelación.
  5. Grilla de auditoría en recibo anulado con fecha de emisión, fecha de cobro, fecha de cancelación, cancelado por, motivo de anulación y observaciones.
  6. En `app/Services/ReciboService.php`: incorporados al array `$data` los campos de trazabilidad (`fecha_cancelacion`, `cancelado_por`, `motivo_cancelacion`) preservando `obtenerPeriodosImputados()` y `obtenerTipoCajaPago()`.
  7. Sincronizados ambos tableros (`PLAN-TRABAJO-IA-v2026-09-08.md` y `PLAN-TRABAJO-CARLOS-v2026-09-08.html` con casilla tildada).
- **Verificaciones:**
  - `php -l resources/views/pdfs/recibo-cuota.blade.php`: sintaxis limpia.
  - `php -l app/Services/ReciboService.php`: sintaxis limpia.
  - `php artisan view:cache; php artisan view:clear`: compilación Blade OK.
  - `php artisan test --filter ReciboMedioDePagoTest`: 6 passed (66 assertions), incluyendo generación y regeneración real de PDF anulado con DomPDF.
  - `php artisan test --filter CspSinCodigoIncrustadoTest`: 2 passed (2 assertions).
  - `php artisan test --filter TablerosNoDivergenTest`: 2 passed (9 assertions).
- **Siguiente paso:** Tareas de cobros y de integridad pendientes en el plan.

## 2026-09-12 — LOG GEM CAB — SEG-05 Sanitización de errores en ReciboController

- **Objetivo:** Ocultar excepciones crudas de recibos al usuario final en `ReciboController`, evitando filtrar rutas internas del servidor, trazas o errores de base de datos y registrando el detalle en el log de Laravel.
- **Cambios reales:**
  1. En `app/Http/Controllers/ReciboController.php`: Atrapadas `\Throwable $e` y validada la existencia del archivo generado tanto en `cuota()` como en `liquidacion()`.
  2. Registrado el error técnico con `Log::error()` conteniendo ID del registro, excepción y contexto.
  3. Respuesta JSON amigable en castellano: `"No se pudo generar el comprobante. Por favor, intentá nuevamente o comunicate con administración."` con HTTP 500.
  4. Agregada prueba de regresión `tests/Feature/ReciboErrorSanitizadoTest.php` comprobando que no se filtre información interna y que se registre en el log.
  5. Sincronizados ambos tableros (`PLAN-TRABAJO-IA-v2026-09-08.md` y `PLAN-TRABAJO-CARLOS-v2026-09-08.html` con casilla tildada).
- **Verificaciones:**
  - `php -l app/Http/Controllers/ReciboController.php`: sintaxis limpia.
  - `php artisan test --filter ReciboErrorSanitizadoTest`: 2 passed (14 assertions).
  - `php artisan test --filter TablerosNoDivergenTest`: 2 passed (9 assertions).
- **Siguiente paso:** Proceder con ENT-02 (nuevo diseño de recibo de cuota y versión anulada).

## 2026-09-12 — LOG GEM CAB — ENT-04 Ojo para ver contraseña en usuarios

- **Objetivo:** Implementar botón para ver/ocultar contraseña en alta y edición de usuarios (`resources/views/usuarios/_form.blade.php`), pedido por Carlos el 07/09.
- **Cambios reales:**
  1. Dos botones de ojo independientes (`.btn-toggle-password` con `data-target="password"` y `data-target="password_confirmation"`), manteniendo simetría de columnas en el grid y localidad de control.
  2. Íconos `.icon-eye` y `.icon-eye-off` (SVG) idénticos al modelo de `login.blade.php`.
  3. `padding-right: 2.75rem` en ambos inputs para evitar que el texto pase por debajo del botón.
  4. Compatible con alta y edición: en edición no afecta el placeholder ("Dejar en blanco para no cambiar") ni la validación.
  5. JavaScript desacoplado en `resources/js/ds-app.js` (sin incrustar código en Blade), manteniendo `CspSinCodigoIncrustadoTest` exactamente en 26 bloques `<script>` y 24 manejadores inline. Compilado con Vite (`npm run build`).
- **Verificaciones:**
  - `php -l resources/views/usuarios/_form.blade.php`: sintaxis limpia.
  - `php artisan view:cache; php artisan view:clear`: compilación Blade OK.
  - `php artisan test --filter CspSinCodigoIncrustadoTest`: 2 passed (2 assertions).
  - `php artisan test`: 166 passed (1026 assertions) 100% verde.
- **Siguiente paso:** Continuar con tareas financieras o de entregas según prioridades.

## 2026-09-12 — LOG GEM CAB — ENT-03 Favicon definitivo de Wings

- **Objetivo:** Resolver ENT-03 (Favicon) con identidad propia de escuela de patín artístico, abandonando la paleta negro/rojo/blanco por pedido de Carlos.
- **Cambios reales:**
  1. Generación de activo gráfico: bota blanca de patín artístico con taco, alas fucsia y cian, ruedas oscuras y fondo claro hielo/lavanda con máximo contraste.
  2. Encuadre maximizado al 95% de superficie útil (aprobado por Carlos) para legibilidad nítida en 16px y 32px.
  3. Producción de assets en `public/`: `favicon.ico` (multi-res 16/32/48), `favicon-32x32.png`, `favicon-16x16.png`, `apple-touch-icon.png` (180x180), `android-chrome-192x192.png`, `android-chrome-512x512.png` y `site.webmanifest`.
  4. Enlace canónico en `resources/views/layouts/ds-app.blade.php` bajo autorización explícita de diseño de Carlos.
- **Verificaciones:**
  - `php -l resources/views/layouts/ds-app.blade.php`: sintaxis limpia.
  - `php artisan view:cache; php artisan view:clear`: compilación Blade OK.
  - `git diff --stat -- resources/views resources/css`: solo 6 líneas autorizadas en `ds-app.blade.php`.
  - `php artisan test`: 166 tests pasados (1026 assertions) verde al 100%.
- **Siguiente paso:** Continuar con las tareas de cobros / financiera del plan vigente.

## 2026-09-11 — extracto documental de LOG GEM CYE — ENT-02

Diseño de recibos de cuota, anulado y liquidación por hora/comisión preparado.
Aprobación visual registrada; no equivale a decisión de cálculo por hora/clase.
Entregables: ../03-diseno-ui/PREVIEW-RECIBOS.html y muestras PDF de esa carpeta.
Instructivo: ../03-diseno-ui/INSTRUCTIVO-IMPLEMENTACION-RECIBOS.md.
No copiar a producción sin resolver bloqueos y autorización concreta de implementación.
Verificar disponibilidad/versionado al cambiar de computadora.
