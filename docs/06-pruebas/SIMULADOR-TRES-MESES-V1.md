# Simulador de tres meses de club

> Definido con Carlos el 30/08/2026.
> **Especificación. Todavía no implementado.**

## Qué problema resuelve

La prueba humana encontró defectos reales, pero **no puede tocar el tiempo**. Y buena
parte del sistema solo existe en el tiempo:

- La generación mensual de deuda, que corre el día 1 y mira el mes anterior.
- Tres de los cuatro estados de cobranza dependen de meses cerrados y días de gracia.
- El cambio de plan que aplica al mes siguiente.
- Las liquidaciones, que son mensuales.

**Hoy nada de eso está probado.** Nunca. Ni una vez.

Tampoco está probada la **acumulación**: los defectos de plata no aparecen en la primera
operación, aparecen después de cobrar, cancelar, volver a cobrar, cambiar de plan y
validar cajas durante semanas.

## No reemplaza a la prueba humana

Un simulador que llama a los servicios **no habría encontrado los dos defectos del
30/08**: el precio en cero y no poder cobrarle a un alumno nuevo. Los dos estaban en las
pantallas, no en la lógica.

Son complementarias: la humana prueba que se puede usar, el simulador prueba que las
cuentas cierran.

---

## Por qué es viable

**Verificado el 30/08:** no hay una sola línea en `app/` que pregunte la fecha por fuera
de Laravel. Ni funciones nativas de PHP, ni `NOW()` dentro de una consulta. Los 28
archivos que trabajan con fechas usan la misma vía.

Eso permite **mover el reloj de todo el sistema desde un solo lugar**, sin tocar el
código de producción. Si hubiera aunque sea un lugar con el reloj real, la simulación
produciría datos incoherentes y no se podría confiar en el resultado.

---

## 1. Cada acción la hace quien corresponde

**Requisito que no se negocia.** El simulador **inicia sesión con el usuario adecuado
antes de cada acción**. Si hiciera todo como admin, no probaría nada del modelo de
permisos, que es donde este proyecto ya se equivocó varias veces.

| Rol | Qué hace en la simulación |
|---|---|
| **ADMIN** | Crea deportes, niveles, grupos, planes, profesores, usuarios y alumnos. Crea las clases. **Valida las cajas.** Genera y paga liquidaciones |
| **OPERATIVO** | **Abre la caja**, cobra cuotas, registra gastos, cancela cobros, **cierra la caja**. Toma asistencia |
| **PROFESOR** | Toma asistencia de sus clases |

**El admin no abre cajas.** Eso es del operativo, y la caja es su rendición.

**La asistencia la toman los dos**, profesor y operativo. El simulador tiene que
alternar, no usar siempre el mismo.

Debe haber **dos operativos**, no uno: es la única forma de ejercitar que una caja no
se pueda cerrar desde otro usuario, y que cada uno vea la suya.

---

## 2. Carga inicial

Se arma un club completo, como el día que arranca de verdad:

| Qué | Cuánto |
|---|---|
| Deportes | 2, uno que liquida por hora y otro por comisión |
| Niveles | 3 |
| Grupos | 4, repartidos entre los dos deportes |
| Planes | 2 o 3 por grupo, con precios distintos |
| Profesores | 3, con sus usuarios |
| **Alumnos** | **20**, repartidos en los grupos, con fechas de alta distintas |
| Usuarios | 1 admin, 2 operativos, 3 profesores |
| Tipos de caja | Los 5, **con saldo inicial** |
| Rubros y subrubros | Los del catálogo, más el subrubro de sueldo del operativo |

Los 20 alumnos no son iguales: distintos planes, distintas fechas de alta, y alguno que
entra a mitad de la simulación.

---

## 3. El recorrido: día por día, tres meses

### Todos los días

- Si hay clases, **se dictan y se toma lista**. Con faltas: nadie tiene asistencia
  perfecta, y las faltas son las que después alimentan el reporte de quién dejó de venir.
- Un operativo **abre su caja**, cobra cuotas y registra gastos.
- **Se cierra la caja** al final del día.
- **El admin la valida al día siguiente**, no el mismo día. Así siempre hay cajas
  pendientes de validar, que es lo que pasa en la realidad.

### Los cobros no son todos iguales

Distintos medios de pago, distintos montos, y todas estas situaciones a lo largo de los
tres meses:

- Pago completo de la cuota del mes.
- Pago parcial.
- Pago de dos o tres meses juntos, para ejercitar la imputación de más viejo a más nuevo.
- Cobro que después se cancela.
- Cobro a un alumno que arrastra deuda anterior.
- Primera cuota de un alumno recién inscripto.

### El día 1 de cada mes

Corre la generación de deuda, exactamente igual que lo haría el servidor.

### A lo largo de los tres meses

- Un alumno **se da de baja**.
- Otro **cambia de plan hacia arriba**, y otro **hacia abajo** habiendo ya asistido —que
  debe aplicar recién al mes siguiente.
- Uno **deja de venir** sin avisar.
- Uno **paga de más**.
- Se generan y se pagan las **liquidaciones** de cada profesor.
- **Una caja cierra con diferencia** y el admin **la rechaza**.

---

## 4. Cómo se ordena para que un fallo se pueda atribuir

**Mes 1: prolijo.** Todo funciona como debería. Si algo falla acá, es un defecto puro:
no hay ninguna situación rara que pueda explicarlo.

**Meses 2 y 3: el desorden real.** Cancelaciones, cajas rechazadas, pagos de más, bajas,
cambios de plan.

Es la única forma de tener cobertura completa **y** poder atribuir una falla. Si el caos
empieza el día uno, cuando algo no cuadre no se va a saber si es un defecto del sistema
o si el simulador generó una situación imposible.

---

## 5. Lo que se verifica — acá está todo el valor

Un simulador que genera tres meses y no comprueba nada solo deja una base más grande.

Al terminar **cada mes**, y otra vez al final, tiene que dar bien:

| Qué | Por qué importa |
|---|---|
| Lo pagado de cada deuda = suma de sus imputaciones | Si no cierra, hay plata cobrada que no se sabe a qué se aplicó |
| Cashflow = movimientos de cajas validadas, exacto | Ni un peso de más ni de menos |
| Ninguna caja sin validar aparece en cashflow | Es el control del admin; saltearlo lo vuelve decorativo |
| Saldo de cada medio de pago = saldo inicial + sus movimientos | Es lo que el dueño mira para saber cuánta plata tiene |
| Nadie tiene dos deudas del mismo mes | Una duplicada le cobra dos veces al alumno |
| Nadie tiene deuda anterior a su fecha de alta | Se le estaría cobrando por meses en los que no era alumno |
| El estado de cobranza coincide con las deudas reales | Es lo que dispara la cobranza |
| Total de cada liquidación = suma de sus líneas | Es lo que se le paga al profesor |
| Ningún cobro cancelado dejó imputaciones vivas | El defecto que ya apareció una vez |
| Ninguna cuota supera el precio del plan de ese período | Cobrar de más |

**Si una sola falla después de tres meses simulados, encontramos un agujero de plata que
ninguna prueba manual iba a ver.**

---

## 6. Reglas de ejecución

**Al azar, pero repetible.** Con una semilla fija, la misma corrida da siempre el mismo
resultado. Sin eso no se puede verificar que un arreglo funcionó: no se sabría si se
arregló o si simplemente no volvió a salir.

**No frena en el primer fallo.** Anota y sigue hasta terminar los tres meses. Si frenara,
cada corrida mostraría un solo problema y encontrarlos todos llevaría semanas.

**Base descartable.** Corre sobre una base propia que se arma desde cero en cada
ejecución. No toca `gestion_wings` ni `wings_test`.

**Prohibido en producción.** Aborta si el entorno no es local o de pruebas, igual que los
otros seeders.

**No toca vistas ni CSS.**

---

## 7. Qué entrega

Un informe con:

- Qué se generó: cuántos alumnos, cobros, movimientos, cajas, clases, liquidaciones.
- **El resultado de cada verificación, mes por mes.**
- La lista de fallas con el detalle suficiente para reproducirlas: qué alumno, qué
  período, qué monto.

---

## 8. Estado

**Especificado, no implementado.**

Conviene hacerlo **después** de cerrar los dos defectos abiertos de la prueba humana —el
precio en cero y no poder cobrar la primera cuota—. El segundo es bloqueante: sin poder
cobrarle a un alumno nuevo, el simulador no puede cobrar nada.
