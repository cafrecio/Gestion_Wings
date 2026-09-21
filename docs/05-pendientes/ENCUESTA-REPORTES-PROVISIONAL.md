# Reportes — decisiones de la encuesta (provisional y removible)

**Corte: 21/09/2026. Encuesta pausada por Carlos.**
Fuente: entrevista de Carlos con Codex iniciada el 13/09 y continuada el 21/09.
Este archivo preserva decisiones y preguntas; no certifica implementación ni
sustituye todavía el contrato final. Puede retirarse cuando todo su contenido
vigente haya sido incorporado al contrato y a las tareas correspondientes.
No borrarlo mientras sea la única fuente de alguna decisión.

## 1. Objetivo y alcance

Diseñar Reportes completo con una entrevista en lenguaje cotidiano, una pregunta
abierta por vez y respuestas cortas. ADMIN necesita saber si el negocio es rentable
y cómo evolucionó. No se busca una contabilidad formal.

Clases particulares va en una rama separada para no ampliar los tiempos de la
implementación y pruebas ya previstas. Su contrato está en
[Clases particulares V1](../02-contratos/Wings-Contrato-Clases-Particulares-V1.md).
FIN-12 se separó como tarea independiente: no esperar particulares para ella.
No crear ramas ni implementar por leer esta nota.

## 2. Períodos, evolución e historia

- Mostrar hasta los últimos **seis meses cerrados con datos**; no inventar meses
  vacíos para completar seis ni incluir el mes en curso en esa comparativa.
- El mes actual tiene un apartado propio.
- Permitir seleccionar un mes pasado y consultar el mismo detalle.
- Comparar alumnos activos actuales por deporte y nivel con los activos que
  había al cierre del mes anterior, no con su estado actual trasladado al pasado.
- Permitir filtrar el reporte por deporte.

La historia debe distinguir el momento real del hecho del momento de carga o
confirmación. Carlos precisó que un mes pasado **puede actualizarse** por carga
tardía o validación posterior; no es una fotografía inmutable de lo conocido ese día.

### Ejemplos acordados y correcciones de la entrevista

1. Una cuota de agosto efectivamente cobrada en septiembre queda por cobrar al
   cierre de agosto y es ingreso de septiembre. No es resultado de agosto.
2. Un movimiento de agosto confirmado en septiembre pasa a confirmado **en agosto**.
   Antes estaba pendiente de confirmación; no se vuelve ingreso de septiembre.
3. Pago real del 31/08 olvidado y registrado el 10/09: modifica los ingresos y
   la deuda histórica de agosto, pero se rinde en la caja del 10/09.
4. La misma regla de carga tardía aplica a egresos.

Por tanto, distinguir fecha del movimiento, fecha de registro y caja de rendición.
No duplicar ingresos o egresos al cruzar ambas lecturas. La frase inicial
«cómo estaba el mes al cierre» queda precisada por estas decisiones posteriores:
se respeta la fecha del hecho y se admiten hechos registrados/confirmados después.

## 3. Apartado del mes: indicadores pedidos

| Grupo | Contenido |
|---|---|
| Cuotas cobradas | Todo lo cobrado durante el mes, aunque corresponda a cuotas de meses anteriores; por fecha real del cobro |
| Cuotas impagas | Saldo pendiente de cuotas del mes consultado |
| Cuotas adeudadas | Saldo pendiente de períodos anteriores al mes consultado |
| Alumnos activos | Total, total por deporte y total por nivel |
| Comparación de alumnos | Totales por deporte y nivel del mes anterior, al cierre de ese mes |
| Alumnos en evaluación | Incluye activos e inactivos, desglosados solo por deporte, no por nivel |
| Ingresos | Total y desglose por rubro y subrubro |
| Egresos | Total y desglose por rubro y subrubro |
| Resultado del negocio | Ingresos de cuotas y clases menos egresos |
| Resultado global | Todos los ingresos menos todos los egresos |
| Saldos de caja | Saldos por tipo de caja y total disponible |

- Cuotas impagas y deuda anterior muestran **importe pendiente y cantidad de alumnos**.
- Si un alumno debe el mes y meses anteriores, se cuenta en ambos grupos: son
  categorías distintas. No presentar su suma como cantidad de personas únicas.
- Cada indicador permite entrar al detalle que compone el total.
- Debe quedar estético, claro y fácil de leer, conservando el sistema visual Wings.
  La entrevista no constituye autorización para rediseñar vistas ajenas al alcance.

## 4. Confirmados y sin confirmar

Carlos pidió separar, no mezclar ni ocultar, los datos confirmados y sin confirmar:

- Ingresos y egresos, con sus desgloses.
- Resultado del negocio y resultado global.
- Saldos por tipo de caja y total disponible.
- Cuotas cobradas.

La confirmación posterior actualiza la categoría del movimiento en su mes de origen.
No contar el mismo movimiento en ambas categorías ni como ingreso nuevo al validar.
La conciliación técnica de saldos y cajas deberá revisarse antes de implementar;
la entrevista no decide tablas ni fórmulas para duplicar el saldo inicial entre grupos.

## 5. Caja real, por pagar y proyecciones

- **Caja actual = dinero real**, no un resultado que incluya plata todavía no cobrada
  o gastos todavía no pagados.
- Total disponible acumulado hasta hoy, incluyendo saldos de meses anteriores,
  con separación confirmado/sin confirmar. Para meses pasados habrá que conservar
  la distinción entre el corte consultado y el saldo actual.
- Sueldos pagados son egresos reales; sueldos pendientes se muestran aparte.
- **Por pagar:** solamente liquidaciones de profesores **cerradas y pendientes de pago**.
- Avisar que hay liquidaciones sin cerrar; el aviso lleva al módulo **Liquidaciones**.
  No sumar liquidaciones abiertas al importe por pagar.
- Ingresos proyectados: únicamente deudas ya generadas pendientes de cobro,
  separadas entre mes en curso y anteriores; no inventar cuotas futuras.
- Alquileres, servicios y otros gastos futuros quedan fuera del alcance: no hay
  un sistema contable de cuentas a pagar. Se usan varios clubes/espacios y no se
  hará calculable ese gasto ahora.
- Agregar un valor sueldo al OPERATIVO se mencionó como posibilidad, pero quedó
  fuera del alcance actual. No implementarlo como decisión aprobada.
- Las proyecciones van aparte y no modifican resultados de movimientos reales.

## 6. Alumnos y actividad

- Los totales por deporte y nivel corresponden a alumnos activos.
- Carlos define que un alumno inactivo que paga pasa a activo, **también ante pago
  parcial**: la intención es reflejar que está concurriendo.
- Esto es una decisión de negocio recogida en la entrevista, no una comprobación
  del comportamiento actual del sistema. Revalidar con los contratos y el flujo
  de cobro antes de proponer cambios; no implementar reactivación en una consulta de reporte.
- Alumnos en evaluación incluye activos e inactivos y se desglosa solo por deporte.
- Debe haber un botón que lleve al módulo donde se define su estado.

## 7. ADMIN, OPERATIVO y lista única de Revisión

- Los agregados económicos generales, resultados, saldos globales y demás
  indicadores de gestión del negocio son **solo ADMIN**.
- OPERATIVO sí ve la información propia del trabajo de cobranza: deuda del mes,
  deuda anterior, importes, alumnos que deben, alumnos que pagaron y alumnos
  posiblemente inactivos/en evaluación.
- Los alumnos que pagaron son visibles **sin importar a quién le pagaron**.
  No limitar a cobros realizados por ese OPERATIVO.
- Al iniciar se indicó acceso operativo a su caja diaria; eso no concede acceso
  al tablero agregado del negocio ni restringe permisos generales ya existentes.
- Hay **una sola lista** de posibles inactivos/alumnos en evaluación, compartida
  para ADMIN y OPERATIVO; no crear otra lista paralela desde Reportes.
- Conservar el nombre **Revisión** y enlazar el módulo existente.
- Criterio expresado por Carlos: el mismo del generador de deuda, no haber tenido
  asistencias en el último mes. Falta cotejar la ventana y condiciones exactas
  contra el generador; no afirmar que ya coincide ni inventar un segundo criterio.
- La entrevista no respondió por separado todas las acciones de resolución de
  Revisión: no deducir permiso de condonar deuda por poder ver o gestionar la lista.

## 8. Relación con documentación anterior

Consultar al cerrar la encuesta:

- [Contrato previo de Reportes](../02-contratos/Wings-Contrato-Reportes-V1.md).
- [Permisos y roles](../02-contratos/PERMISOS-ROLES.md).
- [Plan vigente](../07-evaluacion/PLAN-TRABAJO-IA-v2026-09-08.md), FIN-04 y POS-01.

Esta nota conserva lo dicho por Carlos y las precisiones posteriores. No convierte
propuestas del asistente en autorizaciones ni hallazgos de logs ajenos en hechos
verificados. Si difiere del contrato anterior, conservar la diferencia y resolverla
al redactar la nueva versión, antes de implementar.

## 9. Punto exacto para retomar

**Pregunta pendiente, todavía sin respuesta:**

> Al filtrar por deporte, ¿cómo querés mostrar los gastos generales que no
> pertenecen a uno en particular?

No asignar ni repartir gastos comunes por criterio del agente. Carlos pidió
parar la entrevista, guardar estas decisiones y subirlas a GitHub antes de seguir.
El diseño de Reportes y su contrato final siguen pendientes; no iniciar código.
