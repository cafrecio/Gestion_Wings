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
| Suite | **154 pruebas**, 920 aserciones sobre MariaDB |
| Dump | Fuera de Git y sin reexportacion automatica desde el 05/09 |
| Cloudflare | Proxy activo y acceso web directo al servidor cerrado |
| Cobranza | ADMIN y OPERATIVO habilitados; PROFESOR rechazado |
| PHP | `composer audit` sin avisos |
| JavaScript | 11 avisos npm pendientes de clasificar por uso y alcanzabilidad |
| CSP | En modo reporte; no bloquea. Quedan 26 bloques script y 24 manejadores inline |
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
- [ ] FIN de prioridad alta cerrados; FIN-04 decidido por Carlos.
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
- Liquidaciones cerradas no se reabren; se corrigen con movimientos compensatorios.
- CSP se endurece de forma gradual y supervisada.
- AUD-025 se atiende antes de crear rutas destructivas hoy inexistentes.

## 5. Limites de lo ya verificado

- La restauracion historica probo SQL y conteos; no reconstruccion integral de Wings.
- El rollback del deploy vuelve el codigo, no la base.
- La copia externa del backup puede fallar sin convertir el proceso completo en error.
- `MoneyLockingTest` comprueba estructura del codigo, no simultaneidad real.
- El estado minimo del servidor se preparo manualmente y todavia necesita un
  procedimiento reproducible (FDS-03).
