# PRU-02 — La prueba grande

Un club de verdad usando Wings durante una semana, y después durante dos o tres meses
simulados. No es una lista de pantallas para revisar: es el club funcionando, y lo que se
busca es dónde Wings estorba, confunde o miente.

**Dónde se juega:** https://test.gestionar-te.com.ar
**Contraseña de todas las cuentas:** `PruebaWings2026`

## Los documentos

| Archivo | Qué es |
|---|---|
| [CRONOGRAMA-SEMANAL.md](CRONOGRAMA-SEMANAL.md) | El horario fijo del club: grupos, días, horas y profesores. Se carga el día 1 y no se toca más |
| [DIA-01.md](DIA-01.md) | El primer día de Vanina con Wings, escrito desde el club: todo lo que tiene que hacer y todo lo que falta |
| [SEMANA-01.md](SEMANA-01.md) | Borrador de los ocho días de la primera semana. **Se reescribe** a partir del DIA-01 |

Los hallazgos de cada día van en su propio archivo, `HALLAZGOS-DIA-XX.md`, al lado del
guion, y las capturas en `evidencia/`.

## Cómo está armado el club

60 alumnas y alumnos activos en seis grupos, cuatro profesores, dos personas en el
mostrador y un dueño. Veinte personas arrastran deuda vieja, declarada con el padrón; las
otras cuarenta arrancan al día, con septiembre cerrado.

## Quién la juega y cómo avanza el tiempo

**La prueba la corre Gemini**, actuando como cada persona del club, con la pantalla a la
vista. No es trabajo de Vanina ni de Carlos: ellos miran el resultado y dicen si el sistema
les sirve.

El tiempo no corre en tiempo real: **se le dice a Wings qué día es**
(`php artisan wings:fecha-simulada`), así una semana de club entra en una tarde y tres
meses en unas sesiones. En producción esa fecha se ignora por código, y una prueba
automática lo garantiza.

El primer salto grande es **el 1 de octubre**, cuando el sistema genera solo las cuotas del
mes nuevo. Ahí importa un detalle que ya se comprobó en el servidor: **la cuota se le crea
a quien asistió el mes anterior**; al que no figura lo manda a revisión de cobranza. Con la
base sin asistencias, mandó a revisión a los 60.

## Qué es un hallazgo

Cualquiera de estas tres cosas, aunque el sistema no dé ningún error:

- Algo que no se entiende, o que hay que hacer dos veces.
- Un número que no es el que una persona del club esperaría.
- Algo que el club necesita y Wings no tiene dónde anotar.

Las capturas valen más que las descripciones.
