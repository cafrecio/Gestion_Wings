# Wings — Estado actual

> **Actualizado:** 08/09/2026
> **Plan vigente:** `docs/07-evaluacion/PLAN-TRABAJO-IA-v2026-09-08.md`,
> version 2026-09-08.v3.
> Si otro documento contradice este estado, no improvisar: verificar y corregir.

## 1. Estado general

Wings esta publicado en `https://wings.gestionar-te.com.ar`, pero el gate final de
produccion no esta firmado. La base del servidor quedo preparada para que el club
cargue sus datos por pantalla y Vanina ya tiene una cuenta ADMIN. Todavia no estan
cargados los alumnos ni la operacion real del club.

## 2. Servidor — verificado por SSH el 08/09

| Que | Estado |
|---|---|
| Commit desplegado | `9fdd03d` |
| Diferencia con `main` al corte | 5 commits posteriores, solo documentacion y evaluaciones |
| Plataforma | AlmaLinux 9, PHP 8.2.33, Laravel 12.68.0 |
| HTTPS | Activo |
| Cloudflare | Proxy activo; acceso web directo al servidor cerrado |
| Migraciones | Sin pendientes en la ultima verificacion |
| Scheduler | Registrado y ejecutado cada minuto; deuda mensual programada para dia 1 a las 06:00 |
| Backups | Diarios, cifrados, rotados y copiados a Drive |
| Monitoreo | HTTPS externo activo con email; heartbeats creados. Scripts de scheduler, backup y Telegram preparados localmente, aun no desplegados |

No se pudo demostrar que la corrida mensual del 01/09 haya producido resultado: no
quedo un log que lo pruebe o descarte.

## 3. Base de entrega del servidor

Preparada manualmente el 07/09, con respaldo previo:

- Sin alumnos, deudas, pagos, clases ni operacion real.
- Se conservan `Cuotas` con `Cuota Mensual` y `Sueldos` vacio porque el codigo los
  busca por nombre.
- Se conservan las configuraciones existentes y las tres reglas de primer pago.
- Deportes, niveles, grupos, planes, tipos de caja y demas catalogos los carga el
  usuario por pantalla.
- Existe una cuenta superadmin protegida y una cuenta ADMIN de Vanina.

Este estado todavia no tiene un procedimiento reproducible versionado. Es FDS-03. No
crear un seeder de datos reales sin decision de Carlos.

## 4. Estado confirmado del repositorio

| Area | Estado |
|---|---|
| Stack | Laravel 12, PHP 8.2, MariaDB, Blade y Vite |
| **Tests** | **129 pruebas**, 705 aserciones, verde sobre MariaDB el 08/09 |
| Roles | ADMIN, OPERATIVO y PROFESOR; superadmin protegido |
| Cobranza | ADMIN y OPERATIVO entran; PROFESOR rechazado |
| Alumnos | CRUD, plan vigente, fecha de alta y grupo validado contra deporte |
| Cobros | Circuito principal implementado; tres caminos prioritarios requieren reproduccion/correccion en COB |
| Caja | Apertura, movimientos, cierre, rechazo, validacion y cancelacion |
| Cashflow | Integra cajas validadas y saldo inicial; significado de “Balance” pendiente de decision |
| Clases | Asistencias atomicas; editar clase no repite control de superposicion |
| Liquidaciones | Generacion, cierre, pago y recibos; concurrencia e historia pendientes antes del 25/09 |
| Dump | Fuera de Git e ignorado; `DemoSeeder` ya no lo exporta |
| PHP | `composer audit` sin avisos el 08/09 |
| JavaScript | `npm audit` informa 11 paquetes; falta clasificar uso y alcanzabilidad |
| CSP | Report-only; quedan 26 bloques script en 24 vistas y 24 manejadores inline |
| Diseño | Protegido por `AGENTS.md` y hook de commit |

## 5. Lo cerrado del 5 al 7 de septiembre

- Suite migrada de SQLite a MariaDB.
- Dump retirado y segunda puerta de exportacion eliminada.
- Falla de copia nocturna a Drive corregida y respaldos revalidados.
- Cloudflare configurado con confianza acotada.
- Precio de planes y frecuencias obligatorias protegidos.
- Descuento de primera cuota limitado al mes de alta.
- Importador de deuda inicial validado y carga de prueba preparada.
- Rubros `Cuotas` y `Sueldos` protegidos en la aplicacion.
- Acceso y menu de Cobranza corregidos para OPERATIVO.
- Menu reorganizado y desplegado en `9fdd03d`.
- Base del servidor preparada y cuenta ADMIN de Vanina creada.

## 6. Orden de trabajo

FDS-01 queda cerrado. FDS-02 esta en curso y no se cierra hasta probar las alertas
desde el servidor. El orden restante es:

1. **FDS-02 a FDS-04:** revalidar servidor, reproducibilidad y pantallas corregidas.
2. **COB-01 a COB-05:** monto con miles, cambio de plan, parcial con descuento y
   cancelacion/reintento.
3. **FIN:** recibos, historia, balance y concurrencia financiera.
4. **SEG:** npm, sesiones, despliegue, recuperacion, alertas, CI y CSP.
5. **PRU:** recorrido humano completo, proceso mensual y gate.
6. **ENT:** pedidos concretos de Carlos.
7. **POS:** reportes y evolucion posterior; no bloquean por aparecer en una evaluacion.

Los criterios y dependencias estan en el plan vigente. La version HTML marcable es
`docs/07-evaluacion/PLAN-TRABAJO-CARLOS-v2026-09-08.html`.

## 7. Decisiones pendientes de Carlos

- Si un pago anulado cuenta como primer pago comercial, despues de reproducir COB-04.
- Que significa “Balance” filtrado en Cashflow.
- Como resolver revisiones con parcial, observaciones e importe historico.
- Limites temporales de movimientos manuales.
- Significado de DEUDOR sin pagos y sin saldo pendiente.
- Tratamiento contable de la inscripcion configurable.
- Logo, paleta y favicon del club.

## 8. Riesgos y limites conocidos

- El rollback del deploy no revierte migraciones.
- Preflight corre despues de reabrir el sitio.
- La restauracion probada importa SQL; no reconstruye sola archivos y configuracion.
- El control historico del restore compara conteos, no contenido financiero completo.
- El servidor todavia no tiene desplegada la alerta de copia externa; hasta hacerlo,
  un fallo de Drive conserva la copia local pero solo queda en la salida del cron.
- `MoneyLockingTest` verifica texto del codigo, no concurrencia real.
- Cambiar contraseña no revoca por si solo sesiones y `remember_token`.
- Los errores de recibos pueden devolver el mensaje tecnico de la excepcion.
- Aritmetica monetaria usa `float` en parte del dominio; el daño no esta demostrado.
- `View::composer('*')` ejecuta una consulta global para el badge de clases.

## 9. Contradicciones abiertas

| Tema | Estado real | Proxima accion |
|---|---|---|
| `dia_generacion_deuda` editable | El scheduler usa dia 1 fijo | Definir si gobierna la tarea o se retira de pantalla |
| Balance filtrado de Cashflow | Mezcla saldo inicial historico con movimientos del periodo | Carlos define saldo acumulado o resultado del periodo |
| Estado minimo de entrega | Preparado con un script temporal | FDS-03 deja un procedimiento reproducible |
| Tope de 1200px en guia de diseño | `app.css` no lo implementa | Decidir guia o implementacion; no tocar sin autorizacion |

## 10. Fuentes vigentes

| Necesidad | Ruta |
|---|---|
| Plan para IA | `docs/07-evaluacion/PLAN-TRABAJO-IA-v2026-09-08.md` |
| Plan marcable para Carlos | `docs/07-evaluacion/PLAN-TRABAJO-CARLOS-v2026-09-08.html` |
| Evaluacion Codex | `docs/07-evaluacion/Evaluacion Codex 8-9-26.md` |
| Evaluacion Claude | `docs/07-evaluacion/Evaluacion Claude 8-9-26.md` |
| Bitacora Codex | `docs/00-estado/LOG-CODEX.md` |
| Bitacora Claude | `docs/00-estado/LOG-CLAUDE.md` |
| Acciones de Carlos | `docs/00-estado/CHECKLIST-CARLOS.md` |
| Contratos | `docs/02-contratos/` |
| Pruebas funcionales | `docs/06-pruebas/` |
