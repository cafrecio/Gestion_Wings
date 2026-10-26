# Verificación de T84 — Claude CyE, 27/10/2026

Entrega de Gemini en `51c58857`: interruptor Activo/Inactivo en la ficha del alumno, sin confirmación, y
aviso de lo que respondió Wings (por ejemplo, la cuota de reingreso generada).

## Resultado: lógica aprobada, con una observación

| Control | Resultado |
|---|---|
| Ficha: el botón «Reactivar» con confirmación nativa ya no está; en su lugar, el mismo `x-ds.toggle` del listado, con la ruta `toggle-activo`, para alumno activo e inactivo | Bien (leído en el diff y en la prueba de Gemini) |
| Sin JavaScript en la vista | Bien: `CspSinCodigoIncrustadoTest` en verde |
| `ds-app.js`: el aviso de éxito (`data.mensaje`) se muestra en el listado; en la ficha se guarda, se recarga y se muestra. El error sigue revirtiendo el interruptor | Bien. Ahora la cuota de reingreso generada desde el listado se avisa, cosa que antes no pasaba |
| Quién puede usarlo | Igual que antes: dueño y operativa (`reject.profesor.web`). La profesora no ve la ficha |
| Pruebas | `FichaToggleActivoT84Test` 2/2. Suite rápida en `wings_testing_claude`: 927 aprobadas, 6 omitidas, **1 fallida ajena a T84** (ver abajo); después del arreglo, 11/11 en T77 + T84 |

## La prueba que fallaba (no es de T84 ni de Wings)

`CobrarMesSinDeudaT77Test` fija el reloj en el 25/10. La migración de reportes registra la cobertura
«desde hoy», con la fecha **real** en que se arma la base de prueba. Desde que el calendario real pasó el
25/10, el Inicio del dueño daba la deuda como «no disponible» (`null`) en esa prueba. Arreglado en la prueba
como ya lo hacen otras cinco: fija la cobertura desde el 01/01/2026. Wings no cambia.

## Observación: dos toques seguidos generan una cuota

El interruptor **no se deshabilita mientras espera la respuesta**, y la ruta **invierte** el estado en vez
de recibir el estado deseado. Dos toques rápidos mandan dos pedidos: el alumno queda como estaba, pero al
pasar por «activo» Wings le genera la cuota de reingreso del mes. Es lo que le pasó a Agustín Acosta el
27/10 ($14.000). Ya pasaba en el listado antes de T84; T84 lo lleva también a la ficha.

Arreglo propuesto (T85): la pantalla manda el estado que quiere (`activo=1` o `0`) y el servidor no hace
nada si ya está así; el interruptor se deshabilita hasta que llega la respuesta.

## Falta

- El aspecto lo aprueba Carlos con las capturas de `evidencia-t84/`.
- **No desplegar T84 en el sitio de prueba hasta que Codex termine el día 22**: el 21.4 usa el interruptor.
