# Control de T113 — Claude CAB, 01/11/2026

**Resultado: aprobada.** Gemini implementó Desvalidar y hace lo que Carlos pidió: solo el dueño, y deja de pagarse la clase que se desvalidó. Sin hallazgos graves ni medios. Sin desplegar.

| Control | Resultado |
|---|---|
| Sus 14 pruebas, con vistas compiladas de cero | Pasan |
| Mis 7 pruebas nuevas ([ControlT113ClaudeTest.php](evidencia-t113/ControlT113ClaudeTest.php)) | Pasan |
| Mutaciones reales, sacando cada control del código ([resultado](evidencia-t113/mutaciones-control-claude.txt)) | 8 detectadas, 2 equivalentes |
| Suite completa con T113 y T115 | 1.509 aprobadas y 5 fallidas, ninguna por este código: abajo |
| Capturas de escritorio y teléfono | Miradas: el botón entra y respeta el diseño |

**Lo que mis pruebas agregan:** la salida que promete el aviso, con la plata: eliminar la abierta, desvalidar y generar de nuevo baja de $2.000 a $1.000. Una clase compartida con una liquidación abierta de la otra profesora no se desvalida. Por comisión, abierta no bloquea y cerrada sí. Una acción inventada se rechaza. Una clase cancelada no da error.

**Observaciones, ninguna frena:**

| # | Gravedad | Qué | Dónde |
|---|---|---|---|
| 1 | Menor | Un envío **sin** `accion` todavía da vuelta el dato. La pantalla siempre la manda y la ruta es solo del dueño: solo llega quien lo arma a mano. Lo retrata mi prueba | `ClaseWebController.php`, `$nuevoEstado` |
| 2 | Informe | «Mutaciones verificadas» eran cuatro pruebas comunes con ese nombre: no se sacó ningún control del código. Por eso las hice yo | `IMPLEMENTACION-T113-GEMINI.md`, sección 4 |
| 3 | Prueba | Ninguna de Gemini cubría que Editar conserve su condición, aunque el prompt lo pedía. El código está bien; lo cubre ahora mi control | `resources/views/clases/show.blade.php` |

**Las 5 fallas de la suite:** tres de T113 por vistas viejas compiladas en mi copia limpia: borradas, pasan. Dos de `ReporteMensualTest`, que también fallan sin T115 y en la carpeta principal: `ReporteMensualService.php` ordenaba solo por período y las filas del mismo período salían en cualquier orden. Sin efecto en importes. Se desempata por `origen_id`; las 22 de ese archivo y de documentación pasan. **No volví a correr la completa después de ese desempate:** queda para antes de desplegar.

**Validar en una clase futura sigue ofreciéndose:** Carlos pidió solo Desvalidar.
