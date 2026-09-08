# Monitoreo operativo de Wings

## Canales

- Better Stack controla externamente la web y envia email.
- El servidor informa a Better Stack el scheduler y el backup.
- Ante una falla explicita del scheduler o del backup, el servidor tambien envia Telegram.

Si el servidor completo deja de responder, el email externo sigue funcionando. Telegram
no puede salir desde un servidor apagado.

## Configuracion del servidor

1. Crear `/etc/wings-monitor/alertas.env` tomando
   `scripts/servidor/monitoreo.env.example` como modelo.
2. El archivo debe pertenecer a `root:wings`, con permisos `640`; el directorio debe
   pertenecer a `root:wings`, con permisos `750`.
3. No copiar tokens, chat IDs ni URLs de heartbeat al repositorio, logs o commits.

Cron del usuario `wings`:

```cron
* * * * * /home/wings/app/scripts/servidor/ejecutar-scheduler.sh >> /home/wings/app/storage/logs/scheduler.log 2>&1
```

El cron nocturno de root conserva `respaldar.sh`; el script informa resultado local y
copia a Drive por separado.

## Prueba controlada obligatoria

Antes de cerrar FDS-02:

1. Ejecutar el scheduler real y comprobar que el heartbeat queda activo.
2. Provocar un fallo controlado del scheduler y comprobar Telegram y Better Stack.
3. Ejecutar un backup real y comprobar el heartbeat.
4. Probar la copia externa con un remoto temporal invalido: debe conservar el archivo
   local y alertar claramente que fallo Drive.
5. Restaurar la configuracion real y ejecutar otra corrida correcta.

Registrar solo fecha, resultado y commit. Nunca registrar secretos.
