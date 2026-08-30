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

## Pendiente que afecta al código de Wings

**Laravel no está configurado para trabajar detrás de un proxy** (no hay
`trustProxies` en `bootstrap/app.php`). El dominio pasa por Cloudflare, así que si
se activa el proxy sin arreglar esto:

- La aplicación vería la misma dirección para todos los usuarios, y el límite de
  `throttle:5,1` del login pasaría a contarlos a todos juntos: **cinco intentos
  fallidos de cualquiera dejarían afuera al club entero**.
- Creería que no hay cifrado y armaría las direcciones con `http://`.

Hoy el subdominio está **sin proxy**, así que no está ocurriendo. No activarlo
hasta resolverlo.
