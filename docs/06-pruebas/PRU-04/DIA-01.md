# Día 1 — 10/10/2026

Se cobraron $164.000; la caja de Sandra quedó cerrada y validada, sin diferencia.
Las cuatro fichas quedaron correctas; Mariela guardó 6 presentes y 2 ausentes.
El día no pasa completo: Inicio no concilia y la profesora ve clases ajenas.

Sitio: https://test.gestionar-te.com.ar. Expectativas anotadas antes de actuar. Chrome real, con clics automatizados en menú, botones y formularios; no carga por programa, direcciones internas escritas, consultas a la base ni arreglos. El navegador integrado falló; se usó otro Chrome aislado.

| Tarea | Esperado | Lo que pasó | ¿Coincide? |
|---|---|---|---|
| 1. Admin prepara | Inicio: 100 alumnos y deuda $2.174.000; mostrador Efectivo | Inicio: $2.119.000 y sin cantidad de alumnos. Alumnos sí muestra 100. Efectivo configurado. | Parcial |
| 2. Sandra abre | Caja a su nombre, cambio $10.000, puede cobrar | Caja 1 ABIERTA, Sandra Vidal, inicial $10.000; cobros habilitados. | Sí |
| 3a. Julián Navarro | Octubre $48.000 efectivo; ficha y recibo | Pago 1, octubre $48.000; ficha Al día; recibo pedido. | Sí |
| 3b. Mateo Rodríguez | $38.000 Mercado Pago; cancela septiembre | Se eligió septiembre: pago 2 $38.000; octubre sigue pendiente. Ficha revisada; recibo pedido. | Sí |
| 3c. Emma Fernández | $45.000 efectivo; cancela agosto | Se eligió agosto: pago 3 $45.000; septiembre/octubre siguen pendientes. Ficha revisada; recibo pedido. | Sí |
| 3d. Lucas Gómez | $33.000 efectivo: inscripción $5.000 primero, octubre $28.000 después | Pantalla anuncia inscripción primero. Pago 4 $33.000; inscripción sin saldo y ficha Al día. Dos movimientos: $28.000 cuota y $5.000 inscripción; recibo pedido. | Sí en importes; orden interno no comprobado |
| 4. Profesora | Solo sus clases; guarda presentes/ausentes; sin plata ni listado general de alumnos | Mariela guardó clase 47 de hoy, 6 presentes/2 ausentes; reabierta, conserva las marcas. Menú solo Clases. También ve clases ajenas y abrió la 5 de Lucía, con sus 26 alumnos y Guardar. No modificó esa clase. | Parcial |
| 5. Sandra cierra | Efectivo $136.000, Mercado Pago $38.000, diferencia $0 | CERRADA; contado/esperado $136.000; diferencia $0; cambio $10.000; entrega $126.000. Cobrado: efectivo $126.000 y MP $38.000. | Sí |
| 6. Admin valida y revisa | Deuda $2.010.000; $164.000 una sola vez; tres pantallas coherentes | VALIDADA. Cobranza: $2.010.000, 100 activos. Cashflow de octubre: $164.000, cinco filas sin repetir (Lucas desglosado). Inicio: deuda $1.960.000, ingresos $0 y cinco movimientos por clasificar. | No |

Hallazgos — las etiquetas indican qué frena la aprobación del ensayo:

- **Frena:** después de validar, Inicio dice ingresos $0 y Cashflow $164.000; Inicio avisa cinco movimientos por clasificar. No se investigó ni corrigió la causa. [Inicio](07-inicio-despues-validar.png) / [Cashflow](09-cashflow-164000-contraste.png).
- **Frena:** Mariela abre por Ver una clase de Lucía, ve alumnos y botón Guardar. No se probó guardar en clase ajena. [Listado](04-profesora-ve-clases-ajenas.png) / [Clase ajena](05-profesora-abre-clase-de-colega.png).
- **Molesta:** Inicio muestra deuda inicial $55.000 menor que la esperada y final $50.000 menor que Cobranza. Causa no comprobada. [Antes](01-inicio-deuda-distinta.png) / [Cobranza final](08-cobranza-deuda-correcta-contraste.png).
- **Molesta:** Cobrar llama Total pendiente a la suma de deuda y dos meses adelantados: Julián aparece con $144.000, aunque debe $48.000. El total elegido y el pago sí fueron $48.000. [Captura](03-julian-total-incluye-adelantados.png).
- **Molesta:** Cobrar anuncia 40 alumnos con deuda pero lista 100, incluidos varios con saldo $0. [Captura](02-cobrar-lista-confusa.png).
- **Mejora:** mostrar cantidad de alumnos en Inicio y nombres en Cashflow: hoy aparecen números de alumno, y Registrado muestra al admin que validó, no a Sandra que cobró. [Captura](09-cashflow-164000-contraste.png).

No comprobado: contenido/impresión de los cuatro recibos; los botones abrieron las pestañas 1–4, pero el visor automático no expuso el texto del PDF. Tampoco se prueba el orden interno de inscripción/cuota pagando ambas completas; la pantalla sí lo anuncia. No se sustituyó eso por mirar código o base.

Para mañana: 100 alumnos conservados, cuatro pagos y asistencia guardados; caja 1 validada, $10.000 de cambio retenido. No se deshizo la carga ni se reparó nada. Disponible de Inicio: $1.570.000 antes de cobrar y $1.734.000 tanto [antes](06-inicio-antes-validar.png) como [después](07-inicio-despues-validar.png) de validar: no se duplicó. T14 a verificar por Claude; no cerrada.

Tiempo total aproximado: 29 minutos (14:50–15:18, hora argentina), incluyendo preparación, recorrido y cierre documental/publicación. Preparación y recorrido: 22 minutos; entrega: unos 7 minutos. Lo más caro fueron los cuatro cobros, buscar/reabrir fichas y pedir recibos: unos 9 minutos. La falla de herramientas consumió parte de los primeros 5 minutos. No dispongo del costo monetario de esta sesión.