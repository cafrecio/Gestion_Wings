# Wings — Bitácora activa de GEMINI

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-GEMINI.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).

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
