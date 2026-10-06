# Wings — Plan de produccion

> **Actualizado:** 08/09/2026
> **Estado:** documento de contexto.
> **Orden de trabajo vigente:**
> `docs/07-evaluacion/PLAN-TRABAJO-IA-v2026-09-08.md`, version 2026-09-08.v4.

Este archivo conserva el estado de produccion y el gate. El orden detallado ya no se
toma de los bloques D1-D6 del plan de agosto.

## 1. Estado confirmado

| Area | Estado al 08/09/2026 |
|---|---|
| Aplicacion | Publicada para preparacion; gate final no firmado |
| Servidor | `81f27ef`, verificado por consola el 09/09; scripts de monitoreo instalados y probados |
| Base del servidor | Estado minimo para carga humana; Vanina tiene cuenta ADMIN |
| Suite | **444 pruebas: 438 aprobadas y 1 omitida, 2968 aserciones** el 05/10 en wings_testing_codex, con A4/A5 y A37 de Gemini (3062f95). [Entrega y alcance](../06-pruebas/PRU-02/IMPLEMENTACION-A4-A5.md). Sin despliegue |
| Dump | Fuera de Git y sin reexportacion automatica desde el 05/09 |
| Cloudflare | Proxy activo y acceso web directo al servidor cerrado |
| Cobranza | ADMIN y OPERATIVO habilitados; PROFESOR rechazado. Entrega 1 `abc346a` aprobada por Codex 05/10. Control posterior registrado 06/10: A54 cerrado por Codex; A53 abierto, A55 abierto por inscripción con dos deportes. A13/B1 cobran/anulan sin caja, pero siguen abiertos por historial anulado y representación del contraasiento. [Informe independiente](../06-pruebas/PRU-02/VERIFICACION-A13-A54.md). Sin despliegue |
| Caja A25 | Carlos definió separar cambio/retiro, heredar con confirmación del último cierre del club (cajón compartido) y permitir cerrar con diferencia para revisión ADMIN. Hoy falta implementar inicio y arqueo. Primera apertura/correcciones, turnos simultáneos y cambio retenido variable/fijo pendientes. [Decisiones](../05-pendientes/A25-CAMBIO-INICIAL-CAJA.md). No Hecho, no cerrado; sin aplicación ni datos modificados |
| A2/B2 | Cuota en alta y estados por deuda implementados localmente el 23/09; suite 331/1900. Pendiente verificación independiente y despliegue con migración porcentaje_alta |
| Inscripción ENT-01 | Por DNI, cargos separados, prioridad de inscripción. P0 del 04/10 retira corte y conserva cargos/pagos; pendiente Gemini. Sin deploy |
| Primera carga P0/P1 | P0 implementado; A43 verificada por Gemini el 05/10. P1 aprobada e implementada: plantilla, revisión, carga y Deshacer, capturas escritorio/375. Pendiente Gemini y despliegue con migración primera_carga y build. No limpia producción/test; importadores antiguos se retiran en commit aparte después de aceptar P1 |
| PHP | `composer audit` sin avisos |
| JavaScript | SEG-01 probada 11/09: sin Axios, lock con cero avisos npm; build y suite aislados verdes. Sin deploy |
| Permisos A29/A30/A31 | Entregados: rechazo explícito y Volver al inicio propio, sin ampliar accesos. Suite 380/2242, tres roles normal/375 px. Pendiente Gemini; sin deploy |
| CSP | En modo reporte, ya recolectando avisos en report-uri /csp-reporte. Quedan 19 bloques script y 10 manejadores inline |
| Backups | Diarios, cifrados y con copia a Drive; restauracion SQL probada |
| Monitoreo | Cerrado FDS-02 el 09/09: HTTPS Up, scheduler/backup con fallo y recuperacion; Carlos confirmo email y Telegram |

## 2. Orden vigente

1. **FDS** — cierre documental y revalidacion del fin de semana.
2. **COB** — tres caminos de cobro que pueden confirmar datos incorrectos.
3. **FIN** — recibos, historia, concurrencia y significado del balance.
4. **SEG** — dependencias, sesiones, despliegue, restauracion, alertas y CSP.
5. **PRU** — prueba humana completa y proceso mensual.
6. **ENT** — pedidos concretos de Carlos.
7. **POS** — reportes y evolucion posterior.

El criterio y las dependencias de cada tarea estan en el plan vigente. No copiar de
este archivo una orden vieja ni ejecutar una tarea sin releer aquel documento.

## 3. Gate antes de declarar produccion

- [ ] COB-01 a COB-05 cerrados y verificados.
- [ ] FIN de prioridad alta cerrados. FIN-04 ya definido por Carlos el 22/09 en el contrato de Reportes; implementación de Reportes pendiente.
- [ ] SEG de prioridad alta cerrados.
- [ ] Suite completa verde sobre MariaDB.
- [ ] Recorrido humano de ADMIN, OPERATIVO y PROFESOR completado.
- [ ] Generacion mensual ensayada con alerta observable.
- [ ] `wings:preflight` en verde antes de abrir el release.
- [ ] Commit, migraciones, headers y archivos expuestos revalidados en servidor.
- [ ] Restauracion integral ensayada, no solo importacion SQL.
- [x] Monitoreo HTTPS activo; Carlos recibe alertas reales de scheduler y backup por email y Telegram (09/09). No se simulo caida HTTPS.
- [ ] Datos y usuarios reales cargados por el circuito acordado.
- [ ] Primera caja real acompañada.

## 4. Decisiones que siguen vigentes

- La carga real del club es humana. No se inventan datos en un seeder.
- El primer mes de deuda se carga junto con los datos iniciales.
- API REST apagada a proposito.
- Recibo no fiscal aceptado.
- Exportables fuera de la version inicial.
- Enmienda funcional 13/09, pendiente POS-06: ADMIN puede cancelar una cerrada no pagada para revisar asistencia. Pagadas intactas; ajustes posteriores. Ver contrato de particulares.
- CSP se endurece de forma gradual y supervisada.
- AUD-025 se atiende antes de crear rutas destructivas hoy inexistentes.

## 5. Limites de lo ya verificado

- La restauracion historica probo SQL y conteos; no reconstruccion integral de Wings.
- El rollback del deploy vuelve el codigo, no la base.
- La copia externa del backup puede fallar sin convertir el proceso completo en error.
- `MoneyLockingTest` comprueba estructura del codigo, no simultaneidad real.
- El estado minimo del servidor se preparo manualmente y todavia necesita un
  procedimiento reproducible (FDS-03).
