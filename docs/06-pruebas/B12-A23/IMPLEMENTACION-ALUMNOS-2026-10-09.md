# Alumnos — aplicación aprobada, 09/10/2026

Carlos aprobó el aspecto de la propuesta: **«Esta OK»**.
Integración local en revisión; no cierre conjunto B12/A23 ni despliegue.

## Cambio

- Ruta habitual ADMIN `/reportes/alumnos`; protección de primera carga existente.
- Ingresos/egresos enlaza Alumnos conservando mes y deporte. Volver conserva ambos.
- Formulario real: mes actual o anterior, todos los deportes o uno concreto.
- Matrícula **actual** separada de asistencia mensual, también al elegir septiembre.
- Se conserva la consulta ya probada: cada presencia, canceladas/futuras excluidas,
  huecos de registro sin convertirlos en cero y detalle accesible bajo los gráficos.
- Mes anterior sin clases sigue seleccionado. No baja alumnos ni genera mensajes.
- Sin cambios en app.css, layout compartido, cobros o liquidaciones.

## Comprobaciones

Módulo: **10 aprobadas, 76 aserciones, 105,69s** en `wings_testing_codex`.
Cuatro regresiones nuevas: navegación/contexto, filtros inválidos, roles/P1 y mes
sin clases. Suite completa: **547 aprobadas, 2 omitidas, 4447 aserciones, 521,38s**
en `wings_testing_codex`; salida `storage/app/alumnos-integracion-suite.txt`.
Sintaxis PHP/JS y diff correctos; revisión independiente de fuente sin defectos.
Créditos restablecidos: vistas compiladas, recursos construidos y fuente verificada.
Capturas de rutas habituales autenticadas: Alumnos mes actual, septiembre/deporte;
Finanzas con navegación a ambos reportes. Archivos `alumnos-aplicado` en [visor](visor.html).
Última suite y capturas: [entrega conjunta](IMPLEMENTACION-SUELDOS-2026-10-09.md).
El [prototipo aprobado](PROPUESTA-ALUMNOS-2026-10-09.md) se conserva como antecedente.

## Sueldos — acuerdos y próximo paso

Carlos eligió repartir cuotas proporcionalmente a asistencias, guardar historial
desde ahora y usar **cuotas cobradas** para la comisión. Carlos aclaró que el ADMIN
edita el **monto final de la liquidación** y pidió alumnos pagados/asistencia sin pago.
Textos: **Alumnos con pagos registrados** / **Asistencias sin pago de cuota**.
Pagos parciales cuentan como cobros; estos textos no afirman cancelación completa.
Carlos permite el ajuste también en **cerradas sin pagar**; el circuito todavía
está implementado y probado en base descartable. No cambiar pagos ya realizados por inferencia.
[Historia analítica probada](HISTORIAL-SUELDOS-2026-10-09.md), nueve pruebas aprobadas;
sin activar en el club. Sueldos y ajuste implementados, aspecto por revisar.

[Acuerdos literales](../../07-evaluacion/ACUERDOS-B12-A23-2026-10-09.md).
