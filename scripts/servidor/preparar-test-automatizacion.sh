#!/bin/bash
# Instalacion repetible, exclusivamente para el sitio de prueba. Ejecutar como root.
set -Eeuo pipefail
APP=/home/wingstest/app
[ "$(id -u)" = 0 ] || { echo 'Ejecutar como root'; exit 1; }
[ -f "${APP}/artisan" ] || { echo 'Falta wingstest'; exit 1; }
cd "${APP}"
# Configuracion independiente para la tarea semanal CSP; sin URLs de Better Stack.
# Los destinos siguen viviendo en Configuracion de Wings, no en el .env.
tmp=$(mktemp "${APP}/storage/app/monitor-test.XXXXXX")
cron_tmp=$(mktemp)
trap 'rm -f "$tmp" "$cron_tmp"' EXIT
sudo -u wingstest /usr/bin/php82 <<'PHP' > "${tmp}"
<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== 'wingstest') {
    fwrite(STDERR, "Base distinta de wingstest: se rechaza la instalacion\n");
    exit(1);
}
foreach ([
    'TELEGRAM_BOT_TOKEN' => config('services.telegram.bot_token'),
    'TELEGRAM_CHAT_ID' => App\Models\Configuracion::get('avisos_telegram_chat_id', ''),
] as $clave => $valor) {
    echo $clave.'='.escapeshellarg((string) $valor).PHP_EOL;
}
PHP
chown wingstest:wingstest "${tmp}"
chmod 600 "${tmp}"
mv "${tmp}" "${APP}/storage/app/monitor-test.env"
# SHELL explicito: nologin y password bloqueada no son una shell para cron.
{ crontab -u wingstest -l 2>/dev/null || true; } |
    grep -vE '(^SHELL=|/home/wingstest/app([/ ]).*(schedule:run|ejecutar-scheduler))' > "${cron_tmp}" || true
printf '\nSHELL=/bin/bash\n* * * * * /bin/bash /home/wingstest/app/scripts/servidor/ejecutar-scheduler-test.sh >> /home/wingstest/app/storage/logs/scheduler.log 2>&1\n' >> "${cron_tmp}"
crontab -u wingstest "${cron_tmp}"
echo 'Cron de wingstest instalado; monitoreo independiente sin heartbeats.'
