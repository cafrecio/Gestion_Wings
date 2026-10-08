# Verificación de A12 y A24 — el inicio del operativo

08/10/2026 · Claude CyE · sobre main `d664c35` · base `wings_testing_claude`.
Lo implementó Gemini (`a6ce0f5`). El aspecto lo aprobó Carlos y no se juzga acá: se
verificó que en cada situación del día la pantalla diga lo que pasa y que cada botón lleve
a algo que el sistema deje hacer.

**Dictamen: A12 aprobado. A24 devuelto.**

## Cómo se verificó

Un recorrido que entra al inicio como operativo en ocho situaciones, lee lo que dice la
parte de arriba de la pantalla y abre cada botón que ofrece, anotando qué respondió el
sistema. Son pedidos reales a Laravel, no una sesión de navegador.

[Recorrido](evidencia/verificacion-a12-a24/VerificacionA12A24Test.php) ·
[Lo que respondió, completo](evidencia/verificacion-a12-a24/resultado.json)

## Resultado por situación

| # | Situación | Qué dice la pantalla | Botones y qué pasó | Resultado |
|---|---|---|---|---|
| 1 | Recién llega, nadie abrió | «Cajón listo para iniciar. Para cobrar […] primero declará el cambio inicial.» | **Abrir** → abre el formulario de apertura | Bien |
| 2 | Turno propio abierto, con un cobro de $25.000 | «Caja abierta. Abierta por vos a las 10:00» · cobrado $25.000, 1 cobro | **Cobrar**, **Registrar**, **Resumen** → abren las tres | Bien |
| 3 | El turno lo abrió un compañero | «Cajón compartido en curso. Turno de Marcos Peña. **Podés cobrar y registrar en este cajón.**» | **Cobrar** → rebota a la apertura · **Registrar** → rebota a la apertura · **Detalle** → acceso denegado (403). Y si intenta abrir: «Hay un turno abierto. Cerralo antes de abrir otro.» | **Mal** |
| 4 | Cerró su turno, espera validación | «Turno cerrado. Última caja: CERRADA. Para atender un nuevo turno, abrí la caja.» | **Abrir** → abre el formulario | Bien |
| 4b | La caja de la compañera está cerrada | «Cajón listo para iniciar» | **Abrir** → abre el formulario | Bien |
| 5 | Tiene una caja rechazada | Aviso de caja rechazada, arriba, y debajo el turno cerrado | **Ver** → lista de cajas · **Abrir** → formulario | Bien |
| 6 | Un compañero dejó el turno abierto **ayer** | «Cajón listo para iniciar» | **Abrir** → abre el formulario, pero al enviarlo: «Hay un turno abierto. Cerralo antes de abrir otro.» | **Mal** |
| 7 | Ella dejó su turno abierto ayer | Aviso «Turno pendiente […] Debés cerrarla antes de operar» **y debajo** «Cajón listo para iniciar» | **Ver** → resumen de esa caja. No ofrece Abrir | Funciona; el segundo texto contradice al primero |
| 8 | Turno propio abierto a las 22:30 | «Caja abierta. Abierta por vos a las 22:30» · cobrado $9.000 | Los tres abren | Bien |

## A24 — por qué se devuelve

El defecto era que la pantalla invitaba a cobrar cuando el sistema no dejaba. En la
situación principal (1) quedó resuelto. Pero reaparece en otras dos:

**Situación 3, la más seria.** Cuando el turno lo tiene un compañero, la pantalla le dice
a la operativa que puede cobrar y registrar, y le ofrece tres botones. Ninguno funciona, y
tampoco puede abrir un turno propio. Queda sin poder hacer nada y con una pantalla que le
dice lo contrario.

El sistema está haciendo lo que dice el contrato de caja (V5, «Apertura y turnos»): «como
máximo un turno ABIERTA para todos los operativos. Cerrar el turno anterior antes de abrir
el siguiente». Los turnos son uno después del otro, no compartidos al mismo tiempo. Lo que
está mal es el texto y los botones de la pantalla, que prometen otra cosa.

**Situación 6.** La pantalla solo mira las cajas abiertas hoy. Si un compañero dejó el
turno abierto ayer, dice «Cajón listo para iniciar» y ofrece Abrir, que el sistema rechaza.

**Qué hace falta.** Que en esas dos situaciones la pantalla diga quién tiene el turno y
desde cuándo, que no se puede operar hasta que se cierre, y que no ofrezca botones que no
van a funcionar. El texto exacto lo decide Carlos.

**Una pregunta de negocio que queda a la vista**, y que no es de esta pantalla: si dos
personas atienden el mostrador a la vez, hoy solo puede cobrar la que abrió el turno. Es lo
que dice el contrato. Si en el club eso pasa, es una definición a revisar, no un arreglo.

## A12 — por qué se aprueba

El defecto era que el inicio mostraba tres cuadros en cero y nada que dijera por dónde
empezar. Ahora lo primero que se ve es el estado del cajón con la acción del momento, y en
el recorrido normal del día (situaciones 1, 2, 4, 5 y 8) dice lo correcto y los botones
funcionan. Los números de recaudación coinciden con lo cargado.

Observación menor, no bloquea: en la situación 7 conviven el aviso de turno pendiente y el
texto «Cajón listo para iniciar».

## No verificado

- El aspecto, que aprobó Carlos.
- La columna de clases del día y los enlaces «Con deuda» y «Posibles inactivos»: se leyeron
  en la vista, no se abrieron en el recorrido.
- Una sesión de navegador usada a mano.
- La pantalla tiene muchos estilos escritos en el HTML; no rompe nada hoy y no es parte de
  este dictamen. Queda anotado para el día que se active el control de seguridad del navegador.

## Capturas de la situación 3

- [Lo que ve Sandra en el inicio](evidencia/verificacion-a12-a24/situacion-3-inicio.png): «Podés cobrar y registrar en este cajón», con Cobrar, Registrar y Detalle.
- [A dónde llega al tocar Cobrar](evidencia/verificacion-a12-a24/situacion-3-cobrar.png): «Hay un turno abierto de Marcos Peña. Debe cerrarse antes de abrir el siguiente.»

[Reproductor](evidencia/verificacion-a12-a24/CapturasSituacion3Test.php). Páginas pedidas a Laravel y dibujadas con Chrome.

---

## Segunda vuelta de A24 — 08/10/2026: CERRADO

Gemini corrigió la pantalla (`decfb8c`) con el texto que eligió Carlos. Repetí el mismo
recorrido de ocho situaciones sobre esa versión, en `wings_testing_claude`:
[resultado](evidencia/verificacion-a12-a24/resultado.json).

| # | Situación | Qué dice ahora | Botones | Resultado |
|---|---|---|---|---|
| 3 | La caja la abrió un compañero hoy | «Caja abierta de Marcos Peña. Abierta hoy a las 10:00. Para atender tu turno, Marcos debe cerrar su caja.» | **Caja** → abre | Bien |
| 6 | Un compañero la dejó abierta ayer | «Caja abierta de Marcos Peña. Abierta ayer a las 10:00. […]» | **Caja** → abre | Bien |
| 7 | Ella dejó la suya abierta ayer | «Tu caja sigue abierta. Abierta ayer a las 10:00. Cerrá este turno para poder comenzar el día.» | **Cerrar** → abre el cierre | Bien |
| 1, 2, 4, 4b, 5, 8 | Las demás | Igual que antes | Todos abren | Bien |

En las ocho situaciones la pantalla responde y **ningún botón rebota ni es rechazado**. Si
la operativa intenta abrir igual con una caja ajena abierta, el sistema lo sigue rechazando,
que es lo que corresponde.

Además Gemini dejó una prueba permanente, `tests/Feature/InicioOperativoTest.php`, que
recorre los botones de la tarjeta y falla si alguno no abre. La corrí: 8 pruebas, 99
aserciones, en verde.

No verificado: el aspecto, que es de Carlos; la sesión de navegador a mano; la columna de
clases y los enlaces de alumnos, que siguen leídos en el código y no abiertos.
