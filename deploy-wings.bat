@echo off
chcp 65001 >nul 2>&1
setlocal EnableDelayedExpansion

echo ============================================================
echo   WINGS - Script de Deploy / Diagnostico
echo   Sistema de Gestion Wings - Laravel 12 + XAMPP
echo ============================================================
echo.

set "ERRORS=0"
set "WARNINGS=0"
set "PROJECT_DIR=%~dp0"
set "PROJECT_DIR=%PROJECT_DIR:~0,-1%"

echo Directorio del proyecto: %PROJECT_DIR%
echo.

:: ============================================================
:: FASE 1: VERIFICAR XAMPP
:: ============================================================
echo [FASE 1] Verificando XAMPP...
echo ------------------------------------------------------------

set "XAMPP_DIR="
if exist "C:\xampp\xampp-control.exe" set "XAMPP_DIR=C:\xampp"
if exist "D:\xampp\xampp-control.exe" set "XAMPP_DIR=D:\xampp"

if "!XAMPP_DIR!"=="" (
    echo [ERROR] XAMPP no encontrado en C:\xampp ni D:\xampp
    echo         Descargar desde: https://www.apachefriends.org/download.html
    echo         Instalar la version con PHP 8.2 o superior
    set /a ERRORS+=1
    goto :CHECK_RESULTS
) else (
    echo [OK] XAMPP encontrado en: !XAMPP_DIR!
)

:: ============================================================
:: FASE 2: VERIFICAR PHP
:: ============================================================
echo.
echo [FASE 2] Verificando PHP...
echo ------------------------------------------------------------

set "PHP_EXE=!XAMPP_DIR!\php\php.exe"
if not exist "!PHP_EXE!" (
    echo [ERROR] PHP no encontrado en !XAMPP_DIR!\php\php.exe
    set /a ERRORS+=1
) else (
    echo [OK] PHP encontrado: !PHP_EXE!

    :: Verificar version de PHP
    for /f "tokens=*" %%v in ('"!PHP_EXE!" -r "echo PHP_VERSION;"') do set "PHP_VERSION=%%v"
    echo      Version: !PHP_VERSION!

    for /f "tokens=1,2 delims=." %%a in ("!PHP_VERSION!") do (
        set "PHP_MAJOR=%%a"
        set "PHP_MINOR=%%b"
    )

    if !PHP_MAJOR! LSS 8 (
        echo [ERROR] Se requiere PHP 8.2+. Version actual: !PHP_VERSION!
        set /a ERRORS+=1
    ) else if !PHP_MAJOR! EQU 8 if !PHP_MINOR! LSS 2 (
        echo [ERROR] Se requiere PHP 8.2+. Version actual: !PHP_VERSION!
        set /a ERRORS+=1
    ) else (
        echo [OK] Version de PHP compatible
    )
)

:: Verificar extensiones PHP necesarias
echo.
echo      Verificando extensiones PHP...
set "EXTENSIONS=pdo_mysql mbstring openssl tokenizer xml ctype json bcmath gd"
for %%e in (%EXTENSIONS%) do (
    "!PHP_EXE!" -r "echo extension_loaded('%%e') ? 'SI' : 'NO';" > "%TEMP%\wings_ext_check.txt" 2>nul
    set /p EXT_RESULT=<"%TEMP%\wings_ext_check.txt"
    if "!EXT_RESULT!"=="SI" (
        echo      [OK] Ext %%e
    ) else (
        echo      [FALTA] Ext %%e - Habilitar en php.ini
        set /a WARNINGS+=1
    )
)
del "%TEMP%\wings_ext_check.txt" 2>nul

:: Verificar php.ini para extensiones faltantes
if !WARNINGS! GTR 0 (
    echo.
    echo      Para habilitar extensiones faltantes:
    echo      1. Abrir: !XAMPP_DIR!\php\php.ini
    echo      2. Buscar la linea con ;extension=NOMBRE
    echo      3. Quitar el ; del inicio de la linea
    echo      4. Reiniciar Apache
)

:: ============================================================
:: FASE 3: VERIFICAR MYSQL
:: ============================================================
echo.
echo [FASE 3] Verificando MySQL...
echo ------------------------------------------------------------

set "MYSQL_EXE=!XAMPP_DIR!\mysql\bin\mysql.exe"
if not exist "!MYSQL_EXE!" (
    echo [ERROR] MySQL no encontrado en !XAMPP_DIR!\mysql\bin\
    set /a ERRORS+=1
) else (
    echo [OK] MySQL encontrado: !MYSQL_EXE!

    :: Verificar si MySQL esta corriendo
    "!MYSQL_EXE!" -u root -e "SELECT 1;" >nul 2>&1
    if !ERRORLEVEL! NEQ 0 (
        echo [AVISO] MySQL no esta corriendo. Iniciar desde XAMPP Control Panel.
        set /a WARNINGS+=1
    ) else (
        echo [OK] MySQL esta corriendo

        :: Verificar si la base de datos existe
        "!MYSQL_EXE!" -u root -e "USE `wings-db`;" >nul 2>&1
        if !ERRORLEVEL! NEQ 0 (
            echo [INFO] Base de datos 'wings-db' no existe. Se creara automaticamente.
        ) else (
            echo [OK] Base de datos 'wings-db' existe
        )
    )
)

:: ============================================================
:: FASE 4: VERIFICAR COMPOSER
:: ============================================================
echo.
echo [FASE 4] Verificando Composer...
echo ------------------------------------------------------------

where composer >nul 2>&1
if !ERRORLEVEL! NEQ 0 (
    :: Buscar composer.phar en el proyecto o en XAMPP
    if exist "%PROJECT_DIR%\composer.phar" (
        echo [OK] composer.phar encontrado en el proyecto
        set "COMPOSER_CMD=!PHP_EXE! %PROJECT_DIR%\composer.phar"
    ) else (
        echo [FALTA] Composer no esta instalado
        echo         Descargando Composer...
        "!PHP_EXE!" -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" 2>nul
        if exist "%PROJECT_DIR%\composer-setup.php" (
            "!PHP_EXE!" composer-setup.php --install-dir="%PROJECT_DIR%" --filename=composer.phar
            del "%PROJECT_DIR%\composer-setup.php" 2>nul
            if exist "%PROJECT_DIR%\composer.phar" (
                echo [OK] Composer descargado exitosamente
                set "COMPOSER_CMD=!PHP_EXE! %PROJECT_DIR%\composer.phar"
            ) else (
                echo [ERROR] No se pudo descargar Composer
                echo         Descargar manualmente: https://getcomposer.org/download/
                set /a ERRORS+=1
            )
        ) else (
            echo [ERROR] No se pudo descargar el instalador de Composer
            set /a ERRORS+=1
        )
    )
) else (
    echo [OK] Composer encontrado en PATH
    set "COMPOSER_CMD=composer"
)

:: ============================================================
:: FASE 5: VERIFICAR NODE.JS / NPM
:: ============================================================
echo.
echo [FASE 5] Verificando Node.js y npm...
echo ------------------------------------------------------------

where node >nul 2>&1
if !ERRORLEVEL! NEQ 0 (
    echo [FALTA] Node.js no esta instalado
    echo         Descargar desde: https://nodejs.org/ (version LTS)
    echo         Instalar y reiniciar esta terminal despues.
    set /a WARNINGS+=1
    set "HAS_NODE=0"
) else (
    for /f "tokens=*" %%v in ('node -v') do set "NODE_VERSION=%%v"
    echo [OK] Node.js !NODE_VERSION!
    set "HAS_NODE=1"

    where npm >nul 2>&1
    if !ERRORLEVEL! NEQ 0 (
        echo [FALTA] npm no encontrado
        set /a WARNINGS+=1
    ) else (
        for /f "tokens=*" %%v in ('npm -v') do set "NPM_VERSION=%%v"
        echo [OK] npm !NPM_VERSION!
    )
)

:: ============================================================
:: FASE 6: VERIFICAR APACHE MOD_REWRITE
:: ============================================================
echo.
echo [FASE 6] Verificando Apache...
echo ------------------------------------------------------------

set "HTTPD_CONF=!XAMPP_DIR!\apache\conf\httpd.conf"
if exist "!HTTPD_CONF!" (
    findstr /C:"LoadModule rewrite_module" "!HTTPD_CONF!" | findstr /V "^#" >nul 2>&1
    if !ERRORLEVEL! EQU 0 (
        echo [OK] mod_rewrite habilitado
    ) else (
        echo [AVISO] mod_rewrite parece deshabilitado
        echo         Abrir: !HTTPD_CONF!
        echo         Descomentar: LoadModule rewrite_module modules/mod_rewrite.so
        set /a WARNINGS+=1
    )
) else (
    echo [AVISO] No se encontro httpd.conf
    set /a WARNINGS+=1
)

:: ============================================================
:: RESULTADOS DEL DIAGNOSTICO
:: ============================================================
:CHECK_RESULTS
echo.
echo ============================================================
echo   RESULTADO DEL DIAGNOSTICO
echo ============================================================
echo   Errores criticos: !ERRORS!
echo   Avisos:           !WARNINGS!
echo ============================================================

if !ERRORS! GTR 0 (
    echo.
    echo Hay errores criticos. Corregirlos antes de continuar.
    echo Presiona cualquier tecla para salir...
    pause >nul
    goto :EOF
)

echo.
echo Todo listo para instalar. Continuar con la instalacion?
echo Presiona cualquier tecla para continuar o Ctrl+C para cancelar...
pause >nul

:: ============================================================
:: FASE 7: INSTALAR DEPENDENCIAS PHP
:: ============================================================
echo.
echo [FASE 7] Instalando dependencias PHP (composer install)...
echo ------------------------------------------------------------
cd /d "%PROJECT_DIR%"
!COMPOSER_CMD! install --no-dev --optimize-autoloader
if !ERRORLEVEL! NEQ 0 (
    echo [ERROR] Fallo composer install
    set /a ERRORS+=1
    goto :FINAL
)
echo [OK] Dependencias PHP instaladas

:: ============================================================
:: FASE 8: CONFIGURAR .ENV
:: ============================================================
echo.
echo [FASE 8] Configurando archivo .env...
echo ------------------------------------------------------------

if not exist "%PROJECT_DIR%\.env" (
    copy "%PROJECT_DIR%\.env.example" "%PROJECT_DIR%\.env" >nul
    echo [OK] Archivo .env creado desde .env.example
) else (
    echo [OK] Archivo .env ya existe
)

:: Configurar valores en .env
"!PHP_EXE!" -r "
$env = file_get_contents('%PROJECT_DIR:\=/%/.env');
$env = preg_replace('/^APP_NAME=.*/m', 'APP_NAME=\"Wings\"', $env);
$env = preg_replace('/^APP_URL=.*/m', 'APP_URL=http://gestion-wings', $env);
$env = preg_replace('/^DB_CONNECTION=.*/m', 'DB_CONNECTION=mysql', $env);
$env = preg_replace('/^#?\s*DB_HOST=.*/m', 'DB_HOST=127.0.0.1', $env);
$env = preg_replace('/^#?\s*DB_PORT=.*/m', 'DB_PORT=3306', $env);
$env = preg_replace('/^#?\s*DB_DATABASE=.*/m', 'DB_DATABASE=wings-db', $env);
$env = preg_replace('/^#?\s*DB_USERNAME=.*/m', 'DB_USERNAME=root', $env);
$env = preg_replace('/^#?\s*DB_PASSWORD=.*/m', 'DB_PASSWORD=', $env);
file_put_contents('%PROJECT_DIR:\=/%/.env', $env);
echo 'OK';
"
echo [OK] Valores de .env configurados (MySQL, nombre, URL)

:: Generar APP_KEY
"!PHP_EXE!" artisan key:generate --force
echo [OK] APP_KEY generada

:: ============================================================
:: FASE 9: CREAR BASE DE DATOS
:: ============================================================
echo.
echo [FASE 9] Creando base de datos...
echo ------------------------------------------------------------

"!MYSQL_EXE!" -u root -e "CREATE DATABASE IF NOT EXISTS `wings-db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>nul
if !ERRORLEVEL! NEQ 0 (
    echo [AVISO] No se pudo crear la BD automaticamente.
    echo         Asegurate de que MySQL este corriendo y creala manualmente:
    echo         CREATE DATABASE `wings-db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    set /a WARNINGS+=1
) else (
    echo [OK] Base de datos 'wings-db' lista
)

:: ============================================================
:: FASE 10: MIGRACIONES Y SEEDERS
:: ============================================================
echo.
echo [FASE 10] Ejecutando migraciones y seeders...
echo ------------------------------------------------------------

"!PHP_EXE!" artisan migrate --force
if !ERRORLEVEL! NEQ 0 (
    echo [ERROR] Fallaron las migraciones
    set /a ERRORS+=1
    goto :FINAL
)
echo [OK] Migraciones ejecutadas

"!PHP_EXE!" artisan db:seed --force
if !ERRORLEVEL! NEQ 0 (
    echo [AVISO] Fallaron algunos seeders (puede ser normal si ya habia datos)
    set /a WARNINGS+=1
) else (
    echo [OK] Seeders ejecutados
)

:: ============================================================
:: FASE 11: INSTALAR NODE Y COMPILAR ASSETS
:: ============================================================
echo.
echo [FASE 11] Compilando assets (Node/Vite)...
echo ------------------------------------------------------------

if "!HAS_NODE!"=="1" (
    cd /d "%PROJECT_DIR%"
    call npm install
    if !ERRORLEVEL! NEQ 0 (
        echo [AVISO] Fallo npm install
        set /a WARNINGS+=1
    ) else (
        echo [OK] Dependencias Node instaladas
        call npm run build
        if !ERRORLEVEL! NEQ 0 (
            echo [AVISO] Fallo npm run build
            set /a WARNINGS+=1
        ) else (
            echo [OK] Assets compilados
        )
    )
) else (
    echo [SALTEADO] Node.js no disponible - instalar y ejecutar manualmente:
    echo            npm install
    echo            npm run build
)

:: ============================================================
:: FASE 12: CONFIGURAR VIRTUALHOST APACHE
:: ============================================================
echo.
echo [FASE 12] Configurando VirtualHost en Apache...
echo ------------------------------------------------------------

set "VHOSTS_CONF=!XAMPP_DIR!\apache\conf\extra\httpd-vhosts.conf"
set "HOSTS_FILE=C:\Windows\System32\drivers\etc\hosts"

:: Verificar si ya esta configurado el VirtualHost
findstr /C:"gestion-wings" "!VHOSTS_CONF!" >nul 2>&1
if !ERRORLEVEL! EQU 0 (
    echo [OK] VirtualHost ya configurado en httpd-vhosts.conf
) else (
    echo Agregando VirtualHost...
    (
        echo.
        echo ^<VirtualHost *:80^>
        echo     DocumentRoot "!XAMPP_DIR!\htdocs\gestion-wings\public"
        echo     ServerName gestion-wings
        echo     ^<Directory "!XAMPP_DIR!\htdocs\gestion-wings\public"^>
        echo         AllowOverride All
        echo         Require all granted
        echo     ^</Directory^>
        echo ^</VirtualHost^>
    ) >> "!VHOSTS_CONF!"
    echo [OK] VirtualHost agregado
)

:: Verificar si el host esta en el archivo hosts
findstr /C:"gestion-wings" "!HOSTS_FILE!" >nul 2>&1
if !ERRORLEVEL! EQU 0 (
    echo [OK] Entrada en archivo hosts ya existe
) else (
    echo Agregando entrada al archivo hosts...
    echo 127.0.0.1    gestion-wings >> "!HOSTS_FILE!" 2>nul
    if !ERRORLEVEL! NEQ 0 (
        echo [AVISO] No se pudo escribir en el archivo hosts (requiere permisos de admin)
        echo         Ejecutar este script como Administrador, o agregar manualmente:
        echo         Abrir: !HOSTS_FILE!
        echo         Agregar: 127.0.0.1    gestion-wings
        set /a WARNINGS+=1
    ) else (
        echo [OK] Entrada hosts agregada
    )
)

:: ============================================================
:: FASE 13: LIMPIAR CACHES DE LARAVEL
:: ============================================================
echo.
echo [FASE 13] Limpiando caches...
echo ------------------------------------------------------------

"!PHP_EXE!" artisan config:clear
"!PHP_EXE!" artisan cache:clear
"!PHP_EXE!" artisan route:clear
"!PHP_EXE!" artisan view:clear
echo [OK] Caches limpiadas

:: ============================================================
:: FASE 14: PERMISOS STORAGE
:: ============================================================
echo.
echo [FASE 14] Verificando carpetas de storage...
echo ------------------------------------------------------------

if not exist "%PROJECT_DIR%\storage\logs" mkdir "%PROJECT_DIR%\storage\logs"
if not exist "%PROJECT_DIR%\storage\framework\sessions" mkdir "%PROJECT_DIR%\storage\framework\sessions"
if not exist "%PROJECT_DIR%\storage\framework\views" mkdir "%PROJECT_DIR%\storage\framework\views"
if not exist "%PROJECT_DIR%\storage\framework\cache\data" mkdir "%PROJECT_DIR%\storage\framework\cache\data"
if not exist "%PROJECT_DIR%\bootstrap\cache" mkdir "%PROJECT_DIR%\bootstrap\cache"
echo [OK] Carpetas de storage verificadas

:: Crear storage link
"!PHP_EXE!" artisan storage:link 2>nul
echo [OK] Storage link creado

:: ============================================================
:: FASE 15: VERIFICACION FINAL
:: ============================================================
echo.
echo [FASE 15] Verificacion final...
echo ------------------------------------------------------------

"!PHP_EXE!" artisan route:list --compact >nul 2>&1
if !ERRORLEVEL! EQU 0 (
    echo [OK] Laravel responde correctamente
) else (
    echo [ERROR] Laravel no responde. Revisar errores arriba.
    set /a ERRORS+=1
)

:FINAL
echo.
echo ============================================================
echo   DEPLOY FINALIZADO
echo ============================================================
echo   Errores:  !ERRORS!
echo   Avisos:   !WARNINGS!
echo ============================================================
echo.

if !ERRORS! EQU 0 (
    echo INSTALACION EXITOSA!
    echo.
    echo Pasos finales:
    echo   1. Abrir XAMPP Control Panel
    echo   2. Iniciar Apache y MySQL
    echo   3. Abrir el navegador en: http://gestion-wings
    echo   4. La API esta en: http://gestion-wings/api/
    echo.
    echo Endpoints de prueba:
    echo   http://gestion-wings/api/alumnos
    echo   http://gestion-wings/up          (health check)
) else (
    echo Hubo errores durante la instalacion. Revisar los mensajes arriba.
)

echo.
echo Presiona cualquier tecla para cerrar...
pause >nul
