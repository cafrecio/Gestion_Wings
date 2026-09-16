# Wings — Bitácora activa de GEMINI

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-GEMINI.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).

## 2026-09-16 — PENDIENTES comunes (registrado por Claude CAB a pedido de Carlos)

Misma entrada en los tres logs, para que cada agente arranque con la lista.
Corte: `main` con todo subido; suite 229 pruebas / 1374 aserciones, verde el 16/09.

**Cerrado desde el 13/09:** FIN-06 (Gemini), verificacion cruzada de FIN-10 y FIN-03
(Gemini), ENT-02 recibos de cuota y liquidacion (Gemini, commiteado el 16/09),
SEG-06, SEG-07 y `report-uri` de CSP (Claude), `test.gestionar-te` montado (Claude).

**Pendiente, por orden:**

1. **Actualizar `test.gestionar-te`** — Claude. Esta en `2fccacb`, **sin los recibos
   nuevos**: ENT-02 no estaba commiteado cuando se actualizo. Correr
   `montar-test.sh` y `montar-test-https.sh`.
2. **FIN-12** cancelar liquidacion cerrada no pagada — sin asignar (propuesta:
   Gemini). Contrato enmendado en Liquidaciones §2.4; instrucciones en
   `docs/05-pendientes/FIN-12-CANCELAR-LIQUIDACION-CERRADA.md`.
3. **Prueba grande PRU-02** en `test.gestionar-te` — Gemini, despues de 1 y 2.
   Entrar con `admin@wings.test` / `PruebaWings2026`. No resetear la base por su
   cuenta: pedirlo a Claude.
4. **Ensayo de restauracion contra un respaldo real del servidor** — Claude, no
   toca nada. Hasta hacerlo, que el respaldo sirva no esta demostrado.
5. **Desplegar en wings** — despues de que pase 3. Respaldo manual antes y con
   Carlos presente. Migraciones pendientes: `detalle_anulacion`,
   `motivo_cambio_horario`, `porcentaje_comision_aplicado` (ya probadas en test).

**Clases particulares** — Codex. Contrato en `Wings-Contrato-Clases-Particulares-V1.md`.
Va en rama aparte; **falta que Carlos decida si entra antes o despues de la prueba grande**.

**Decisiones de Carlos que no bloquean la prueba:** FIN-04 (balance), FIN-08
(revision con parciales), FIN-09 (limites de fechas), PRU-03 (DEUDOR sin pagos),
ENT-01 (inscripcion), liquidacion por hora: por clase o por duracion, `monto_base`.

**Sin asignar, no bloquean:** SEG-10 integracion continua; SEG-11 resto (22 bloques
`<script>`, uno por archivo segun DESIGN-RULES §8); ENT-06/07/08 despues de la prueba.

## 2026-09-13 — LOG GEM CAB — ENT-02 Implementación y verificación visual final de recibos PDF

- **Objetivo:** Aplicación del diseño de comprobantes PDF aprobado por Carlos y Vanina el 11/09 (`INSTRUCTIVO-IMPLEMENTACION-RECIBOS.md §4.2`) a `recibo-liquidacion.blade.php`, soporte de anexo detallado en `ReciboService.php`, y saneamiento del texto de observaciones en `recibo-cuota.blade.php`.
- **Diseño autorizado por Carlos:** `Diseno-autorizado: aplicar el diseño de recibos que Carlos y Vanina aprobaron el 11/09 (INSTRUCTIVO-IMPLEMENTACION-RECIBOS.md §4.2), nunca aplicado a recibo-liquidacion.blade.php`
- **Cambios reales implementados:**
  1. `resources/views/pdfs/recibo-liquidacion.blade.php`: Reemplazada la plantilla histórica verde por el diseño institucional en pizarra `#0F172A` de 2 páginas exactas A5 vertical (`148mm × 210mm`, márgenes `10mm 12mm 10mm 12mm`). Hoja 1: Resumen ejecutivo con logo institucional en marco oscuro `.logo-frame`, período, total liquidado, medio e imputación contable, y firma de conformidad. Hoja 2: Anexo cronológico de clases dictadas (modalidad HORA) o nómina de alumnos comisionados con cuota base, porcentaje y comisión (modalidad COMISIÓN).
  2. `app/Services/ReciboService.php`: En `generarReciboLiquidacion()`, agregada carga de la relación `detalles`, mapeo batch de referencias (`Clase` con grupo o `Alumno`) sin N+1, cálculo de horas/conteo y estructuración de la colección `$detalles` requerida por la Página 2. En `generarReciboCuota()`, se mantiene compatibilidad de `$observaciones` y se evita la duplicación visual en `recibo-cuota.blade.php` limpiando el motivo repetido al renderizar.
  3. `resources/views/pdfs/recibo-cuota.blade.php`: Limpiado el motivo de anulación del bloque de observaciones al renderizar pagos cancelados, mostrando observaciones originales y motivo de cancelación en campos independientes.
- **Verificaciones:**
  - `php -l`: sintaxis limpia en `ReciboService.php`, `recibo-cuota.blade.php` y `recibo-liquidacion.blade.php`.
  - `php artisan view:cache; php artisan view:clear`: plantillas Blade compiladas sin errores.
  - `php artisan test`: suite completa con **229 pruebas pasadas / 1374 aserciones** (100% verde).
  - Inspección visual en pantalla vía screenshots Edge headless: los 4 comprobantes (cuota 1 mes, cuota 3 meses, cuota estrés con nombre largo, cuota anulado, liquidación hora 2 páginas y liquidación comisión 2 páginas) confirmados prolijos y listos para su entrega en el mostrador.
- **Siguiente paso:** ENT-02 completado y validado en pantalla; continuar según prioridades de Carlos.

## 2026-09-13 — LOG GEM CAB — FIN-03 Verificación cruzada de detalle de anulación en recibos

- **Objetivo:** Verificación cruzada de la implementación de FIN-03 (commit `c1bef8a` de Codex) contra el contrato `Wings-Contrato-Recibos-PDF-V2.md §7.c`.
- **Entorno:** Base aislada `wings_testing_fin10_gemini_20260913` con cobro de 2 meses (`2026-08` y `2026-09`, $60.000) por caja operativa y anulación posterior desde la UI web como `admin@wings.test`.
- **Resultados (100% verificado):**
  1. Cobro de dos meses y posterior anulación: el recibo anulado (`GET /recibos/cuota/{id}`) conserva y muestra ambos períodos con sus importes ($30.000 cada uno, total $60.000 revertido), sello visible y bloque de auditoría con fecha, usuario y motivo.
  2. Base de datos: `pagos.detalle_anulacion` almacena la copia documental JSON con motivo y períodos/montos; `pago_deuda_cuota` queda en 0 filas (imputaciones activas eliminadas atómicamente).
  3. Deuda revertida: las cuotas vuelven a estado `PENDIENTE` con `monto_pagado = 0.00`; saldo total del alumno vuelve de $0 a $60.000 exactos.
  4. Caso legacy: pago anulado previo a FIN-03 sin `detalle_anulacion` no inventa períodos (muestra leyenda explicativa "sin detalle de períodos disponible").
- **Evidencia:** PDFs reales generados en `scratch/recibo_anulado_cuota_3.pdf` y `scratch/recibo_anulado_legacy_4.pdf`. Suite `ReciboMedioDePagoTest` (6 tests, 66 assertions) en verde.
- **Siguiente paso:** Tarea FIN-03 cerrada formalmente; continuar según prioridades de Carlos.

## 2026-09-13 — LOG GEM CAB — FIN-10 Verificación cruzada de edición de clases en pantalla y base

- **Objetivo:** Verificación cruzada contra el contrato `Wings-Contrato-Clases-Asistencias-V1.md §4.c` de la implementación de FIN-10 (commit `c17b3d0` de Codex).
- **Entorno:** Base aislada `wings_testing_fin10_gemini_20260913` con `PrimeraCargaCompletaSeeder` y servidor web autenticado como `admin@wings.test`. Clases pasadas con asistencia creadas vía formulario y ajustada su fecha en base para cumplir restricción de creación.
- **Resultados (10 de 10 casos cumplidos):**
  1. Fechas pasadas inmutables y no movibles al pasado (validación rechaza; fecha en BD intacta).
  2. Horario en clase pasada exige motivo obligatorio; al enviarlo se persiste en `clases.motivo_cambio_horario`.
  3. Edición de horario bloqueada si integra liquidación CERRADA; permitida si está ABIERTA.
  4. Lista de profesores inmutable en clases pasadas (parámetro ignorado en backend; relación en `clase_profesor` intacta).
  5. Clase de hoy permite cambio de fecha, horario y profesores sin pedir motivo.
  6. Superposición de alumno presente en otra clase rechaza con error amigable y ejecuta rollback atómico de la transacción (horario y motivo en BD intactos).
  7. Guardar clase pasada sin cambios no exige motivo (redirección a show con éxito; datos en BD intactos).
- **Evidencia:** Informe detallado comparativo en chat y persistido en `scratch/resultado_verificacion_fin10.json` y `scratch/resultado_sin_cambios.json`.
- **Siguiente paso:** Tarea FIN-10 cerrada formalmente; continuar según prioridades de Carlos.

## 2026-09-13 — LOG GEM CAB — FIN-06 Comisión histórica en liquidaciones

- **Objetivo:** Garantizar que las liquidaciones por comisión se calculen y recalculen con hechos del pasado y no con datos del presente, desacoplando el cálculo del estado actual del alumno y congelando el porcentaje de comisión del profesor al momento de crear la liquidación.
- **Cambios reales:**
  1. `database/migrations/2026_09_13_190000_add_porcentaje_comision_aplicado_to_liquidaciones_table.php`: Agregada columna `porcentaje_comision_aplicado` (`decimal(5,2)`, nullable) a la tabla `liquidaciones` con backfill automático para liquidaciones preexistentes de tipo `COMISION` tomando el porcentaje actual de su profesor.
  2. `app/Models/Liquidacion.php`: Agregado `porcentaje_comision_aplicado` a `$fillable` y a `$casts` (`'decimal:2'`).
  3. `app/Services/LiquidacionService.php`:
     - En `generarLiquidacionMensual()`: se congela `porcentaje_comision_aplicado` al crear la liquidación si es de tipo `COMISION` (`$profesor->porcentaje_comision`).
     - En `calcularLiquidacionComision()`: se usa `$liquidacion->porcentaje_comision_aplicado ?? $profesor->porcentaje_comision ?? 0`, garantizando inmutabilidad histórica ante cambios posteriores en el porcentaje del profesor.
     - Removidos los filtros de estado actual `->where('activo', true)` y `->where('deporte_id', $deporteId)` de la consulta de alumnos con pago. Solo hechos históricos deciden la comisión: pago del período completado y asistencia confirmada (`presente = true`) a clase no cancelada del profesor en ese mes.
  4. `app/Http/Controllers/LiquidacionWebController.php`: En `show()`, si la liquidación es `COMISION` y tiene `porcentaje_comision_aplicado`, se asigna en memoria a `$liquidacion->profesor->porcentaje_comision` y se llama inmediatamente a `$liquidacion->profesor->syncOriginal()`. Esto mantiene el porcentaje visible para la vista Blade (diseño intacto, Regla 1) pero limpia el estado "dirty" del modelo en memoria, previniendo que una llamada posterior o accidental a `save()` persista el porcentaje congelado en la tabla de profesores.
  5. `tests/Feature/LiquidacionComisionHistoricaTest.php`: Creada suite de pruebas con 7 tests cubriendo: alumno dado de baja con pago/asistencia pasada, alumno que cambia de deporte, cambio posterior de porcentaje del profesor sin alterar liquidación recalculada, liquidación legacy con porcentaje null usando porcentaje actual, anulación de pago que retira la comisión en recálculo, alumno con pago sin asistencia que no genera comisión, y vista show mostrando el porcentaje congelado con verificación de que `profesor->isDirty()` es falso y `save()` no altera la base de datos.
  6. Documentación actualizada: `ESTADO-ACTUAL.md`, `CHECKLIST-CARLOS.md`, `PLAN-PRODUCCION.md` y `RESUMEN-ARRANQUE.md` sincronizados en 229 pruebas / 1372 aserciones. Registrada decisión pendiente sobre `valor_hora` en liquidaciones por hora.
- **Verificaciones:**
  - `php -l`: sintaxis limpia en modelos, servicios, controladores, migraciones y tests.
  - `php artisan migrate`: migración ejecutada sin errores.
  - `php artisan test --filter LiquidacionComisionHistoricaTest`: 7 pasaron (26 assertions).
  - `php artisan test --filter DocumentacionNoMienteTest`: 1 passed (7 assertions).
  - `git diff --stat -- resources/views resources/css`: vacío (diseño intacto).
  - `php artisan test`: suite completa con **229 pruebas pasadas**, 100% verde.
- **Siguiente paso:** Próxima tarea del bloque FIN / SEG según prioridades de Carlos.

## 2026-09-13 — LOG GEM CAB — SEG-11 Scripts de grupos y usuarios a sus propios archivos JS

- **Objetivo:** Migrar los bloques `<script>` de `grupos/_form.blade.php` y `usuarios/_form.blade.php` a sus propios archivos dedicados (`resources/js/grupos.js` y `resources/js/usuarios.js`), compilados por Vite y cargados por sus vistas mediante `@vite` vía `@push('scripts')`, conforme a la regla de modularidad de `DESIGN-RULES.md §8`.
- **Cambios reales:**
  1. `resources/js/grupos.js`: Creado para la lógica de grupos (verificación de deporte+nivel vía AJAX `/grupos/check-disponible`, carga y borrado de precios por frecuencia dinámicos vía `#btn-add-plan` y `.btn-remove-plan`, formateo de montos vía `window.initMoneyInput` e índice inicial leído de `data-initial-idx`).
  2. `resources/js/usuarios.js`: Creado para la lógica de usuarios (verificación de email único vía AJAX `/usuarios/check-email`, validación cruzada de contraseña/confirmación, resalte visual de radio `.rol-label` y visibilidad condicional de `#panel-profesor`).
  3. `vite.config.js`: Declarados `resources/js/grupos.js` y `resources/js/usuarios.js` en los inputs del plugin Laravel.
  4. `resources/views/grupos/_form.blade.php`: Reemplazado bloque `<script>` por `@push('scripts') @vite('resources/js/grupos.js') @endpush` y agregado `data-initial-idx` al contenedor de planes.
  5. `resources/views/usuarios/_form.blade.php`: Reemplazado bloque `<script>` por `@push('scripts') @vite('resources/js/usuarios.js') @endpush`.
  6. Compilados assets de producción con Vite (`npm run build`).
  7. `tests/Feature/CspSinCodigoIncrustadoTest.php`: Reducida la constante `BLOQUES_SCRIPT_PERMITIDOS` de 24 a 22.
- **Verificaciones:**
  - `node -c`: sintaxis JS limpia en `grupos.js` y `usuarios.js`.
  - `php -l`: sintaxis limpia en vistas Blade modificadas y en el test de CSP.
  - `php artisan view:cache; php artisan view:clear`: compilación de plantillas Blade OK.
  - `php artisan test --filter CspSinCodigoIncrustadoTest`: 2 passed (2 assertions).
  - Pruebas funcionales de roles (`ADMIN`, `OPERATIVO`, `PROFESOR` con vínculo obligatorio) y precios por frecuencia con separador de miles y borrado dinámico (`test_funcional_seg11.php`) aprobadas con éxito.
  - `git diff --stat -- resources/css`: vacío (diseño 100% intacto).
  - `php artisan test`: suite completa verde en el nuevo corte con **222 pruebas pasadas** (1347 assertions).
- **Siguiente paso:** Tareas restantes de SEG-11 o siguientes pasos según defina Carlos.

## 2026-09-13 — LOG GEM CAB — SEG-11 Validador de nombre repetido unificado en ds-app.js

- **Objetivo:** Unificar en `resources/js/ds-app.js` el validador en vivo de disponibilidad / nombre repetido compartido por `niveles/_form.blade.php` y `tipos-caja/_form.blade.php`, eliminando sus scripts en línea sin tocar el diseño y preparando el soporte para campos combinados (futuro `grupos`).
- **Cambios reales:**
  1. `resources/js/ds-app.js`: Agregado validador delegado sobre `[data-verificar-disponible]` que consulta el endpoint vía fetch AJAX con `X-Requested-With: XMLHttpRequest`. Maneja eventos `blur` e `input` dinámicos, muestra/oculta el error en vivo, limpia opcionalmente el error previo del servidor (`data-verificar-error-sv`) y deshabilita/habilita el botón de envío del formulario. Preparado para múltiples campos combinados (`data-verificar-combinado`).
  2. `resources/views/niveles/_form.blade.php`: Configurado input `#nombre` con atributos `data-verificar-*` y removido su bloque `<script>` de 40 líneas.
  3. `resources/views/tipos-caja/_form.blade.php`: Configurado input `#nombre` con atributos `data-verificar-*` y removido su bloque `<script>` de 43 líneas.
  4. `grupos` y `usuarios` quedaron afuera por evaluación de alcance acordada: `grupos` valida combinación deporte+nivel y aloja la tabla dinámica de planes; `usuarios` valida contraseñas cruzadas, gestiona selección de roles y el panel de profesor.
  5. Compilados assets con Vite (`npm run build`).
  6. `tests/Feature/CspSinCodigoIncrustadoTest.php`: Reducida la constante `BLOQUES_SCRIPT_PERMITIDOS` de 26 a 24.
- **Verificaciones:**
  - `php -l`: sintaxis limpia en vistas, JS y tests.
  - `php artisan view:cache; php artisan view:clear`: compilación Blade OK.
  - `php artisan test --filter CspSinCodigoIncrustadoTest`: 2 passed (2 assertions).
  - `php artisan test --filter TablerosNoDivergenTest`: 2 passed (9 assertions).
  - Renderizado HTML probado (`scratch/test_verificar_html.php`) con atributos correctos en create y edit.
  - `git diff --stat -- resources/css`: vacío (diseño intacto).
  - `php artisan test`: 214 tests pasaron (1315 assertions).
- **Siguiente paso:** Próximos pasos de SEG-11 o tareas del bloque de entregables según indique Carlos.

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
