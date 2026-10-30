# Control de la etapa B de T12/T13 (Gemini, `a6fee09b`) — Claude CAB, 30/10/2026

**Resultado: la etapa vuelve entera.** Hay un error grave en una página, y el método falló igual que en la
primera vuelta de la A: la ficha toma la regla general y no ve la excepción. Los arreglos del paso 0 y el
**?** de las pantallas del dueño están bien.

## Lo que se comprobó

- `verificar-manual.php` con las reglas de ese momento: 37 páginas, 0 problemas.
- Páginas contrastadas con el código: `recalcular-ajustar-liquidacion`, `cerrar-pagar-liquidacion`,
  `primera-carga-deshacer`, `validar-rechazar-caja` y `transferir-entre-cuentas`. Cerrar y pagar, y
  transferir, no tienen errores de regla. Los mensajes existen en el código: cerrar (`LiquidacionService.php:286`,
  `:290`), cuotas atrasadas (`partials/revision-atrasadas.blade.php:12`), transferencia y fecha vieja.

## Hallazgos

| # | Gravedad | Página | Qué dice | Qué hace Wings |
|---|---|---|---|---|
| 1 | **Grave** | `admin/recalcular-ajustar-liquidacion.md` (nota, opción 2 y «Qué hago si… ya fue cerrada») | El monto se ajusta en cualquier liquidación y solo mientras está **Abierta**. Si está cerrada, cancelarla y generar otra. | El ajuste es **solo para liquidaciones a comisión**, **abiertas o cerradas** sin pagar: `Liquidacion.php:197-199` («Solo se ajustan liquidaciones a comisión abiertas o cerradas sin pagar.»). El panel está dentro del bloque de comisión de `liquidaciones/show.blade.php` y se abre con **Editar monto final**. Pide **Motivo del ajuste**, de 5 a 255 caracteres (`:254-255`), y la página no lo nombra. Con un profesor por hora, el dueño busca un panel que no existe. Con una liquidación a comisión cerrada, la página lo manda a cancelar y rehacer, cuando puede ajustarla. |
| 2 | Media | `admin/primera-carga-deshacer.md` | La única condición es «cero cobros». | También rechaza si los alumnos tienen asistencias, revisión, excesos o movimientos, y si hay deudas, cargos o planes nuevos (`PrimeraCargaExcelService.php:333-343`, tres mensajes). El botón aparece igual (`primera-carga/index.blade.php:124-125`): la profesora toma lista y el dueño recibe un rechazo que la página no explica. |
| 3 | Menor | Todo el manual (57 textos, 18 páginas) | Los mensajes de Wings y los ejemplos van entre `comillas de código`. | El verificador controlaba solo «» y negritas, así que esos textos no los miró nadie. Los busqué uno por uno: existen, salvo los ejemplos. Desde hoy el verificador marca cada comilla de código. |
| 4 | Menor | `cerrar-pagar-liquidacion.md`, `transferir-entre-cuentas.md` | «Banco Galicia». | Las cuentas las configura cada club; mismo criterio que los medios de pago. |
| 5 | Menor | `recalcular-ajustar-liquidacion.md` | «un anticipo pactado». | Wings no tiene adelantos (paso 0 de la lista). |
| 6 | Menor, no es de Gemini | `IMPLEMENTACION-T12-T13.md:191` | MF12 como hueco. | Claude lo arregló el 29/10 (`ac712594`), después de escribir el prompt que pedía listarlo. Hay que sacarlo. |

## Por qué vuelve la etapa entera

La ficha `fuentes/admin-recalcular-ajustar-liquidacion.md` cita `LiquidacionWebController.php:ajusteFinal`,
una función que no existe (es `ajustar`), y apoya el «solo abierta» en «No se puede modificar una liquidación
cerrada» (`Liquidacion.php:124`) sin leer la excepción del ajuste (`:106-122`). Las fichas de la B no traen
la columna «Condición en el código» que se pidió en la devolución de la A. El revisor independiente informó
0 hallazgos. Es el mismo defecto de la primera vuelta de la A: una regla escrita sin leer el `if` completo.
