#!/bin/bash
# Monta test.gestionar-te.com.ar replicando como esta armado wings.
#
# Se apoya en lo relevado en VPS/ESTADO-SERVIDOR.md y en el wings.conf real:
#   - En este servidor manda CWP. Los vhosts usan la IP EXPLICITA, no comodin:
#     con `*:80` Apache los trata como grupos separados y nunca se evalua.
#   - El vhost va en /usr/local/apache/conf.d/ (un nivel ARRIBA de conf.d/vhosts/),
#     que CWP no reescribe al reconstruir su configuracion.
#   - El Apache de CWP corre como `nobody`: el socket del pool debe ser de nobody
#     o da permiso denegado.
#   - public/build no viaja en el repositorio: hay que compilar en el servidor.
#
# No toca absolutamente nada de wings. Solo agrega recursos nuevos.
set -Eeuo pipefail

IP=2.25.204.38
DOMINIO=test.gestionar-te.com.ar
USUARIO=wingstest
APP=/home/${USUARIO}/app
SOCKET=/var/opt/remi/php82/run/php-fpm/${USUARIO}.sock
REPO=https://github.com/Chalie/gestion-wings.git

paso() { printf '\n=== %s ===\n' "$1"; }

paso "1. usuario del sistema"
if id "${USUARIO}" >/dev/null 2>&1; then
    echo "ya existe"
else
    useradd -m -s /sbin/nologin "${USUARIO}"
    passwd -l "${USUARIO}" >/dev/null
    echo "creado"
fi
chmod 711 "/home/${USUARIO}"

paso "2. base de datos"
if [ -f /root/.wingstest-db ]; then
    CLAVE=$(cat /root/.wingstest-db)
    echo "reutilizando la clave ya guardada"
else
    CLAVE=$(openssl rand -base64 24 | tr -dc 'A-Za-z0-9' | head -c 24)
    printf '%s' "${CLAVE}" > /root/.wingstest-db
    chmod 600 /root/.wingstest-db
    echo "clave nueva en /root/.wingstest-db"
fi
mysql -e "CREATE DATABASE IF NOT EXISTS wingstest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS 'wingstest'@'localhost' IDENTIFIED BY '${CLAVE}';"
mysql -e "ALTER USER 'wingstest'@'localhost' IDENTIFIED BY '${CLAVE}';"
mysql -e "GRANT ALL PRIVILEGES ON wingstest.* TO 'wingstest'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"
echo "base wingstest lista"

paso "3. pool de PHP propio"
cat > /etc/opt/remi/php82/php-fpm.d/${USUARIO}.conf <<POOL
[${USUARIO}]
user = ${USUARIO}
group = ${USUARIO}
listen = ${SOCKET}
; El Apache de CWP corre como nobody: con dueño apache el socket da permiso denegado.
listen.owner = nobody
listen.group = nobody
listen.mode = 0660
pm = ondemand
pm.max_children = 10
pm.process_idle_timeout = 30s
pm.max_requests = 500
php_admin_value[upload_max_filesize] = 20M
php_admin_value[post_max_size] = 20M
php_admin_value[memory_limit] = 256M
php_admin_value[expose_php] = Off
POOL
# El binario de Remi no se llama php-fpm82 ni esta en el PATH: vive con nombre
# generico dentro de su propio arbol.
FPM=/opt/remi/php82/root/usr/sbin/php-fpm
[ -x "${FPM}" ] || { echo "FALLA: no se encuentra el php-fpm de PHP 8.2 en ${FPM}"; exit 1; }
"${FPM}" -t || { echo "FALLA: configuracion de PHP-FPM invalida"; exit 1; }
systemctl reload php82-php-fpm
echo "pool listo"

paso "4. codigo"
if [ -d "${APP}/.git" ]; then
    sudo -u "${USUARIO}" git -C "${APP}" fetch --all --quiet
    sudo -u "${USUARIO}" git -C "${APP}" reset --hard origin/main --quiet
    echo "actualizado a $(sudo -u ${USUARIO} git -C ${APP} rev-parse --short HEAD)"
else
    sudo -u "${USUARIO}" git clone --quiet --branch main "${REPO}" "${APP}"
    echo "clonado en $(sudo -u ${USUARIO} git -C ${APP} rev-parse --short HEAD)"
fi

paso "5. configuracion"
if [ ! -f "${APP}/.env" ]; then
    sudo -u "${USUARIO}" cp "${APP}/.env.example" "${APP}/.env"
fi
fijar() {
    local clave="$1" valor="$2"
    if grep -q "^${clave}=" "${APP}/.env"; then
        sed -i "s|^${clave}=.*|${clave}=${valor}|" "${APP}/.env"
    else
        printf '%s=%s\n' "${clave}" "${valor}" >> "${APP}/.env"
    fi
}
fijar APP_ENV production
fijar APP_DEBUG false
fijar APP_URL "https://${DOMINIO}"
fijar DB_CONNECTION mysql
fijar DB_HOST 127.0.0.1
fijar DB_PORT 3306
fijar DB_DATABASE wingstest
fijar DB_USERNAME wingstest
fijar DB_PASSWORD "${CLAVE}"
fijar SESSION_DRIVER database
fijar CACHE_STORE database
fijar QUEUE_CONNECTION database
fijar LOG_LEVEL warning
chown "${USUARIO}:${USUARIO}" "${APP}/.env"
chmod 640 "${APP}/.env"
echo ".env escrito"

paso "6. dependencias"
cd "${APP}"
COMPOSER=$(command -v composer || echo /usr/local/bin/composer)
[ -x "${COMPOSER}" ] || { echo "FALLA: no se encuentra composer"; exit 1; }
sudo -u "${USUARIO}" "${COMPOSER}" install --no-dev --optimize-autoloader --no-interaction --quiet
echo "composer ok"
sudo -u "${USUARIO}" npm ci --silent --no-audit --no-fund
sudo -u "${USUARIO}" npm run build --silent
echo "assets compilados"

paso "7. clave de aplicacion y permisos"
if ! grep -qE '^APP_KEY=base64:.+' "${APP}/.env"; then
    sudo -u "${USUARIO}" php82 artisan key:generate --force --quiet
fi
chown -R "${USUARIO}:${USUARIO}" "${APP}/storage" "${APP}/bootstrap/cache"
chmod -R ug+rwX "${APP}/storage" "${APP}/bootstrap/cache"
echo "permisos ok"

paso "8. migraciones"
sudo -u "${USUARIO}" php82 artisan migrate --force
echo "migraciones aplicadas"

paso "9. sitio en Apache (solo HTTP por ahora)"
cat > /usr/local/apache/conf.d/${DOMINIO}.conf <<VHOST
# ${DOMINIO} — entorno de prueba. Creado el 13/09/2026.
# Vive fuera de conf.d/vhosts/ a proposito: esa carpeta la reconstruye CWP.
# La IP va explicita, no comodin: CWP agrupa los vhosts por IP y un *:80 nunca
# se evalua (ya paso al montar wings).
<VirtualHost ${IP}:80>
    ServerName ${DOMINIO}
    DocumentRoot ${APP}/public

    <Directory ${APP}/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch \.php\$>
        SetHandler "proxy:unix:${SOCKET}|fcgi://localhost"
    </FilesMatch>

    <IfModule mod_setenvif.c>
        SetEnvIf X-Forwarded-Proto "^https\$" HTTPS=on
    </IfModule>

    CustomLog /usr/local/apache/domlogs/${DOMINIO}.log combined
    ErrorLog  /usr/local/apache/domlogs/${DOMINIO}.error.log
</VirtualHost>
VHOST

# Nunca recargar sin validar: una configuracion rota se lleva puesto wings.
HTTPD=/usr/local/apache/bin/httpd
[ -x "${HTTPD}" ] || HTTPD=$(command -v httpd)
if ! "${HTTPD}" -t 2>&1 | grep -q "Syntax OK"; then
    "${HTTPD}" -t 2>&1 | tail -5
    echo "FALLA: la configuracion de Apache no valida. Se retira el sitio nuevo."
    rm -f /usr/local/apache/conf.d/${DOMINIO}.conf
    exit 1
fi
systemctl reload httpd
echo "sitio publicado por HTTP"

paso "10. comprobacion"
sleep 2
CODIGO=$(curl -s -o /dev/null -w '%{http_code}' -H "Host: ${DOMINIO}" "http://${IP}/login" --max-time 15 || echo 000)
echo "http://${DOMINIO}/login responde: ${CODIGO}"
echo
echo "Commit desplegado: $(sudo -u ${USUARIO} git -C ${APP} rev-parse --short HEAD)"
echo "Falta: certificado HTTPS y cron del scheduler."
