# A11 — aprobación de maqueta y pruebas previas

04/10/2026 · Codex CAB · Preparación; sin implementación ni cierre.

Carlos aprobó la maqueta con **«Ok, aprobada»**. La aprobación visual está recibida.
Su pedido exige además: **«esperá que él escriba la línea Diseno-autorizado.
No la escribas vos»**. Se solicitó esa línea al dueño y no se escribió en su nombre.

Tras `git pull` (sin novedades), se prepararon ocho pruebas locales en
`tests/Feature/ConfiguracionA11Test.php`. Se corrieron antes de cambiar el código:

```text
Tests: 7 failed, 1 passed (58 assertions)
Duration: 9.65s
```

La corrida usó `wings_testing`, sin otra suite simultánea. Sintaxis PHP correcta.
Los fallos comprueban agrupación/nombres ausentes, mensaje de importe no adaptado,
días fuera de 1–28 aceptados, correo inválido guardado, destinos vacíos rechazados,
día fijo presentado editable y errores que no aparecen arriba/junto al campo.
La actualización de valores válidos ya pasa.

El importe negativo **ya se rechaza en el servidor**; el fallo de esa prueba es el
mensaje esperado en castellano. No se reporta como una nueva aceptación de negativos.

El archivo de pruebas queda **sin commit** hasta implementar y dejar la suite verde;
no se publica un corte fallido. El corte completo anterior sigue siendo 343/1955,
verificado en Entrega 1; no es una certificación de esta preparación en curso.
No se cambiaron controlador, Blade, JavaScript, CSS, datos del club ni servidor.

Siguiente paso: recibir la línea del dueño, implementar A11 conforme a la
[maqueta aprobada](../../05-pendientes/maqueta-configuracion/README.md), correr suite
completa y actualizar los documentos del corte. Entregar con commit para que Gemini
verifique; Codex no cierra A11.
