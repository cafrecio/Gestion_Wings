# Verificación de T85 (Gemini) — Claude CyE, 27/10/2026

**Dictamen: APROBADA.** El interruptor Activo/Inactivo no acepta otro toque mientras espera al servidor.
Implementación de Gemini en [`IMPLEMENTACION-T85.md`](IMPLEMENTACION-T85.md); el código entró en `6a06cf09`
(commit de Claude que se llevó por error la entrega preparada de Gemini; la autoría es de Gemini).

## Lectura del código (`resources/js/ds-app.js`)

- Al cambiar, traba el interruptor (`guardando` y `input.disabled`) antes de mandar el pedido; un clic sobre la
  etiqueta mientras espera se descarta.
- Respuesta correcta: en listados se destraba; en la ficha recarga (queda trabado hasta la recarga).
- Error del servidor, respuesta que no es JSON o falla de red: vuelve al estado anterior, corrige `aria-checked`,
  se destraba y muestra el aviso.
- Los seis interruptores con `data-url` (alumnos en listado y ficha, deportes, grupos, profesores, usuarios)
  responden JSON (`activo` o `error`), así que el código nuevo los trata igual. No hay estilo `:disabled` para el
  interruptor en `app.css`: el aspecto no cambia mientras espera. Sin JavaScript en vistas.

## Prueba propia en Chrome real

La de Gemini dispara los dos clics en el mismo instante. Esta reproduce el caso real: **segundo clic de mouse a los
300 ms, con el pedido en viaje** (la respuesta del servidor se retiene 1,5 s con CDP). Base descartable
`wings_testing_claude`, un alumno al día **sin cuota del mes** (como Agustín). Scripts en
[`evidencia-verificacion-t85-claude/`](evidencia-verificacion-t85-claude/).

| Caso | Código anterior (`6a06cf09^`) | Con T85 |
|---|---|---|
| Listado: dos clics, el segundo a los 300 ms | **2 pedidos**; queda activo **con cuota de reingreso** | **1 pedido**; queda inactivo, **0 cuotas** |
| Ficha: lo mismo | — | **1 pedido**; queda inactivo, sin cuota nueva |
| Control: un clic después de la respuesta | — | Se manda (1 pedido) y reactiva: el interruptor sigue funcionando |

Al terminar se restauró el código y se recompiló (`app-DOC7vG8b.js`, el mismo de la entrega).

## Observación menor (no bloquea)

Con la sesión vencida o el token de seguridad caducado, el aviso dice «Error del servidor» o «Respuesta no válida
del servidor», sin decir que hay que volver a entrar. El interruptor sí vuelve a su estado. Para Carlos, si quiere
un texto más claro.

## Lo que T85 no cubre (decisión de Carlos, 27/10)

Un toque deliberado después de la respuesta sigue generando la cuota de reingreso si el alumno no tenía cuota del
mes. Las opciones 2 (mandar el estado deseado) y 3 (deshacer el mismo día) no se hicieron.
