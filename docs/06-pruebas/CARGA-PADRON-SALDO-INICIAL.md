# Carga del saldo inicial de todo el padrón

> Reemplaza a `CARGA-DEUDA-INICIAL-EXCEL.md` para el arranque del club.
> Aquel carga **solo deudores**; este declara el saldo inicial de **todos** y cierra
> el mes de corte. Corre una sola vez, cuando el club termina de cargar sus alumnos.

## Por qué existe

Con la carga vieja, el alumno que no figuraba en el Excel se asumía sin deuda. Un
olvido y una persona al día se veían exactamente igual. Acá cada alumno tiene que
tener escrito **SI** o **NO**: el silencio deja de ser una respuesta.

Además cierra el mes de corte para todos, así Wings empieza a facturar el mes
siguiente y no hay que decidir qué hacer con lo que el club ya cobró por su cuenta.

## Paso 1 — exportar el padrón

```bash
php artisan wings:exportar-padron padron.xlsx
```

Sale un `.xlsx` con un alumno activo por fila, ordenado por apellido:

| DNI | Alumno | Deporte | DEBE | Periodo 1 | Monto 1 | … |
|---|---|---|---|---|---|---|
| 12345678 | Debe, Uno | Hockey | | | | |

El DNI va como texto para que Excel no se coma los ceros de adelante. `DEBE` sale
vacío a propósito: lo completa el club.

Con `--pares=N` se cambia cuántos pares período/monto quedan disponibles. Por
defecto son 6.

## Paso 2 — que el club lo complete

Una fila por alumno, sin borrar ninguna:

- **DEBE = SI** → cargar los pares: período en formato `mmYYYY` y monto.
- **DEBE = NO** → dejar los pares vacíos.

Ejemplo:

| DNI | Alumno | Deporte | DEBE | Periodo 1 | Monto 1 |
|---|---|---|---|---|---|
| 12345678 | Debe, Uno | Hockey | SI | 092026 | 52000 |
| 12345679 | Nodebe, Dos | Hockey | NO | | |

## Paso 3 — validar sin escribir nada

```bash
php artisan wings:importar-padron padron.xlsx --solo-validar
```

Recorre el archivo entero e informa **todos** los errores juntos. No escribe nada.

## Paso 4 — importar

```bash
php artisan wings:importar-padron padron.xlsx
```

El mes de corte es el mes en curso. Para otro, `--corte=2026-09`.

## Qué escribe

| Caso | Qué queda |
|---|---|
| DEBE = SI | Una deuda **PENDIENTE** por cada par período/monto |
| DEBE = NO | Una deuda del mes de corte, **monto 0, PAGADA** |
| DEBE = SI y declara el mes de corte | Solo la deuda declarada; no se cierra dos veces |

**No registra ningún pago.** Un pago en cero diría que entró plata y no entró: lo
que el club cobró antes de Wings queda fuera de esta contabilidad.

El efecto de la deuda en cero pagada es que la pantalla de cobro deja de ofrecer ese
mes, porque mira si existe una deuda del período sin importar su estado.

## Qué rechaza

Es todo o nada: si una sola fila falla, no se escribe ninguna.

- DEBE vacío o distinto de SI/NO.
- DEBE = SI sin ningún período con monto.
- DEBE = NO con períodos o montos cargados.
- Período que no sea `mmYYYY` válido desde 2025.
- Monto no numérico o menor o igual a cero.
- Período repetido en la misma fila.
- DNI + deporte repetido en dos filas.
- DNI + deporte que no corresponde a ningún alumno.
- Deporte inexistente.
- Ya existe una deuda para ese alumno y período, incluido el de corte.

## Consecuencia esperada

En los reportes de Wings, la facturación del mes de corte de los alumnos que dicen
**NO** figura en cero. Es correcto: esa plata entró antes y fuera del sistema.
