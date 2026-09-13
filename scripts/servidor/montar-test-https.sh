#!/bin/bash
# Segunda parte del montaje de test.gestionar-te.com.ar: certificado, HTTPS,
# tarea programada y datos de prueba.
#
# Se corre despues de montar-test.sh, con el sitio ya respondiendo por HTTP:
# la validacion del certificado necesita justamente eso.
set -Eeuo pipefail

IP=2.25.204.38
DOMINIO=test.gestionar-te.com.ar
USUARIO=wingstest
APP=/home/${USUARIO}/app
SOCKET=/var/opt/remi/php82/run/php-fpm/${USUARIO}.sock
CERT_DIR=/etc/pki/tls/${USUARIO}

paso() { printf '\n=== %s ===\n' "$1"; }

paso "1. certificado"
if [ -s "${CERT_DIR}/test.crt" ]; then
    echo "ya existe"
else
    mkdir -p "${CERT_DIR}" /usr/local/apache/autossl_tmp
    # CWP desvia TODAS las validaciones a autossl_tmp con una regla global, sin
    # importar el sitio: poner el archivo en la carpeta del dominio no sirve.
    /root/.acme.sh/acme.sh --issue \
        -d "${DOMINIO}" \
        -w /usr/local/apache/autossl_tmp \
        --server letsencrypt \
        --keylength 2048 || { echo "FALLA: no se pudo emitir el certificado"; exit 1; }

    /root/.acme.sh/acme.sh --install-cert -d "${DOMINIO}" \
        --cert-file "${CERT_DIR}/test.crt" \
        --key-file  "${CERT_DIR}/test.key" \
        --fullchain-file "${CERT_DIR}/fullchain.crt" \
        --reloadcmd "systemctl reload httpd"
    chmod 600 "${CERT_DIR}"/*.key
    echo "emitido"
fi

paso "2. sitio por HTTPS"
# Archivo propio, NO el del vhost HTTP: ese lo reescribe montar-test.sh en cada
# actualizacion de test, y el bloque seguro se perderia en la primera corrida.
SSL_CONF="/usr/local/apache/conf.d/${DOMINIO}-ssl.conf"
if [ -s "${SSL_CONF}" ]; then
    echo "ya estaba"
else
    cat > "${SSL_CONF}" <<VHOST

<VirtualHost ${IP}:443>
    ServerName ${DOMINIO}
    DocumentRoot ${APP}/public

    SSLEngine on
    SSLCertificateFile    ${CERT_DIR}/fullchain.crt
    SSLCertificateKeyFile ${CERT_DIR}/test.key
    SSLProtocol -all +TLSv1.2 +TLSv1.3

    <Directory ${APP}/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch \.php\$>
        SetHandler "proxy:unix:${SOCKET}|fcgi://localhost"
    </FilesMatch>

    CustomLog /usr/local/apache/domlogs/${DOMINIO}.ssl.log combined
    ErrorLog  /usr/local/apache/domlogs/${DOMINIO}.ssl.error.log
</VirtualHost>
VHOST

    HTTPD=/usr/local/apache/bin/httpd
    [ -x "${HTTPD}" ] || HTTPD=$(command -v httpd)
    if ! "${HTTPD}" -t 2>&1 | grep -q "Syntax OK"; then
        echo "FALLA: Apache no valida. Se deja el sitio como estaba."
        "${HTTPD}" -t 2>&1 | tail -5
        exit 1
    fi
    systemctl reload httpd
    echo "publicado"
fi

paso "3. tarea programada"
CRON="* * * * * cd ${APP} && /usr/bin/php82 artisan schedule:run >> ${APP}/storage/logs/scheduler.log 2>&1"
if crontab -u "${USUARIO}" -l 2>/dev/null | grep -q "schedule:run"; then
    echo "ya estaba"
else
    (crontab -u "${USUARIO}" -l 2>/dev/null || true; echo "${CRON}") | crontab -u "${USUARIO}" -
    echo "instalada"
fi

paso "4. datos de prueba"
cd "${APP}"
YA=$(sudo -u "${USUARIO}" /usr/bin/php82 artisan tinker --execute="echo App\Models\Alumno::count();" 2>/dev/null | tr -dc '0-9')
if [ "${YA:-0}" -gt 0 ]; then
    echo "la base ya tiene ${YA} alumnos, no se toca"
else
    # APP_ENV se pisa solo para esta corrida. En el .env queda `production`
    # a proposito: test tiene que parecerse a produccion, y el seeder se niega
    # a correr ahi justamente para que nadie lo dispare contra el club.
    sudo -u "${USUARIO}" env APP_ENV=local /usr/bin/php82 artisan db:seed \
        --class=PrimeraCargaCompletaSeeder --force
fi

paso "5. comprobacion"
sudo -u "${USUARIO}" /usr/bin/php82 artisan config:cache --quiet
sudo -u "${USUARIO}" /usr/bin/php82 artisan route:cache --quiet
sudo -u "${USUARIO}" /usr/bin/php82 artisan view:cache --quiet
systemctl reload php82-php-fpm
sleep 2
echo "alumnos: $(sudo -u ${USUARIO} /usr/bin/php82 artisan tinker --execute='echo App\Models\Alumno::count();' 2>/dev/null | tr -dc '0-9')"
echo "https local: $(curl -sk -o /dev/null -w '%{http_code}' -H "Host: ${DOMINIO}" "https://${IP}/login" --max-time 15 || echo 000)"
