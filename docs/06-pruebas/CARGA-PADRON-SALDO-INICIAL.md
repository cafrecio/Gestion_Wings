# Carga del saldo inicial de todo el padrón

> Reemplaza a `CARGA-DEUDA-INICIAL-EXCEL.md` para el arranque del club.
> Aquel carga **solo deudores**; este declara el saldo inicial de **todos** y cierra
> el mes de corte. Corre una sola vez, cuando el club termina de cargar sus alumnos.

## Para la prueba grande: el padrón ya exportado

`docs/06-pruebas/PADRON-PRUEBA.xlsx` es el padrón de los 60 alumnos de
`PrimeraCargaCompletaSeeder`, exportado el 22/09 y **sin completar**. Viaja en el
repositorio para no volver a exportarlo en cada máquina: son datos inventados.

Es el punto de partida del primer paso de PRU-02. Se completa como lo haría el club
(columna DEBE con SI o NO, y los pares período + monto) sobre una **copia**, y se importa
en la base de prueba. Si alguien lo completa y lo commitea, dejar el original sin tocar y
subir el completado con otro nombre.

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

El archivo trae dos hojas: **Instrucciones**, que se abre primero y explica cómo se
completa con ejemplos, y **Padron**, con los datos. El importador busca la hoja por
nombre, así que da igual en cuál quede parado el archivo al guardarlo.

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

## Cómo lee lo que escribe el club

Ensayo del 21/09/2026 con el padrón de 60 alumnos completado como lo haría una
persona. Hasta ese día el monto escrito como texto **"52.000" se grababa como $52**,
y el período `092026` que Excel convierte en el número `92026` se rechazaba sin
explicar por qué. Corregido; lo cubre `CargaPadronFormatosExcelTest`.

| En la celda | Se lee |
|---|---|
| DEBE: `SI`, `si`, `Sí`, `SI ` (con espacio) | SI |
| Período `092026`, o el número `92026` que deja Excel | 2026-09 |
| Monto como número de Excel (`52000`, `52000,5`) | tal cual |
| Monto como texto: `52000`, `52.000`, `1.052.000`, `52.000,50` | formato argentino: punto de miles, coma decimal |
| Monto `52,000`, `52.5`, `$52000`, letras, cero o negativo | **rechazado**: es ambiguo o inválido, no se adivina |

El Excel exportado trae las columnas de período en formato texto, para que Excel no
se coma el cero. Los montos quedan como número.

## Qué rechaza

Es todo o nada: si una sola fila falla, no se escribe ninguna.

- DEBE vacío o distinto de SI/NO.
- DEBE = SI sin ningún período con monto.
- DEBE = NO con períodos o montos cargados.
- Período que no sea mes y año válido desde 2025 (el mensaje muestra el ejemplo `092026`).
- Monto ambiguo, con signos, no numérico o menor o igual a cero (el mensaje muestra `52000` o `52.000`).
- Período repetido en la misma fila.
- DNI + deporte repetido en dos filas.
- DNI + deporte que no corresponde a ningún alumno.
- Deporte inexistente.
- Ya existe una deuda para ese alumno y período, incluido el de corte.

## Consecuencia esperada

En los reportes de Wings, la facturación del mes de corte de los alumnos que dicen
**NO** figura en cero. Es correcto: esa plata entró antes y fuera del sistema.
