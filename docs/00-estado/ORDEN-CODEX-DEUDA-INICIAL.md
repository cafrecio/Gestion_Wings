# Orden de trabajo — Codex — Carga de deuda inicial y los cuatro estados

> Emitida el 06/09/2026 por Claude CAB, autorizada por Carlos.
> **Leer `AGENTS.md` completo antes de tocar un archivo.** No se repite acá.
> Diseño y justificación de la carga: `docs/06-pruebas/DISENO-DEUDA-INICIAL-V1.md`.

---

## Qué hay hecho y qué falta

**Hecho (no rehacer):** los dos Excel están generados desde los datos reales de
`wings_test` y **ya pasaron `--solo-validar`**. El bueno aprueba 81 deudas; el de
rechazos falla las ocho filas y no escribe nada.

| Archivo | Estado |
|---|---|
| `docs/06-pruebas/DEUDA-INICIAL-PRUEBA-V1.xlsx` | 48 filas, 81 cuotas, $2.997.000. Validado |
| `docs/06-pruebas/DEUDA-INICIAL-RECHAZOS-V1.xlsx` | 8 filas, todas rechazadas. Validado |
| `docs/06-pruebas/generar-deuda-inicial.php` | Regenera ambos desde la base |
| `docs/06-pruebas/DISENO-DEUDA-INICIAL-V1.md` | Qué demuestra cada grupo |

**Falta:** ejecutar la carga y mover el tablero hasta que se vean los cuatro
estados. Eso es esta orden.

---

## Límites

**No tocar:**

- `resources/views/**`, `resources/css/app.css`, `resources/views/components/ds/**`
- Los dos `.xlsx` y el generador. Si un dato no cierra, **frenar y reportar**, no
  editar el Excel a mano: se pierde la trazabilidad con el generador.
- La lógica de `CobranzaEstadoService`. Esta prueba la mide, no la corrige.

**No arreglar defectos durante la prueba.** Registrar y seguir. La corrección va
después, con la lista completa a la vista.

Antes de cerrar cada paso:

```bash
git diff --stat -- resources/views resources/css   # debe estar vacío
php artisan test                                    # 119 pruebas deben pasar
```

---

# ORDEN DE EJECUCIÓN

Es una cadena. **Si se traba un paso, frenar y reportar**: lo que sigue falla en
cascada por una sola causa y el informe queda inservible.

---

## Paso 0 · Los catálogos que faltan — BLOQUEANTE, va primero

**Hallazgo verificado el 06/09 por Claude CAB.** `wings_test` tiene **2 rubros y 2
tipos de caja**. `CatalogosSeeder` define **8 rubros y 5 tipos de caja**.

| Qué | Definido | En la base | Faltan |
|---|---:|---:|---|
| Rubros | 8 | 2 | Intereses, Servicios, Gastos Operativos, Alquileres, Torneos, Indumentaria |
| Tipos de caja | 5 | 2 | Banco Nación, Banco Nación ahorro, Banco Galicia |

**Por qué bloquea:** el único subrubro que afecta caja es `Cuota Mensual`. Hoy **no
se puede registrar un solo gasto en la caja operativa**, así que el flujo
caja → cierre → validación → cashflow no se puede terminar.

**Hay una contradicción documental que hay que resolver antes**, no después:
`PRUEBA-HUMANA-V1.md:44` dice "Rubros: 8, con 15 subrubros… es el mismo punto de
partida que va a tener el cliente el día uno". `PRIMERA-CARGA-V1.md:21` dice que la
base queda a propósito solo con Cuotas y Sueldos. **Los dos no pueden ser ciertos.**

*Qué hacer:* correr `php artisan db:seed --class=CatalogosSeeder` (es idempotente,
`updateOrCreate` contra el nombre) y **verificar que no tocó** los subrubros de
sueldo de los profesores ni los `Op-` de los operativos. Después corregir el
documento que quedó mintiendo y decir cuál en el informe.

*Aceptación:* 8 rubros, 5 tipos de caja, y los 6 subrubros de sueldo intactos.

---

## Paso 1 · Validar los dos Excel de nuevo, en tu máquina

```bash
php artisan wings:importar-deuda-inicial docs/06-pruebas/DEUDA-INICIAL-PRUEBA-V1.xlsx --solo-validar
php artisan wings:importar-deuda-inicial docs/06-pruebas/DEUDA-INICIAL-RECHAZOS-V1.xlsx --solo-validar
```

*Aceptación:* el primero aprueba **81 deudas**. El segundo rechaza y lista **las
ocho filas juntas**, sin escribir nada.

**Si el primero no da 81, frenar.** Significa que la base cambió respecto de cuando
se generó el archivo, y hay que regenerarlo con `generar-deuda-inicial.php` antes de
seguir — no editarlo.

---

## Paso 2 · La carga real

```bash
php artisan wings:importar-deuda-inicial docs/06-pruebas/DEUDA-INICIAL-PRUEBA-V1.xlsx
```

*Aceptación, verificada contra la base y no contra la salida del comando:*

- `deuda_cuotas` pasa de 0 a **81** filas.
- Todas `pendiente`, `monto_pagado = 0`, sin imputaciones en `pago_deuda_cuota`.
- Suma de `monto_original` = **$2.997.000**.
- **48 alumnos** con al menos una deuda; **12 sin ninguna**.
- Sofía Morales (DNI `32123456`) tiene **3 deudas en Patín y 1 en Fútbol**, con los
  montos de cada plan. Es la prueba de que la clave es DNI+deporte.
- `alumnos`, `alumno_planes`, `pagos` y `users` **sin cambios**.

---

## Paso 3 · Que rechace la segunda corrida

```bash
php artisan wings:importar-deuda-inicial docs/06-pruebas/DEUDA-INICIAL-PRUEBA-V1.xlsx
```

*Aceptación:* rechazo explícito por deuda ya existente. **Siguen habiendo 81 filas,
no 162.** No suma ni sobrescribe.

---

## Paso 4 · La reversión, y volver a cargar

Es el paso que nadie prueba y el que salva una carga mal hecha en producción.

```bash
php artisan wings:importar-deuda-inicial docs/06-pruebas/DEUDA-INICIAL-PRUEBA-V1.xlsx --revertir
php artisan wings:importar-deuda-inicial docs/06-pruebas/DEUDA-INICIAL-PRUEBA-V1.xlsx --solo-validar
php artisan wings:importar-deuda-inicial docs/06-pruebas/DEUDA-INICIAL-PRUEBA-V1.xlsx
```

*Aceptación:* la reversión deja `deuda_cuotas` en **0**. La validación vuelve a
aprobar 81 sin conflictos. La recarga deja 81 otra vez. **Alumnos y planes
intactos** en los tres momentos.

---

## Paso 5 · El estado inicial, medido

Antes de cobrar nada, con los 81 cargados.

*Aceptación:* **los 60 alumnos en DEUDOR**, incluidos los 12 sin deuda. Es la
condición `!$tienePagos` de `CobranzaEstadoService:234`, no un error.

**Registrar la pantalla de cobranza con esos 60 en DEUDOR.** Si muestra otra cosa,
ese desacuerdo entre la pantalla y el servicio es el hallazgo más importante de todo
este paso.

---

## Paso 6 · Los cobros, por pantalla — acá empieza la prueba de verdad

**Desde el navegador, con la sesión del OPERATIVO.** El operativo abre la caja,
cobra y la cierra. No se llaman servicios: si algo no se puede hacer desde una
pantalla, **eso mismo es el hallazgo**.

Abrir caja primero. Después, en este orden:

| # | A quién | Qué cobrar | Estado esperado después |
|---|---|---|---|
| 6.1 | Uno de **sin deuda** | septiembre completo | **AL_DÍA** |
| 6.2 | Uno de **solo septiembre** | la cuota completa | **AL_DÍA** |
| 6.3 | Uno de **solo septiembre** | **pago parcial** | **EN_PLAZO** (hasta el día 10) |
| 6.4 | Uno de **dos meses** | una sola cuota | Tiene que imputarse a **agosto**, no a septiembre. Queda **EN_PLAZO** |
| 6.5 | El mismo de 6.4 | la cuota que queda | **AL_DÍA** |
| 6.6 | Uno de **tres meses** | una sola cuota | Se imputa a **julio**. Sigue **DEUDOR** (le quedan impagas anteriores al mes vigente) |
| 6.7 | Uno de **deuda vieja 2025** | una cuota | Se imputa al período **más viejo de 2025**. El FIFO cruza de año |

**6.4, 6.6 y 6.7 son el corazón del paso.** Si el sistema imputa al mes más nuevo en
lugar del más viejo, es un defecto de plata y hay que frenar la prueba y reportarlo
de inmediato.

*Aceptación:* después de cada cobro, verificar **en la base** a qué `deuda_cuota` se
imputó el pago (`pago_deuda_cuota`), no solo lo que dice la pantalla.

---

## Paso 7 · MOROSO

`dias_gracia_cobranza = 10` y hoy es día 6: **MOROSO es inalcanzable hasta el 11/09.**

*Qué hacer:* bajar `dias_gracia_cobranza` a **3** desde la pantalla de configuración
—no por SQL, la pantalla es parte de la prueba—, comprobar que los que tienen
septiembre impago y algún pago pasan a **MOROSO**, y **dejarlo de nuevo en 10** al
terminar.

*Aceptación:* el estado cambia con la configuración, y vuelve al bajarla. Registrar
el valor con el que quedó la base.

**Ojo:** `Configuracion::set()` usa `update()`; sobre una clave que no existe no hace
nada y no avisa (`PRIMERA-CARGA-V1.md`, hallazgo del 06/09). Verificar que la fila
quedó realmente escrita, no que la pantalla dijo que sí.

---

## Paso 8 · Condonación

Con el **ADMIN**, condonar la deuda de Domínguez (`42691735`, Patín, `052025`) y la
de Cejas (`42509736`, Fútbol, `112025`). Motivo obligatorio, entre 10 y 500
caracteres.

*Aceptación:* la deuda queda `condonada`, **deja de contar como impaga**
(`CobranzaEstadoService::estaImpaga()` la excluye) y el estado del alumno mejora. No
se creó ningún pago ni movimiento de caja.

---

## Paso 9 · Cerrar la caja y validar

Con el **OPERATIVO** cerrar la caja del día. Con el **ADMIN** validarla.

*Aceptación:* la caja validada se refleja en cashflow
(`CashflowIntegracionCajaService`). El total del cashflow coincide con lo cobrado.
**Este paso necesita el Paso 0 hecho**: sin subrubros que afecten caja no se pueden
registrar los gastos que hacen realista el cierre.

---

## Qué entregar

Un `RESULTADO-DEUDA-INICIAL-V1.md` en `docs/06-pruebas/` con:

1. **PASA / FALLA / NO SE PUDO por cada paso**, con los números reales de la base.
2. Los cuatro estados, cada uno con el alumno concreto que lo alcanzó.
3. Para cada falla: qué se esperaba, qué pasó, si fue validación o pantalla de
   error, y **si quedó algo escrito en la base pese al error** — es la falla más
   grave y la más fácil de pasar por alto.
4. La contradicción del Paso 0, resuelta, diciendo qué documento se corrigió.

Y la entrada en `LOG-CODEX.md`, firmada.

---

## Por qué este orden

0 va primero porque es **bloqueante**: sin catálogos no hay caja, y el Paso 9 se cae.

1→4 son el importador solo, sin nada más en juego. Si algo falla ahí, falla con la
base en un estado conocido y se puede repetir. Meter cobros antes de probar la
reversión significa no poder revertir nunca más: el comando solo retira deudas
pendientes, intactas y sin imputaciones.

5 mide el punto de partida antes de tocarlo. Sin esa foto, después no se sabe qué
cambió por los cobros y qué ya estaba.

6→8 mueven el tablero de a un estado por vez, en orden de riesgo creciente: primero
el cobro simple, después el FIFO, después la configuración, y al final la
condonación, que es la única que borra deuda sin plata de por medio.

9 va último porque necesita todo lo anterior cargado para que el cierre sea real.
