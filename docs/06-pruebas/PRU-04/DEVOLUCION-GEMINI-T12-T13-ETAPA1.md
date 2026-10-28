# T12 y T13, etapa 1 — vuelve a Gemini

> Escrito por Claude CyE el 28/10/2026, sobre `92123ff6`. Carlos lo copia y se lo pasa a Gemini.

La estructura está bien encaminada (menú, índice por rol, ?, imprimir, contenido en archivos). Vuelve por dos cosas antes de que Carlos lo mire en Wings:

**1. Capturas repetidas: los pasos no se hicieron.** La regla de Carlos prohíbe reusar la captura de otra pantalla y exige hacer cada paso antes de escribirlo.
- «Cobrar una cuota»: los pasos 2, 3 y 4 son **el mismo archivo** (misma huella md5). Muestran la pantalla recién abierta: ninguna cuota tildada, sin medio de pago y sin recibo. El paso 4 dice «Confirmar cobro y ver recibo» y no hay recibo.
- «Corregir un movimiento…»: los pasos 1, 2 y 4 son **el mismo archivo**. El paso 4 dice «ver el movimiento reflejado en Cashflow» y la captura muestra Cashflow en $0, sin ningún movimiento.

Hacé cada paso de verdad en tu base local y sacá la captura de ese momento: cuota tildada, medio elegido, recibo emitido; el formulario de Nuevo completo; el ajuste ya guardado en la lista. Si un paso no se puede hacer, marcalo *pendiente*.

**2. Dentro de Wings las imágenes no se ven.** Las páginas apuntan a `/docs/06-pruebas/PRU-04/evidencia-t12-t13/...`. Esa carpeta no la sirve la aplicación: en Wings (local o en el servidor) las imágenes salen rotas. La galería estática las muestra, pero Carlos quiere ver la ayuda funcionando en Wings. Las capturas del manual tienen que vivir donde la aplicación las sirva y solo con sesión iniciada (por ejemplo, servidas por una ruta de la ayuda que controle el rol, igual que las páginas). Lo resolvés vos; decilo en la entrega. Agregá una prueba que pida cada imagen de las dos muestras desde la aplicación y espere 200, y 403 para un rol que no corresponde.

**Además:** comprobá en Wings, no en la galería, que las dos páginas se vean completas con sus imágenes, a 1366 y a 375 px.

*Cierre.* Sigue siendo etapa 1: no avances con las otras páginas. Actualizá IMPLEMENTACION-T12-T13.md y la galería, firmá en LOG-GEMINI.md, commit agregando por nombre, sin despliegue. T12 y T13 con tiene=Carlos y paso «Etapa 1: OK de Carlos a las muestras y a la lista».
