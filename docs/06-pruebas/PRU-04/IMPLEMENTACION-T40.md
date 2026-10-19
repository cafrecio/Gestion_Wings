# T40 — Las cuotas del día 1 se reintentan solas y avisan

19/10/2026 · Claude CyE. Implementación local, a verificar. Sin pantallas nuevas ni cambios de vistas.

Decisión de Carlos (DU-52, 13/10): «Que se reintente solo los días siguientes y avise si no corrió». Caso CBZ-020.

## Qué pasaba

`cobranza:generar-deudas` estaba programado solo el día 1 a las 6:00 (`monthlyOn(1, '06:00')`). Si el servidor estaba caído a esa hora, ese mes no se generaba ninguna cuota, nada avisaba y había que correrlo a mano.

No alcanza con mirar si hay cuotas del mes para saber si corrió: el alta, el reingreso y el cobro adelantado también crean cuotas del mes. Por eso hace falta una constancia propia.

## Cambios

| Dónde | Cambio |
|---|---|
| Migración `2026_10_19_120000_create_generaciones_cuotas` | Tabla `generaciones_cuotas`: un registro por mes cuya generación terminó, con lo que hizo (creadas, a revisión, ya existían, sin plan). Al instalarse anota el mes en curso como atendido; en la base de pruebas no, para no depender del día real. |
| `App\Models\GeneracionCuotas::faltaGenerar` | Día 1: falta si el mes no figura. Del 2 en adelante: falta si el mes no figura **y el anterior sí** (el sistema venía andando). Sin mes anterior, no genera a mitad de mes. |
| `GenerarDeudasMensualesCommand` | Opción `--si-falta` para el programador. La generación no cambió. Al terminar, cualquier corrida (programada o manual) deja la constancia; si se corta a mitad, no la deja y se reintenta. Con `--si-falta`: si genera después del día 1, avisa; si falla, registra en el log, avisa y sale con error. `--si-falta` no se combina con `--periodo`. |
| `routes/console.php` | Todos los días a las 6:00, `withoutOverlapping` y `onOneServer`, como los punitorios. |
| `AvisoAdminService` | `cuotasGeneradasConAtraso` y `cuotasNoGeneradas` por el canal único de avisos (Telegram y correo). El resumen diario de las 8:00 suma «Cuotas del mes sin generar» mientras falten. |

Repetir no duplica: el comando ya salteaba al alumno con cuota del mes (`crearDeudaSiNoExiste`) y la cola de revisión usa `firstOrCreate`.

## Límites conocidos

- Si la generación corre tarde, el criterio «alta y pago en los últimos 15 días» se mide desde el día en que corre, no desde el 1. Es el mismo cálculo que haría un técnico corriéndolo a mano ese día.
- Si el servidor está caído todo el mes, no hay quien avise: el aviso sale cuando vuelve. El monitoreo del servidor por Telegram cubre la caída.
- El primer día 1 después de instalar depende de la fila que anota la migración. Instalado antes del 1/11, noviembre queda cubierto.

## Pruebas

`tests/Feature/GeneracionCuotasReintentoT40Test.php`, 9 pruebas: el día 1 genera sin avisar; el día 3 recupera y avisa; con el mes generado no hace nada; sin mes anterior no genera a mitad de mes; una corrida cortada por la base se completa al día siguiente sin duplicar, con los dos avisos; `--si-falta` con `--periodo` se rechaza; la corrida manual deja constancia; el resumen diario lo muestra hasta que se genera; el programador lo corre todos los días a las 6.

Relacionadas en `wings_testing_claude`: T40, generador mensual, resumen diario, cuota del alta, vigencia de precios y cobro adelantado, **63 aprobadas, 319 aserciones**.

## Para desplegar

Requiere `php artisan migrate` en el servidor. El programador ya corre cada minuto allí, así que no hay que instalar nada a mano.

## Para verificar

- Que la generación en sí no cambió (diff del comando: solo se movió a `generar()` y se sumó la constancia al final).
- Que un mes generado el día 1 no se vuelva a tocar en el resto del mes.
- Que en el servidor, con la base real, la migración anote el mes en curso y no genere nada.
