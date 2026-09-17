# FIN-13 — Parte de diseño: recibo y pantalla de liquidación por hora

> Instrucciones pasadas a Gemini el 17/09/2026. El cálculo ya está hecho y subido
> (commit `7547586`); esto es solo lo que se ve en pantalla y en el PDF.
> Carlos autoriza tocar las dos vistas nombradas acá.

## Antes de empezar

- `git pull` en `main`.
- Seguir `docs/00-estado/PROTOCOLO-CONTINUIDAD.md`: resumen de arranque y las tres bitácoras.
- Leer `docs/03-diseno-ui/wings-design/SKILL.md` y `DESIGN-RULES.md`.
- Leer la fila FIN-13 del plan y `docs/02-contratos/LIQUIDACIONES_CONTRATO_V2.md` §3.1.

## Qué cambió (ya está hecho, no se toca)

Los profesores por hora cobran tarifa × minutos / 60. Una clase de 1 h 20 min a $5.000
la hora cobra $6.666,67. Al liquidar quedan guardados:

- la tarifa, en `liquidaciones.valor_hora_aplicado`;
- los minutos de cada clase, en `liquidacion_detalles.minutos`.

Si después alguien cambia la tarifa o el horario de la clase, lo liquidado no cambia.
**Las pantallas tienen que mostrar lo guardado, no lo que diga hoy la clase.**

## Qué hay que cambiar

### 1. `resources/views/pdfs/recibo-liquidacion.blade.php` (anexo HORA, ~línea 456)

- Hoy dice `{{ number_format($det['horas'], 1) }} hs`: una clase de 80 min sale "1.3 hs".
  Tiene que decir "1 h 20 min". Una de 60 min: "1 h". Una de 30 min: "30 min".
- Encabezado de la columna: "Duración".
- En `app/Services/ReciboService.php` (anexo HORA, ~línea 130) agregar `'minutos' => $minutos`
  a cada fila y usarlo en la vista. El `conteo_texto` de arriba ("X clases dictadas ·
  1,33 horas") tiene que salir en el mismo formato: "X clases dictadas · 3 h 20 min".
- Una sola función de formato en el servicio; no copiar la cuenta en la vista.

### 2. `resources/views/liquidaciones/show.blade.php`

- ~Línea 181: "Valor por clase" pasa a "Valor por hora". El número ya sale de la tarifa
  guardada (el controlador la pone en memoria).
- ~Líneas 214-219: la duración se calcula en vivo desde la clase. Tiene que usar
  `$detalle->minutos`, con el mismo formato "1 h 20 min". Si `minutos` es null
  (liquidación vieja), mostrar "—".
- ~Línea 244 (subtotal por clase) y ~349-351 (total HORA): hoy van sin centavos, así que
  $6.666,67 sale $6.667. Mostrarlos con 2 decimales, como el total de comisión.

## No hacer

- No cambiar el cálculo, la migración ni `LiquidacionService`.
- No tocar otras vistas ni `app.css`. No introducir Alpine ni Livewire.
- No correr seeders ni tocar `gestion_wings` ni el servidor. Tests solo en `wings_testing`.

## Pruebas

- Renderizar las dos vistas con clases de 60, 80 y 90 minutos y comprobar "1 h",
  "1 h 20 min", "1 h 30 min", "$ 6.666,67" y "Valor por hora".
- Tienen que fallar contra el código actual; dejarlo dicho en el log.
- Otra prueba: cambiar el horario de la clase después de cerrar la liquidación no cambia
  la duración que muestran la pantalla y el recibo.
- Suite completa. Si cambia la cantidad de pruebas, actualizar `ESTADO-ACTUAL`,
  `CHECKLIST-CARLOS`, `PLAN-PRODUCCION` y `RESUMEN-ARRANQUE` (`DocumentacionNoMienteTest`
  cuenta los métodos `test_`).
- Generar el PDF de verdad y mirarlo: que las columnas no se corten y no salga una hoja
  en blanco.

## Al terminar

- Commit con la línea:
  `Diseno-autorizado: Carlos, FIN-13 recibo y pantalla de liquidacion por hora`
- Marcar FIN-13 como CERRADA en `PLAN-TRABAJO-IA` y tildarla en
  `PLAN-TRABAJO-CARLOS-v2026-09-08.html` (`TablerosNoDivergenTest` lo controla).
  Sacar el pendiente de FIN-13 del `CHECKLIST-CARLOS` §2.
- Entrada corta en `LOG-GEMINI.md` (5–10 líneas): qué cambió, pruebas y que no hay deploy.
- Commit y push juntos.
