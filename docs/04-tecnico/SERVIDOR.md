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

## Correo saliente — reparado el 23/09/2026

Estaba roto para todo el servidor, no solo para Wings. Postfix tiene sus tablas en
MySQL —así lo arma CWP— pero le faltaba el paquete `postfix-mysql`, así que
**rechazaba todos los mensajes antes de intentar mandarlos**
(`unsupported dictionary type: mysql`). Se instaló ese paquete.

Con eso los mensajes salieron, pero **Gmail los rechazaba** con `550 5.7.26`: el
dominio no tenía ningún registro que autorizara al servidor a mandar correo en su
nombre. Se publicaron dos registros SPF en Cloudflare, uno para el dominio y otro
para el subdominio de prueba:

```
gestionar-te.com.ar        TXT  v=spf1 ip4:2.25.204.38 ~all
test.gestionar-te.com.ar   TXT  v=spf1 ip4:2.25.204.38 ~all
```

**No hay DKIM.** Con SPF alcanzó para que Gmail acepte, pero si en algún momento
los avisos empiezan a caer en spam, eso es lo que falta.

Del lado de Laravel, **no usar `sendmail -bs`**, que es el valor por defecto: en ese
modo el binario levanta un `smtpd` con el usuario del sitio y no puede abrir los
sockets privados de la cola; el error que se ve es
`Connection to "process /usr/sbin/sendmail -bs -i" has been closed unexpectedly`.
Se entrega por SMTP al propio servidor, que es lo que deja escrito `montar-test.sh`:

```
MAIL_MAILER=smtp
MAIL_URL="smtp://127.0.0.1:25?verify_peer=0"
```

`verify_peer=0` porque el certificado de ese postfix es propio y el destino es la
misma máquina.

**Producción todavía no manda correo.** `/home/wings/app/.env` no tiene ninguna
clave `MAIL_`, y sin `MAIL_MAILER` Laravel escribe los mensajes en el log en vez de
enviarlos. Hay que dejar las mismas tres líneas al desplegar wings.

Se borraron además **16.211 mensajes** que estaban atascados en la cola desde junio,
casi todos avisos automáticos del sistema dirigidos a Carlos que nunca habían salido.

### El SPF del subdominio de prueba anuló su comodín — 23/09/2026

Al publicar el TXT de SPF para `test.gestionar-te.com.ar` se rompió el acceso al sitio de
prueba, con `DNS_PROBE_FINISHED_NXDOMAIN` para todo el mundo. El motivo: ese subdominio no
tenía dirección propia, resolvía por el comodín `*.gestionar-te.com.ar`, y **un comodín deja
de aplicar a un nombre que existe con cualquier otro tipo de registro**. Al crear el TXT, el
nombre pasó a existir y el comodín dejó de darle la dirección.

Se corrigió agregando el registro A explícito de `test.gestionar-te.com.ar` (2.25.204.38,
detrás del proxy de Cloudflare, igual que wings).

**Regla para la próxima:** antes de agregar un TXT a un subdominio que depende del comodín,
crear primero su registro A. Y comprobar desde afuera, no desde el servidor: desde el
servidor seguía respondiendo mientras nadie más podía entrar.
