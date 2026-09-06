# Servidor — dónde está la información

**La documentación del servidor NO vive en este repositorio.**

Wings es **un producto** de la plataforma Gestionar-te. El servidor, el panel, el
DNS, los certificados y las credenciales son de **la plataforma**, no de Wings:
ahí también van a correr Gestión Taller y los productos que vengan.

Mezclarlos hacía que un cambio de infraestructura pareciera un cambio de Wings.

## Dónde buscar

| Qué | Dónde |
|---|---|
| Estado del servidor, cómo está armado el despliegue, trampas conocidas | `D:\CAB Consultores\Gestionar-te\VPS\ESTADO-SERVIDOR.md` |
| Credenciales — **archivo único** | `D:\CAB Consultores\Gestionar-te\VPS\CREDENCIALES.txt` |
| Decisiones de la plataforma | `D:\CAB Consultores\Gestionar-te\documentacion\decisiones.md` |

**Ninguna credencial se copia a este repositorio, por ningún motivo.** Un archivo
con claves commiteado queda en el historial de git para siempre, aunque después se
borre.

## Lo mínimo que hay que saber desde acá

Wings está publicado en **https://wings.gestionar-te.com.ar**.

| Dato | Valor |
|---|---|
| Código en el servidor | `/home/wings/app` |
| Usuario del sistema | `wings` |
| PHP | 8.2, con un pool propio y aislado |
| Base de datos | `wings`, con un usuario que solo lee y escribe datos |
| Acceso | Por clave SSH, alias `vps` |

**El diseño se compila en el servidor.** `public/build` está en `.gitignore`, así
que los archivos compilados no viajan en el repositorio: el despliegue tiene que
correr `npm ci` y `npm run build`, o el sistema se ve sin estilos.

## Cloudflare — resuelto el 06/09/2026

El sitio **ya está detrás del proxy de Cloudflare**, y el servidor **solo acepta
tráfico web que venga de Cloudflare**: entrando por la IP directa no responde.

Lo que toca al código de Wings es `trustProxies` en `bootstrap/app.php`, con los 22
rangos publicados por Cloudflare. Sin eso, la aplicación vería la misma dirección
para todos los usuarios —cinco intentos fallidos de cualquiera dejarían afuera al
club entero— y creería que no hay cifrado.

Está cubierto por `tests/Feature/ConfianzaEnCloudflareTest.php`, que verifica las
dos mitades: que se confíe en Cloudflare y que **no** se confíe en nadie más.

**Si Cloudflare suma un rango nuevo hay que agregarlo en dos lugares**: acá en
`bootstrap/app.php` y en `/etc/csf/csf.allow` del servidor. Si queda solo en uno, o
la aplicación deja de ver al visitante real, o el tráfico de ese rango no entra.

El detalle del lado del servidor está en `VPS/ESTADO-SERVIDOR.md`.
