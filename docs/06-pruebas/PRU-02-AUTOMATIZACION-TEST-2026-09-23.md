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

## Destinos y ensayo real — Telegram confirmado, correo fallido

Carlos autorizó explícitamente bot y correo el 23/09. El token quedó solo en el
`.env` privado de wingstest; destinatarios en Configuración, no en variables del entorno.
El monitoreo de test se regeneró sin heartbeats. Producción no se modificó.

`getUpdates` seguía vacío. La captura de Carlos acreditó conversaciones anteriores
con el bot; se consultó `getChat` sobre el destino del monitoreo existente:
Telegram devolvió el chat privado **8543830872**, Carlos Bonifacio, **@Cafrecio**.
Se informó el id correcto antes de cargarlo. El número 1157060104 no fue utilizado.

Se ejecutó `avisos:resumen-diario` con una revisión temporal dentro de transacción.
Al finalizar se revirtió; consulta posterior sin pendientes. No se modificaron cuotas,
importes ni alumnos del padrón. Se observaron los eventos de transporte reales, sin fakes.

Telegram devolvió HTTP 200, `ok=true`, mensaje 6, chat 8543830872.
**Carlos confirmó que lo recibió.** Texto recibido:

> Wings: resumen diario de pendientes
>
> Revisiones de cobranza pendientes: 1 — más vieja: Morales, Sofía (23/09/2026) — https://test.gestionar-te.com.ar/revision-cobranza
>
> Revisá cada sección ingresando al enlace correspondiente.

**Correo NO entregado.** Destino configurado: `carlos.a.bonifacio@gmail.com`.
Transporte habilitado: sendmail local, remitente `avisos@test.gestionar-te.com.ar`.
No se produjo evento MessageSent. Postfix registró `virtual_alias_maps map lookup
problem` y `message not accepted, try again later` para el destinatario; sus consultas
MySQL de alias/vacaciones fallan. No existe un texto de correo recibido que mostrar.
El comando imprimió éxito porque el servicio captura errores: esa salida no prueba entrega.

Pendiente técnico: reparar el transporte compartido del servidor en una tarea con ese
alcance, o configurar un SMTP externo operativo para test. No se modificó Postfix,
ni se reemplazaron credenciales globales. No hace falta que Carlos vuelva a autorizar
el bot ni que repita su chat id; queda pendiente solo la entrega efectiva de correo.

## Verificación local

Sintaxis `bash -n` correcta en los tres scripts. Suite en base descartable exclusiva:
**315 pruebas / 1804 aserciones, todas verdes, 93,45 s**, en
`wings_testing_scheduler`. Sin cambios de vistas, CSS ni reglas de negocio.
