# T64 — Verificación independiente de lógica

## Segunda vuelta (520bafd1) — APROBADA y cerrada, Claude CyE 20/10/2026

- H1: Cancelar repone los valores guardados y el lote toma, de las tarjetas cerradas, los valores
  guardados (leído en `configuraciones.js`; Gemini lo comprobó con clics en Chrome, `probar-h1-browser.mjs`).
- H2: id repetido → **422** y las 3 reglas intactas. H3: id inexistente → **422** «Una de las reglas
  enviadas no existe o ya no está disponible.». Las inactivas se conservan.
- Mis pruebas independientes, ahora exigiendo el rechazo: 5/5; las de Gemini: 16/16 (21 en total).
- **Observación sin bloqueo:** los valores guardados en el editor redondean el porcentaje (`Math.round`).
  Ya pasaba antes: la pantalla muestra y edita el porcentaje entero (`number_format(..., 0)`). Con reglas
  enteras —todas las de `wingstest`— no tiene efecto; con un decimal cargado a mano, guardar otra regla lo
  redondearía.
- Sin aprobación visual: no cambia ninguna vista ni estilo.


Claude CyE, 20/10/2026. Entrega de Gemini: `5b832693` y `9735275f`.

**CON HALLAZGOS — devuelta a Gemini.** Uno alcanzable desde la pantalla (H1) y dos solo con pedido
armado a mano por el dueño (H2, H3). **Sin aprobación visual:** no cambia ninguna vista ni estilo;
el error usa el recuadro que ya existía (Carlos, 20/10: «¿Me estás pidiendo confirmación por una pantalla que no cambia?»).

## Lo que está bien (comprobado)

- `ReglasPrimerPagoValidator`: inicio en el día 1, fin en el 31, sin huecos ni superposiciones. Revisé el
  caso de superposición entre reglas no consecutivas: al ordenar por `dia_desde`, cualquier superposición
  aparece entre dos consecutivas, así que el chequeo por pares alcanza.
- Guardado en lote en una transacción; un id inexistente revierte todo (la base no cambia).
- Cambio de tramos 1–15/16–23/24–31 → 1–10/11–31 en un envío: pasa y queda cubierto.
- Solo ADMIN: OPERATIVO y PROFESOR reciben 403 y la base no cambia.
- Borrar una regla deja en blanco `pagos.regla_primer_pago_id` (`onDelete set null`). No se ve en la web:
  solo lo usa la API, apagada, y cada cobro guarda su propio `porcentaje_aplicado`.
- Sitio de prueba (`wingstest`, solo lectura): reglas 1–15, 16–23 y 24–31, sin cobros con regla.
- Pruebas de Gemini: 13/13 en `wings_testing_claude`.

## Hallazgos

### H1 — Cancelar una edición no deshace los valores, y el próximo guardado los manda

`resources/js/configuraciones.js`: `recopilarReglas` toma los datos de **todas** las tarjetas desde los
campos de sus paneles de edición (`datosRegla` → `campo.value`). El botón Cancelar (`btn-cancelar-edit`)
solo oculta el panel y limpia el error; no repone los valores.

Caso: el dueño abre la regla 1–15, cambia 100 por 90 y aprieta Cancelar. Después edita otra regla, o
borra una, o agrega una nueva, y guarda. El lote lleva la primera con 90 y el servidor la guarda: un
porcentaje que nadie confirmó. **Leído en el código, no reproducido en navegador.**

Corrección esperada: al cancelar, reponer los valores guardados (los tiene `pintarRegla`), o armar el
lote con los valores guardados de las tarjetas que no se están editando.

### H2 — El mismo id dos veces en el lote deja días sin cubrir

`ReglaPrimerPagoWebController::guardarTodas`: el validador ve dos reglas (1–15 y 16–31, conjunto válido),
pero la base guarda una sola porque ambas actualizan el mismo id. Reproducido: respuesta **200** y queda
una única regla 16–31; **los días 1 a 15 sin cubrir**. Solo con pedido armado a mano por el dueño (la
pantalla manda una tarjeta por id). Corrección esperada: `reglas.*.id` con `distinct`.

### H3 — Un id inexistente responde 404 en inglés

Reproducido: 404 «No query results for model [App\Models\ReglaPrimerPago] 999999». La base no cambia.
Solo con pedido armado. Corrección esperada: `reglas.*.id` con `exists:reglas_primer_pago,id` → 422 en
castellano.

### Observación, sin decisión

El lote borra las reglas inactivas que no vienen en el envío (no hay ninguna en `wingstest`). Si las
inactivas no se usan, no tiene efecto; si se usan, conviene decidir si se conservan.

## Ejecución

Pruebas independientes en [evidencia-t64-claude/VerificacionT64ClaudeTest.php](evidencia-t64-claude/VerificacionT64ClaudeTest.php),
fuera de `tests/` para no alterar el contador, con guarda de base `wings_testing_claude`:
**5 pruebas, 4 aprobadas, 1 fallida (H2)**. Salidas: [id repetido](evidencia-t64-claude/id-repetido.json) ·
[id inexistente](evidencia-t64-claude/id-inexistente.json) · [inactiva](evidencia-t64-claude/inactiva.json).

Sin suite completa: la carpeta compartida tiene T42 de Codex sin subir, y T64 no toca cobros.
