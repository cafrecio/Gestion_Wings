# Prueba funcional completa — como si fuera una persona

> Se ejecuta **desde el navegador**, cargando los datos a mano en cada pantalla.
> No se llaman servicios ni se escribe en la base directamente: si algo no se puede
> hacer desde una pantalla, **eso mismo es el hallazgo**.

## Por qué así

Los dos defectos encontrados el 30/08 —el rubro sin observación y el alumno sin
celular— **no los detectó ninguna prueba automatizada**. Aparecieron cuando una
persona dejó un campo vacío. Las 65 pruebas del sistema pasaban igual.

Esta prueba busca esa clase de cosa: lo que se rompe cuando alguien usa el sistema
de verdad.

---

## Ambiente

| Qué | Valor |
|---|---|
| Dirección | `http://gestion-wings` |
| Base de datos | **`wings_test`**, separada. La base de trabajo no se toca |
| Estado inicial | Catálogos cargados, **cero usuarios operativos, cero alumnos, cero movimientos** |

**Ya está preparado.** El `.env` apunta a `wings_test`, con las migraciones y
`CatalogosSeeder` aplicados.

**Cuenta para empezar:** `admin@wings.test` / `PruebaWings2026`

### Lo que ya viene cargado (no hay que crearlo)

- **Deportes:** Patín (liquida por HORA), Fútbol (liquida por COMISIÓN)
- **Niveles:** Principiantes, Intermedias, Avanzadas
- **Tipos de caja:** Efectivo, Banco Nación, Mercado Pago, Banco Nación ahorro, Banco Galicia
- **Rubros:** 8, con 15 subrubros

Es el mismo punto de partida que va a tener el cliente el día uno.

---

## Cómo reportar

Para **cada paso**: `PASA` / `FALLA` / `NO SE PUDO`.

Cuando falla, hacen falta tres cosas:

1. **Qué se esperaba** y **qué pasó**.
2. **Si fue un mensaje de validación o una pantalla de error.** Un campo obligatorio
   que avisa está bien; el mismo campo que tira una pantalla de excepción es un bug.
3. **Si quedó algo escrito en la base pese al error.** Es la falla más grave y la más
   fácil de pasar por alto.

**No arreglar nada durante la prueba.** Primero la lista completa, después los arreglos.

---

# ORDEN OBLIGATORIO

Es una cadena, no tres pruebas sueltas. Cada actor depende de lo que hizo el anterior.

```
ADMIN carga la estructura  →  OPERATIVO opera  →  PROFESOR toma asistencia
```

**Si se traba un eslabón, frenar y reportar.** No saltear: lo que sigue va a fallar
en cascada por una sola causa y el informe queda inservible.

---

# BLOQUE A — El administrador arma el club

## A1 · Un deporte más

| Campo | Valor |
|---|---|
| Nombre | `Hockey` |
| Tipo de liquidación | `HORA` |

*Esperado:* se crea. Ahora hay 3 deportes.

## A2 · Deporte repetido — tiene que rechazarlo

Intentar crear otro con nombre `Patín`.

*Esperado:* **mensaje de validación** diciendo que ya existe. **No** una pantalla de
error. Y que siga habiendo 3 deportes, no 4.

Probar también `patín` en minúscula y `PATIN` sin tilde: el sistema debería
reconocerlos como el mismo nombre.

## A3 · Profesores

| # | Nombre | Apellido | DNI | Deporte | Valor hora / Comisión |
|---|---|---|---|---|---|
| P1 | Jorge | Mitre | 20111222 | Patín | valor hora `12000` |
| P2 | Silvia | Roca | 21333444 | Fútbol | comisión `40` |

Completar dirección y localidad, que son obligatorias.

*Esperado:* los dos se crean. **Verificar además** que a cada uno se le creó
automáticamente su subrubro de sueldo dentro del rubro **Sueldos**. Mirar la pantalla
de Rubros: tienen que aparecer dos subrubros nuevos con el apellido de cada profesor.

## A4 · Profesor con DNI repetido

Crear otro con DNI `20111222`.

*Esperado:* mensaje de validación. No se crea.

## A5 · Profesor sin dirección

Dejar la dirección vacía.

*Esperado:* mensaje de validación en castellano. **Si sale una pantalla de error, es
un bug** — la columna no acepta vacío.

## A6 · Grupos

| # | Deporte | Nivel |
|---|---|---|
| G1 | Patín | Principiantes |
| G2 | Patín | Avanzadas |
| G3 | Fútbol | Intermedias |

## A7 · Grupo repetido

Intentar crear otra vez Patín + Principiantes.

*Esperado:* rechazado. Siguen siendo 3 grupos.

## A8 · Planes — ATENCIÓN, ACÁ SE TRABA TODO

**Los planes NO están en el menú lateral.** Se cargan **entrando a cada grupo**, en
la sección de precios por frecuencia.

**Sin planes no se puede crear ningún alumno**, y el campo para elegirlos ni siquiera
aparece en el formulario. Si se saltea este paso, todo el bloque B es imposible.

| Grupo | Frecuencia | Precio |
|---|---|---|
| G1 Patín Principiantes | 2 veces/semana | 28000 |
| G1 Patín Principiantes | 3 veces/semana | 35000 |
| G2 Patín Avanzadas | 3 veces/semana | 42000 |
| G3 Fútbol Intermedias | 2 veces/semana | 30000 |

*Verificar de paso:* al escribir `28000` el campo tiene que mostrar `28.000`.

## A9 · Precio inválido

Cargar un plan con precio `0`, y otro con `-5000`.

*Esperado:* rechazados los dos. **Si acepta el cero o el negativo, es un hallazgo**:
después se puede cobrar una cuota de cero pesos.

## A10 · Usuarios

| # | Nombre | Email | Rol | Vinculado a |
|---|---|---|---|---|
| U1 | Operativo Prueba | `operativo@wings.test` | OPERATIVO | — |
| U2 | Jorge Mitre | `jorge@wings.test` | PROFESOR | profesor P1 |

Contraseña para los dos: `PruebaWings2026`

*Esperado:* al elegir rol PROFESOR el formulario debe **exigir** a qué profesor se
vincula.

## A11 · Email repetido

Crear otro usuario con `operativo@wings.test`.

*Esperado:* rechazado con mensaje.

## A12 · Alumnos

**Los cinco primeros tienen que crearse bien:**

| # | Nombre | Apellido | DNI | Nacimiento | Celular | Email | Grupo | Plan |
|---|---|---|---|---|---|---|---|---|
| A1 | Lucía | Fernández | 45111222 | 2012-04-10 | 11-5000-0001 | luf@test.com | G1 | 2/sem |
| A2 | Mateo | Gómez | 45222333 | 2011-08-22 | 11-5000-0002 | mag@test.com | G1 | 3/sem |
| A3 | Valentina | Ruiz | 44333444 | 2009-02-15 | 11-5000-0003 | var@test.com | G2 | 3/sem |
| A4 | Benjamín | Torres | 43444555 | 2007-11-30 | 11-5000-0004 | bet@test.com | G3 | 2/sem |
| A5 | Emma | Castro | 46555666 | 2014-06-05 | 11-5000-0005 | emc@test.com | G1 | 2/sem |

Los menores de 18 exigen nombre y teléfono del tutor: completarlos con
`Tutor Apellido` y `11-4000-000X`.

**Verificar la fecha de alta:** tiene que venir con la de hoy puesta, y debe poder
cambiarse a una anterior (probarlo con A3, poniéndole `2024-03-01`).

## A13 · Alumnos que tienen que fallar

| Caso | Qué hacer | Esperado |
|---|---|---|
| Sin celular | Dejarlo vacío | **Mensaje de validación**, no pantalla de error |
| DNI repetido | Usar `45111222` otra vez | Rechazado |
| Sin grupo | No elegir grupo | Rechazado |
| Menor sin tutor | Fecha de nacimiento 2015, tutor vacío | Rechazado pidiendo el tutor |
| Nacimiento futuro | `2030-01-01` | Rechazado |

**Después de cada rechazo, mirar el listado:** no tiene que haber quedado ningún
alumno a medias.

---

# BLOQUE B — El operativo trabaja

Salir e iniciar sesión como `operativo@wings.test`.

## B1 · Lo que NO tiene que poder

Intentar entrar, escribiendo la dirección a mano, a: `usuarios`, `cashflow`,
`configuraciones`, `liquidaciones`, `rubros`, `deportes`, `profesores`.

*Esperado:* **ninguna** se abre. Anotar qué pasa en cada caso.

## B2 · Cobrar la primera cuota

Cobrar a **Lucía Fernández** (A1) la cuota del mes en curso, **$28.000 en Efectivo**.

*Esperado en la base:* se crea el pago, la imputación y el movimiento de caja.

**Y esto es lo que hace valiosa la prueba — verificar en OTRAS pantallas:**

| Dónde mirar | Qué tiene que haber pasado |
|---|---|
| Ficha del alumno | El estado de cobranza cambió, y el pago figura en el historial |
| Movimientos | Aparece el ingreso |
| Detalle de la caja | Aparece con el nombre del alumno y el período |
| **Cashflow** | **NO tiene que aparecer todavía** — recién al validar la caja |
| Recibo | Se descarga y muestra el período, el monto y el medio de pago |

## B3 · Cobro dejando deuda anterior

Requiere que el alumno deba más de un mes. Si no hay deuda vieja, **anotarlo como no
probable** y seguir.

Si la hay: cobrar solo el mes nuevo.

*Esperado:* aparece un **aviso** con la cantidad de meses y el monto adeudado, pide un
**motivo**, y recién entonces cobra. **No debe bloquear el cobro.**

## B4 · Cancelar un cobro

Cobrar a **Mateo Gómez** (A2) y después cancelar ese cobro, con motivo.

*Esperado:* la deuda vuelve a pendiente, el pago queda anulado, el movimiento queda
cancelado, **y no queda ninguna imputación colgada**. El recibo tiene que salir marcado
como anulado.

## B5 · Cerrar y validar la caja

Cerrar la caja como operativo. Iniciar sesión como admin y validarla.

*Esperado:* **recién ahora** aparece el ingreso en cashflow, y el saldo de Efectivo
sube exactamente por lo cobrado.

**Validar una segunda vez** la misma caja: no tiene que duplicar nada.

## B6 · Clases

Como **admin** (el operativo no puede crear clases):

| # | Grupo | Profesor | Fecha | Horario |
|---|---|---|---|---|
| C1 | G1 | Jorge Mitre | hoy | 18:00–19:00 |
| C2 | G2 | Jorge Mitre | hoy | 18:30–19:30 |

*Esperado:* **C2 tiene que ser rechazada**: el profesor ya está ocupado en ese horario.
El mensaje debe nombrarlo.

Crear entonces C2 de 20:00 a 21:00, que sí debe aceptarse.

---

# BLOQUE C — El profesor

Iniciar sesión como `jorge@wings.test`.

## C1 · Lo que ve y lo que no

*Esperado:* menú mínimo. **No** llega a alumnos, caja, movimientos ni cobros.
Intentar entrar por dirección directa y anotar qué pasa.

## C2 · Tomar asistencia

Abrir la clase C1 y marcar presentes a **Lucía y Emma**, ausente a **Mateo**.

*Esperado:* se guarda. **Verificar en otras pantallas:**

| Dónde | Qué |
|---|---|
| Listado de clases | Pasa a figurar con la lista tomada |
| Contador de presentes | Dice 2 |
| Menú "Sin asistencia" | **El número baja** |

## C3 · Corregir la asistencia

Volver a la misma clase y cambiar a Mateo a presente.

*Esperado:* como la lista ya estaba tomada, **tiene que pedir un motivo**. Sin motivo,
no debe guardar.

## C4 · Alumno de otro grupo

*Esperado:* la lista solo muestra alumnos del grupo de la clase. Benjamín (G3) **no**
debe aparecer.

---

# BLOQUE D — Cierre

## D1 · Liquidación del profesor

Como admin, generar la liquidación de Jorge Mitre del mes en curso.

*Esperado:* aparece la clase con asistencia, valorizada a **$12.000**, y **cada línea
debe decir el nombre del grupo completo**, tipo `Clase 30/08/2026 - Patín — Principiantes`.
Si alguna sale con el grupo vacío, es un hallazgo.

## D2 · Recuento final

Contar en cada pantalla y anotar: deportes, profesores, grupos, planes, alumnos,
usuarios, clases, pagos, movimientos y filas de cashflow.

Contrastar con lo que se cargó. **Cualquier diferencia es un hallazgo**, incluso a
favor: un registro de más significa que algo se guardó dos veces.

---

# Al terminar

Escribir el resultado en `docs/06-pruebas/RESULTADO-PRUEBA-HUMANA-V1.md`, con el
resultado de cada paso y, al final, **la lista de lo que no se pudo probar y por qué**.

**No hace falta limpiar la base:** `wings_test` es descartable y se vuelve a armar
desde cero cuando haga falta.
