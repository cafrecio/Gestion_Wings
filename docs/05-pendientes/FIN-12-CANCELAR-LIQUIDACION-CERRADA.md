# FIN-12 — Cancelar liquidación cerrada no pagada

**PENDIENTE. Prioridad de Carlos: hoy, 13/09/2026.**
Este turno registra la tarea; no implementa ni despliega.
Se adelanta este alcance de POS-06 sin esperar al módulo de particulares.

## Decisión y alcance

Solo ADMIN puede cancelar una liquidación cerrada que todavía no fue pagada,
para revisar las asistencias y preparar nuevamente la liquidación correcta.
«No cobrada» se refiere aquí al sueldo aún no pagado al profesor, no a cuotas
pendientes de las alumnas. Una pagada nunca se cancela ni modifica; las diferencias
económicas van a la próxima liquidación. Implementar ese circuito compensatorio
no forma parte de FIN-12.

Fuente: [contrato de particulares, §8](../02-contratos/Wings-Contrato-Clases-Particulares-V1.md).
El bloqueo de asistencias mientras la liquidación sigue cerrada afecta a todos
los perfiles y a las clases ordinarias. No implementar particulares, créditos,
avisos ni Reportes como parte de esta tarea.

## Instrucciones para IA

1. Leer AGENTS, continuidad y las enmiendas de los contratos de Liquidaciones,
   Clases/Asistencias y Permisos. La decisión de Carlos sustituye la prohibición
   anterior para cerradas no pagadas, no para pagadas.
2. Revalidar los caminos web de gestión/pago de liquidaciones y corrección de
   asistencias; leer cuerpos, modelos y relaciones antes de elegir la modificación.
   Inspección documental del 13/09: `eliminarLiquidacion()` en
   `app/Services/LiquidacionService.php` rechaza cerradas y luego llama a `delete()`.
   **No basta con retirar esa condición:** se perdería la historia que debe conservarse.
   El grafo declaró metadatos cambiados; este cuerpo se cotejó con el archivo local.
3. Implementar cancelación administrativa trazable, conservando liquidación,
   detalle y vínculo con su reemplazo o revisión. Registrar responsable, fecha
   y motivo. No borrar ni modificar pagos, recibos o egresos para habilitarla.
   Definir la representación técnica compatible con el modelo actual; si exige
   una nueva decisión de negocio, detener solo el camino dependiente y consultar.
4. Verificar ADMIN en servidor; OPERATIVO, PROFESOR y anónimo no pueden cancelar,
   ni mediante petición directa. Proteger también la condición de no pagada.
5. Bloquear y releer la liquidación dentro de la transacción antes de cancelar;
   coordinar con el pago usando el mismo recurso. Si el pago ganó, rechazar la
   cancelación; si ganó la cancelación, impedir que se pague el documento cancelado.
   Reintentos no deben crear dos cancelaciones ni dos reemplazos.
6. Mientras siga cerrada, ningún perfil cambia asistencias vinculadas. Cancelarla
   no concede permisos nuevos al profesor ni reinicia su plazo de corrección.
   Comprobar también vínculos de comisión, sin asumir que todo detalle apunta a clase.
7. La revisión posterior debe permitir preparar y cerrar la liquidación correcta
   sin doble liquidación ni pérdida del documento cancelado. Mantener las demás
   validaciones de asistencia y superposición.
8. Si hace falta una acción/campo en Blade, presentar el cambio mínimo y obtener
   autorización concreta de diseño según AGENTS; este pedido de documentación
   no autoriza modificar vistas. No habilitar API ni cambiar cálculo de sueldos.

## Verificaciones para aceptar

- ADMIN cancela cerrada no pagada; historia y detalle conservados y revisión posible.
- Pagada rechazada para todos, sin cambiar importes, recibos ni cashflow.
- OPERATIVO/PROFESOR/anónimo rechazados sin escrituras por petición directa.
- Liquidación todavía cerrada bloquea asistencia también para ADMIN.
- Nueva liquidación correcta tras revisión, sin doble pago ni doble cómputo.
- Dos conexiones MariaDB reales: pago contra cancelación en ambos órdenes;
  evidencia de espera sin escritura del segundo proceso y estado final coherente.
- Fallo intermedio revierte toda la operación; reintento no duplica efectos.
- Suite completa sin otra corrida simultánea, sintaxis, compilación de vistas y
  evidencia específica según AGENTS, siempre en base descartable.

Al cerrar: actualizar ambos tableros, contratos/estado afectados y LOG-CODEX
(o log del agente ejecutor); separar implementado, verificado y desplegado.
No desplegar por figurar como tarea para hoy.
