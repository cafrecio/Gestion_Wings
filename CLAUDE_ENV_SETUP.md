# Instrucciones para Claude: Configurar .env en nueva máquina

## Contexto
Este es un proyecto Laravel 12 + MySQL que corre sobre XAMPP en Windows.
La API es un backend REST (Sanctum) sin frontend SPA propio (es consumida por un front externo).

## Paso a paso para configurar el .env

### 1. Copiar el archivo base
```bash
cp .env.example .env
```

### 2. Editar los siguientes valores en .env

```dotenv
APP_NAME="Wings"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://gestion-wings

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wings-db
DB_USERNAME=root
DB_PASSWORD=
```

**NOTA sobre APP_URL:** El valor `http://gestion-wings` requiere configurar un VirtualHost
en Apache (ver sección VirtualHost abajo). Si no se quiere configurar VirtualHost, usar
`http://localhost/gestion-wings/public` en su lugar.

### 3. Los demás valores se dejan como están en .env.example
No tocar: SESSION, CACHE, QUEUE, MAIL, REDIS, AWS, VITE - todos quedan con los defaults.

### 4. Generar APP_KEY
```bash
php artisan key:generate
```
Esto llena automáticamente `APP_KEY=base64:...` en el .env.

### 5. Crear la base de datos
Desde MySQL (phpMyAdmin o terminal):
```sql
CREATE DATABASE `wings-db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 6. Ejecutar migraciones y seeders
```bash
php artisan migrate
php artisan db:seed
```

Los seeders cargan datos esenciales del sistema:
- FormaPagoSeeder (formas de pago)
- ReglaPrimerPagoSeeder (reglas de primer pago)
- RubrosSeeder (rubros de caja)
- SubrubrosSeeder (subrubros de caja)
- TiposCajaSeeder (tipos de caja)
- CashflowMovimientoSeeder (movimientos iniciales cashflow)
- Usuario test: test@example.com

### 7. Instalar dependencias de Node y compilar assets
```bash
npm install
npm run build
```

## Configuración de VirtualHost en Apache (XAMPP)

### Archivo: C:\xampp\apache\conf\extra\httpd-vhosts.conf
Agregar al final:
```apache
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/gestion-wings/public"
    ServerName gestion-wings
    <Directory "C:/xampp/htdocs/gestion-wings/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Archivo: C:\Windows\System32\drivers\etc\hosts
Agregar esta línea:
```
127.0.0.1    gestion-wings
```

### Reiniciar Apache desde XAMPP después de estos cambios.

## Dependencias del proyecto
- PHP >= 8.2 con extensiones: pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath, gd
- Composer
- Node.js >= 18 + npm
- MySQL 5.7+ o MariaDB 10.3+
- Apache con mod_rewrite habilitado
- Paquete PHP: barryvdh/laravel-dompdf (para generar recibos PDF)

## Verificación rápida
Después de configurar todo, probar:
```bash
php artisan config:cache
php artisan route:list
curl http://gestion-wings/api/alumnos
```
Si responde un JSON (aunque sea vacío `[]`), está funcionando.
