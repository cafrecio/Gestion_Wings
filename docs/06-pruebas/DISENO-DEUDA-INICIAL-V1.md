# Diseño de la deuda inicial — qué se quiere demostrar

> Definido con Carlos el 06/09/2026. **Los dos Excel ya están generados y validados.**
> Archivos: `DEUDA-INICIAL-PRUEBA-V1.xlsx` y `DEUDA-INICIAL-RECHAZOS-V1.xlsx`.
> Generador reproducible: `generar-deuda-inicial.php` (en esta misma carpeta).

## Por qué el Excel solo no alcanza

Dos hechos **verificados leyendo el cuerpo** de
`CobranzaEstadoService::calcularEstadoDesdeDeudas()` (líneas 226-241), no el nombre
ni el docblock:

```php
if (!$tienePagos || $impagasAnteriores->isNotEmpty())  → DEUDOR
elseif ($vigenteImpaga && $diaActual > $diasGracia)    → MOROSO
elseif ($vigenteImpaga)                                → EN_PLAZO
else                                                   → AL_DIA
```

**1. Hoy los 60 alumnos ya están en DEUDOR, sin deber un peso.** La primera
condición es `!$tienePagos`: quien nunca pagó es deudor aunque no tenga ninguna
deuda. La base tiene 0 pagos.

**2. MOROSO no se puede alcanzar el 06/09.** Es `día > dias_gracia`, y
`dias_gracia_cobranza = 10`. Hoy es día 6. Recién el **11/09** un alumno con
septiembre impago pasa a MOROSO; hasta entonces es EN_PLAZO.

**Consecuencia:** los cuatro estados no salen del Excel. Salen del Excel **más una
tanda de cobros por pantalla** — que es exactamente lo que la prueba tiene que
ejercitar. El Excel prepara el tablero; los cobros lo mueven.

## La repartición

60 alumnos. 48 con deuda, 12 limpios. **81 cuotas, $2.997.000.**

| Grupo | Cuántos | Qué deben | Qué demuestra |
|---|---:|---|---|
| Sin deuda | 12 | nada (no van al Excel) | Control. Se les cobra septiembre y quedan **AL_DÍA**. Si alguna pantalla los muestra deudores, es un defecto |
| Solo septiembre | 20 | `092026` | El mes en curso, recién empezado. Cobro completo → AL_DÍA. Cobro parcial → **EN_PLAZO** |
| Dos meses | 14 | `082026` + `092026` | **FIFO**: al cobrar una cuota tiene que imputar agosto primero, no septiembre |
| Tres meses | 8 | `072026` + `082026` + `092026` | Acumulación y orden con tres períodos abiertos |
| Deuda vieja 2025 | 4 | uno o dos meses de 2025 | Que el FIFO cruce de año |
| Para condonar | 2 | un mes de 2025 | El admin condona con motivo → esa deuda deja de contar como impaga |

### Los seis alumnos elegidos a mano

| Alumno | DNI | Deporte | Deuda | Por qué ese |
|---|---|---|---|---|
| Arias, Valentina | 41738592 | Patín | `032025` `042025` — $24.000 c/u | Alta 17/01/2025, la más antigua |
| Cabrera, Milagros | 39862471 | Patín | `042025` `052025` — $34.000 c/u | Alta 20/02/2025 |
| Acosta, Alan | 41627593 | Fútbol | `062025` — $22.000 | Alta 08/05/2025, el más antiguo de Fútbol |
| Barrios, Benjamín | 39815267 | Fútbol | `082025` `092025` — $38.000 c/u | Alta 16/07/2025 |
| Domínguez, Catalina | 42691735 | Patín | `052025` — $28.000 | Para condonar |
| Cejas, Cristóbal | 42509736 | Fútbol | `112025` — $24.000 | Para condonar |

### Los montos de 2025 son menores al plan actual, a propósito

Arias paga hoy $30.000 y debe $24.000 de 2025. Cabrera paga $43.000 y debe
$34.000. Acosta paga $28.000 y debe $22.000. **La cuota vieja se generó con el
precio viejo.** Sirve para comprobar que el importador escribe el monto del Excel y
no recalcula por el plan vigente.

El resto de las cuotas usa el precio real del plan activo de cada alumno, leído de
la base al generar el archivo.

### Sofía Morales sale gratis

DNI `32123456`, inscripta en **Patín y en Fútbol**. Va en dos filas, con deporte y
monto distintos: Patín en el grupo de tres meses ($30.000), Fútbol en el de solo
septiembre ($28.000). Es la prueba de que la clave del importador es **DNI+deporte**,
no DNI.

## El archivo de rechazos

Ocho filas, una regla rota por fila. **Verificado el 06/09** con `--solo-validar`:
el importador las rechaza todas, las informa juntas y no escribe nada.

| Fila | Qué rompe | Mensaje obtenido |
|---:|---|---|
| 2 | DNI que no existe | No existe un alumno para ese DNI y deporte |
| 3 | Deporte inexistente (`Hockey`) | El deporte 'Hockey' no existe |
| 4 | Monto cero | El monto debe ser numérico y mayor que cero |
| 5 | Monto negativo | ídem, más el aviso de DNI+deporte repetido |
| 6 | Mes 13 (`132026`) | El período debe tener formato mmYYYY válido desde 2025 |
| 7 | Anterior a 2025 (`122024`) | ídem |
| 8 | Monto sin período | Cada monto debe tener su período mmYYYY y viceversa |
| 9 | DNI+deporte repetido | Se repite DNI + deporte; ya figura en la fila 4 |

## Cómo se consiguen los cuatro estados

| Estado | Cómo llegar | Cuándo |
|---|---|---|
| **DEUDOR** | Ya están los 60, por no tener pagos. Después de la carga, los 48 con deuda siguen ahí | Inmediato |
| **AL_DÍA** | Cobrarle todo a alguien del grupo "sin deuda" o "solo septiembre" | Inmediato |
| **EN_PLAZO** | Cobrar una cuota vieja a alguien de "dos meses", dejando septiembre impago | Inmediato, hasta el día 10 |
| **MOROSO** | Lo mismo, pero con `día > dias_gracia` | **Del 11/09 en adelante**, o bajando `dias_gracia_cobranza` a 3 desde la pantalla de configuración |

Bajar los días de gracia es un cambio por pantalla, reversible, y de paso prueba
esa pantalla. Es la opción recomendada si la prueba se corre antes del 11.

## Lo que este diseño NO cubre

- **Punitorios por mora**: sin implementar (`ESTADO-ACTUAL.md` §F). Con
  `mora_porcentaje = 0` el sistema se comporta igual que hoy.
- **Generación mensual automática**: `GenerarDeudasMensualesCommand` exige
  asistencia del mes anterior o pago reciente. Con 0 asistencias no genera nada.
  Esa parte la cubre el simulador de tres meses, no esta carga.
