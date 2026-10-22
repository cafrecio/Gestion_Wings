# Incidente: no se puede ingresar al sitio de prueba y no aparece ningún error

22/10/2026 · Claude CyE · Sitio de prueba https://test.gestionar-te.com.ar (base `wingstest`).
Producción no está afectada: no usa reloj simulado.

## Síntoma

Durante la prueba manual, al ingresar con usuario y clave correctos la página vuelve a la pantalla de
ingreso, sin mensaje de error. Con una clave equivocada tampoco aparece el aviso de credenciales.

## Causa (comprobada)

- El reloj simulado de la prueba estaba en el **13/10/2026 18:25** (`wings:fecha-simulada`).
- Laravel calcula el vencimiento de la cookie de sesión con ese reloj: «ahora» + `SESSION_LIFETIME`
  (120 minutos) = **13/10 a las 23:26**.
- El servidor mandaba la cookie así (la fecha real era el 22/10):

  ```
  Set-Cookie: wings-session=…; expires=Tue, 13 Oct 2026 23:26:04 GMT; Max-Age=0; path=/; secure; httponly
  ```

- Para el navegador, que vive en la fecha real, esa cookie **ya venció**: la borra apenas la recibe.
  Sin sesión no se guarda el ingreso ni el mensaje de error, y la pantalla vuelve al login en silencio.

Pasa siempre que el reloj simulado esté en una fecha anterior a la real. Ya había pasado en T74 (Claude
levantó el servidor local con la misma corrección).

## Solución aplicada

Solo en el sitio de prueba, sin tocar código:

1. Copia previa del archivo de configuración: `/root/respaldos/wingstest-env-2026-10-22`.
2. En `/home/wingstest/app/.env`: `SESSION_EXPIRE_ON_CLOSE=true`. La cookie de sesión pasa a no tener
   fecha de vencimiento: dura hasta cerrar el navegador.
3. Recargar la configuración, como `wingstest`:

   ```bash
   cd /home/wingstest/app && sudo -u wingstest /usr/bin/php82 artisan config:cache
   ```

## Comprobación

- La cookie llega sin vencimiento: `Set-Cookie: wings-session=…; path=/; secure; httponly; samesite=lax`.
- Un ingreso con clave equivocada vuelve con «credenciales ingresadas no son válidas» (antes, sin mensaje):
  la sesión se mantiene. Con la clave correcta, se ingresa.
- La cookie `XSRF-TOKEN` sigue llegando vencida. No afecta: el formulario de ingreso y los pedidos de las
  pantallas usan el token guardado en la sesión, no esa cookie.

## Si vuelve a pasar

1. Ver qué cookie manda el servidor:

   ```bash
   curl -s -D - -o /dev/null https://test.gestionar-te.com.ar/login | grep -i set-cookie
   ```

   Si `wings-session` trae `Max-Age=0` o un `expires` anterior a hoy, es este problema.
2. Revisar que `.env` siga con `SESSION_EXPIRE_ON_CLOSE=true` y volver a correr `config:cache` (un
   despliegue o un `optimize:clear` sin `config:cache` puede dejar la configuración vieja).
3. **No** se arregla moviendo el reloj a la fecha real en medio de un día de la prueba.

Si aparece «Demasiados intentos seguidos», es otra cosa: el límite de 5 ingresos por minuto. Se espera un
minuto y se reintenta.

## Mientras dure la prueba

No sacar `SESSION_EXPIRE_ON_CLOSE=true` del sitio de prueba. Al terminar la prueba se puede volver al
`.env` de la copia, si se quiere.

**Pendiente, no urgente:** la causa de fondo es que las cookies toman la fecha del reloj simulado. Se
corrige en el código haciendo que las cookies usen la hora real (relacionado con T32, reloj simulado).
Solo importa en el sitio de prueba.
