# PRU-02 — Automatización del sitio de prueba, 23/09/2026

## Alcance y estado comprobado

Solo `wingstest`, aplicación `/home/wingstest/app`, PHP `/usr/bin/php82`, base
`wingstest`. No se modificó `/home/wings` ni el monitoreo de producción.

El relevamiento encontró un cron directo `artisan schedule:run` ya instalado y
registros recientes; no estaba ausente. Se reemplazó por un ejecutor exclusivo de
prueba con trazabilidad de inicio/fin y configuración de monitoreo independiente.

`ejecutar-scheduler-test.sh` fija las rutas de prueba y rechaza otro usuario.
No carga `/etc/wings-monitor/alertas.env`, elimina heartbeats heredados y no hace
llamadas a Better Stack. La tarea semanal CSP usa archivo de destinos propio y
archivo de firmas dentro del almacenamiento de test, sin acceder al de producción.

`preparar-test-automatizacion.sh` instala el cron de forma repetible y preserva
otras tareas del usuario. Dos ejecuciones seguidas dejaron una sola entrada de
scheduler. `montar-test.sh` lo invoca al terminar; no hace falta recordar el cron
en cada despliegue. Configuración de destinatarios no se reemplaza al instalarlo.

## Evidencia real de cron

Se esperó el minuto siguiente, sin invocar manualmente al ejecutor. Log del servidor:

```text
2026-09-23T03:28:01+00:00 scheduler-test inicio usuario=wingstest
INFO No scheduled commands are ready to run.
2026-09-23T03:28:02+00:00 scheduler-test fin estado=0
```

Usuario comprobado con shell `/sbin/nologin` y contraseña `LK` (bloqueada).
El crontab fija `SHELL=/bin/bash`; no se habilitó login ni contraseña.
`php82 artisan schedule:list` muestra:

- `cobranza:generar-deudas`: día 1, 06:00.
- `avisos:resumen-diario`: todos los días, 08:00.
- `resumen-csp.sh`: lunes, 07:00 (tercera tarea preexistente).

No se adelantó el reloj ni se generaron cuotas fuera de fecha.

## Destinos y entrega — pendiente de completar

El email solicitado se guardó en `configuraciones.avisos_email` de `wingstest`.
Telegram: `getMe` identificó al bot GestionarteAlertasBot; `getUpdates` no devolvió
conversaciones. No se cargó el número propuesto como chat id sin verificación.
Se pidió a Carlos iniciar el bot para obtener el id real.

El correo efectivo está en modo `log`; no se considera entregado. Hay Postfix activo,
pero aún no se probó entrega a Gmail. La aplicación no tiene token de Telegram.
La revisión automática bloqueó la copia persistente del token y cambio de transporte;
se pidió autorización explícita. No se reintentó esa operación ni se dio por enviada.

No se disparó aún el resumen. Al revisar no había cajas cerradas, revisiones pendientes
ni liquidaciones abiertas; el ensayo requerirá un pendiente temporal y controlado,
sin dejar cambios en la deuda o los cobros del padrón.

## Verificación local

Sintaxis `bash -n` correcta en los tres scripts. Suite en base descartable exclusiva:
**315 pruebas / 1804 aserciones, todas verdes, 93,45 s**, en
`wings_testing_scheduler`. Sin cambios de vistas, CSS ni reglas de negocio.
